<?php

namespace Tests\Unit;

use App\Support\Code128;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class Code128Test extends TestCase
{
    public function test_every_pattern_is_eleven_modules_wide(): void
    {
        $this->assertCount(107, Code128::PATTERNS);
        $this->assertCount(107, array_unique(Code128::PATTERNS));
        foreach (Code128::PATTERNS as $value => $pattern) {
            $this->assertSame($value === Code128::STOP ? 13 : 11, array_sum(str_split($pattern)), "symbol {$value}");
        }
    }

    public function test_checksum_and_framing(): void
    {
        // Start B (104) + P·1 + J·2 + J·3 + 1·4 + 2·5 + 3·6 + C·7 = 879, and 879 mod 103 = 55.
        $this->assertSame([104, 48, 42, 42, 17, 18, 19, 35, 55, 106], Code128::symbols('PJJ123C'));
    }

    public function test_the_bars_decode_back_to_the_text(): void
    {
        $widths = Code128::widths('20000123');
        $lookup = array_flip(Code128::PATTERNS);
        $chunks = array_map(fn ($c) => implode('', $c), array_chunk(array_slice($widths, 0, -7), 6));
        $values = array_map(fn ($c) => $lookup[$c], $chunks);

        $this->assertSame(Code128::START_B, array_shift($values));
        array_pop($values); // checksum
        $this->assertSame('20000123', implode('', array_map(fn ($v) => chr($v + 32), $values)));
        $this->assertSame('2331112', implode('', array_slice($widths, -7)));
    }

    public function test_svg_has_one_rect_per_bar(): void
    {
        $svg = Code128::svg('2000');
        $bars = intdiv(count(Code128::widths('2000')) + 1, 2);
        $this->assertSame($bars + 1, substr_count($svg, '<rect'));
    }

    public function test_non_ascii_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Code128::symbols('ذهب');
    }
}
