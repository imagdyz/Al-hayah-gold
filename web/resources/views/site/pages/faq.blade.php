<x-layouts.site title="الأسئلة الشائعة">
    <x-page-head title="الأسئلة الشائعة" />
    <div class="container-site flex max-w-[860px] flex-col gap-3 py-10 pb-20">
        @foreach ([
            ['السعر بيتحسب إزاي؟', 'سعر الجرام بيتحدث كل دقيقة حسب البورصة العالمية وسعر الصرف. سعر القطعة = الوزن × سعر العيار + المصنعية.'],
            ['السعر بيتثبّت لحد إمتى؟', 'سعر السبائك بيتثبّت '.\App\Models\Setting::int('price_lock_minutes').' دقيقة من تأكيد الطلب، وحجز المجوهرات بيفضل '.\App\Models\Setting::int('reservation_hours').' ساعة.'],
            ['هل بتحفظوا ذهب باسمي؟', 'لأ. مفيش محفظة ولا رصيد. كل طلب بيتقفل باستلامك للذهب من الفرع بفاتورة.'],
            ['أستلم إزاي؟', 'تروح الفرع اللي اخترته، توري كود الطلب وبطاقتك الشخصية، وتدفع الباقي لو فيه.'],
            ['ينفع أبيع ذهب قديم؟', 'أيوه. احجز معاد من صفحة بيع ذهبك، والذهب بيتوزن ويتفحص قدامك في الفرع وبتاخد قيمته بسعر اللحظة.'],
            ['الصور حقيقية؟', 'الصور الحالية توضيحية لحد تصوير القطع من المحل. [سيتم التحديث]'],
        ] as [$q, $a])
            <details class="card group p-5 open:shadow-raised">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-bold">{{ $q }}<x-icon name="chevron-down" class="transition group-open:rotate-180" /></summary>
                <p class="m-0 mt-3 leading-[1.8] text-muted">{{ $a }}</p>
            </details>
        @endforeach
    </div>
</x-layouts.site>
