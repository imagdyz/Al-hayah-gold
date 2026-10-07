<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PieceStatus;
use App\Models\Invoice;
use App\Models\Piece;
use App\Models\Setting;
use App\Models\User;
use App\Services\GoldPricing;
use App\Services\PosReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->first();
    }

    private function ring(): Piece
    {
        return Piece::where('barcode', 'AH-0108-1-01')->firstOrFail();
    }

    private function siteStock(Piece $piece): int
    {
        return (int) DB::table('branch_product')->where('product_id', $piece->product_id)->where('branch_id', $piece->branch_id)->value('quantity');
    }

    /** A sale of the demo ring plus 4.8 g of 21k scrap taken in exchange. */
    private function exchange(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin())->postJson('/admin/pos', array_merge([
            'customer_name' => 'أحمد',
            'customer_phone' => '01000000009',
            'settlement' => 'exchange',
            'sales' => [['piece_id' => $this->ring()->id, 'gram_price' => 5000, 'making_fee' => 650]],
            'purchases' => [['description' => 'دبلة قديمة', 'karat' => 21, 'gross_weight' => 5, 'net_weight' => 4.8, 'gram_price' => 5400]],
        ], $overrides));
    }

    public function test_pages_render_for_admins_only(): void
    {
        $this->get('/admin/pos')->assertRedirect('/admin/login');
        $this->actingAs(User::where('is_admin', false)->first())->get('/admin/pieces')->assertForbidden();

        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/pos', '/admin/invoices', '/admin/pieces', '/admin/pieces/create', '/admin/reports', '/admin/shop', '/admin/pieces/'.$this->ring()->id.'/edit', '/admin/products/'.$this->ring()->product->slug.'/edit', '/admin/pieces/create?product='.$this->ring()->product_id] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_piece_needs_its_tag_code_and_a_product(): void
    {
        $ring = $this->ring();
        $before = $this->siteStock($ring);
        $this->actingAs($this->admin());

        $this->post('/admin/pieces', ['product_id' => $ring->product_id, 'weight_g' => 2.9, 'making_fee' => 1800])->assertSessionHasErrors('barcode');
        $this->post('/admin/pieces', ['barcode' => '7003211859', 'weight_g' => 2.9, 'making_fee' => 1800])->assertSessionHasErrors('product_id');

        $this->post('/admin/pieces', ['barcode' => '7003211859', 'product_id' => $ring->product_id, 'branch_id' => $ring->branch_id, 'weight_g' => 2.9, 'making_fee' => 1800, 'after' => 'another'])
            ->assertRedirect('/admin/pieces/create?product='.$ring->product_id);
        $piece = Piece::where('barcode', '7003211859')->firstOrFail();
        $this->assertSame($ring->product->name, $piece->name);
        $this->assertSame(18, $piece->karat);
        $this->assertSame($before + 1, $this->siteStock($ring), 'the site sees the new piece');

        $this->post('/admin/pieces', ['barcode' => '7003211859', 'product_id' => $ring->product_id, 'weight_g' => 1, 'making_fee' => 0])->assertSessionHasErrors('barcode');

        // A piece of a model the site doesn't have yet makes a hidden product.
        $this->post('/admin/pieces', ['barcode' => 'A010204394002336', 'product_id' => 'new', 'new_name' => 'حلق فراشة', 'new_category' => 'earring', 'new_karat' => 18, 'weight_g' => 2.36, 'making_fee' => 400])->assertRedirect();
        $new = Piece::where('barcode', 'A010204394002336')->firstOrFail()->product;
        $this->assertSame('حلق فراشة', $new->name);
        $this->assertFalse($new->is_published);
    }

    public function test_the_scanner_finds_pieces_in_stock_only(): void
    {
        $this->actingAs($this->admin());
        $this->getJson('/admin/pos/piece?barcode=AH-0108-1-01')
            ->assertOk()
            ->assertJsonPath('id', $this->ring()->id)
            ->assertJsonPath('gram_price', app(GoldPricing::class)->sell('18'));
        // A 2D tag that carries more than the code still finds the piece.
        $this->getJson('/admin/pos/piece?barcode='.urlencode('K:18;W:2.8;AH-0108-1-01'))->assertOk()->assertJsonPath('id', $this->ring()->id);
        $this->getJson('/admin/pos/piece?barcode=nope')->assertNotFound();

        $this->ring()->update(['status' => PieceStatus::Sold]);
        $this->getJson('/admin/pos/piece?barcode=AH-0108-1-01')->assertStatus(422);
    }

    public function test_an_exchange_invoice_is_totalled_on_the_server(): void
    {
        Setting::put('invoice_start', 1831);
        $stock = $this->siteStock($this->ring());

        $this->exchange()->assertOk()->assertJsonPath('number', 1831);

        $invoice = Invoice::where('number', 1831)->firstOrFail();
        // 2.8 g × 5000 + 650 making, and 4.8 g after the deduction × 5400.
        $this->assertEquals(14650, $invoice->sales_total);
        $this->assertEquals(25920, $invoice->purchases_total);
        $this->assertEquals(-11270, $invoice->net);
        $this->assertSame('المحل يدفع', $invoice->netLabel());
        $this->assertSame(PieceStatus::Sold, $this->ring()->status);
        $this->assertSame($stock - 1, $this->siteStock($this->ring()), 'sold in the shop, gone from the site');
        $this->assertCount(2, $invoice->lines);

        // The next invoice follows on, whatever the start setting says now.
        $this->actingAs($this->admin())->postJson('/admin/pos', [
            'settlement' => 'cash',
            'purchases' => [['description' => 'كسر', 'karat' => 18, 'gross_weight' => 2, 'net_weight' => 2, 'gram_price' => 4600]],
        ])->assertOk()->assertJsonPath('number', 1832);
    }

    public function test_a_piece_is_never_sold_twice(): void
    {
        $this->exchange()->assertOk();
        $this->exchange()->assertStatus(422)->assertJsonValidationErrors('sales');

        $other = Piece::where('barcode', 'AH-0114-1-01')->firstOrFail();
        $this->exchange(['sales' => [
            ['piece_id' => $other->id, 'gram_price' => 4600, 'making_fee' => 900],
            ['piece_id' => $other->id, 'gram_price' => 4600, 'making_fee' => 900],
        ]])->assertStatus(422);
        $this->assertSame(PieceStatus::InStock, $other->fresh()->status);
    }

    public function test_bad_invoices_are_refused(): void
    {
        $this->exchange(['sales' => [], 'purchases' => []])->assertStatus(422);
        $this->exchange(['purchases' => [['description' => 'كسر', 'karat' => 21, 'gross_weight' => 4, 'net_weight' => 4.5, 'gram_price' => 5400]]])
            ->assertStatus(422)->assertJsonValidationErrors('purchases.0.net_weight');
        $this->exchange(['settlement' => 'wallet'])->assertStatus(422);
        $this->assertSame(0, Invoice::count());
    }

    public function test_voiding_puts_the_piece_back_and_drops_it_from_reports(): void
    {
        $this->exchange()->assertOk();
        $invoice = Invoice::firstOrFail();
        $report = app(PosReport::class);

        $stock = $this->siteStock($this->ring());
        $before = $report->summary(today(), now()->endOfDay());
        $this->assertEquals(14650, $before['sales']);
        $this->assertEquals(14650 - (float) $this->ring()->cost, $before['profit']);

        $this->actingAs($this->admin())->post("/admin/invoices/{$invoice->number}/void", ['reason' => 'غلط في الوزن'])->assertRedirect();

        $this->assertSame(InvoiceStatus::Voided, $invoice->fresh()->status);
        $this->assertSame(PieceStatus::InStock, $this->ring()->status);
        $this->assertSame($stock + 1, $this->siteStock($this->ring()));
        $after = $report->summary(today(), now()->endOfDay());
        $this->assertEquals(0, $after['sales']);
        $this->assertSame(0, $after['invoices']);
    }

    public function test_sold_pieces_are_locked(): void
    {
        $this->exchange()->assertOk();
        $ring = $this->ring();

        $this->put("/admin/pieces/{$ring->id}", ['barcode' => $ring->barcode, 'product_id' => $ring->product_id, 'weight_g' => 1, 'making_fee' => 0])->assertForbidden();
        $this->delete("/admin/pieces/{$ring->id}")->assertForbidden();

        $fresh = Piece::where('barcode', 'AH-0109-1-01')->firstOrFail();
        $this->delete("/admin/pieces/{$fresh->id}")->assertRedirect('/admin/pieces');
        $this->assertNull($fresh->fresh());
    }

    public function test_invoices_and_labels_print(): void
    {
        Setting::put('shop_phone', '0501234567');
        $this->exchange()->assertOk();
        $invoice = Invoice::firstOrFail();
        $this->actingAs($this->admin());

        $this->get("/admin/invoices/{$invoice->number}")->assertOk()->assertSee('دبلة قديمة');
        $this->get("/admin/invoices/{$invoice->number}/print?format=a5&preview=1")
            ->assertOk()->assertSee('السنبلاوين: المشاية الجديدة')->assertSee('0501234567')->assertSee($invoice->label())->assertSee('وزن التحييف')->assertDontSee('window.print');
        $this->get("/admin/invoices/{$invoice->number}/print?format=80mm")->assertOk()->assertSee('المحل يدفع')->assertSee('window.print');
        $this->get('/admin/pieces/labels?ids='.$this->ring()->id.'&preview=1')->assertOk()->assertSee('<svg', false)->assertSee('AH-0108-1-01');
        $this->get('/admin/pieces?q=AH-0108-1-01&status=all')->assertOk()->assertSee('AH-0108-1-01');
        $this->get('/admin/invoices?q='.$invoice->number)->assertOk()->assertSee($invoice->label());
        $this->get('/admin/reports?from=2026-01-01&to=2030-01-01')->assertOk();
    }

    public function test_shop_details_are_saved(): void
    {
        $this->actingAs($this->admin())->put('/admin/shop', [
            'shop_name' => 'الحياة جولد', 'shop_tagline' => 'ذهب', 'shop_address' => 'السنبلاوين', 'shop_phone' => '', 'invoice_start' => 500,
        ])->assertRedirect();

        $this->assertSame(500, Setting::int('invoice_start'));
        $this->assertSame('', Setting::get('shop_phone'));
    }
}
