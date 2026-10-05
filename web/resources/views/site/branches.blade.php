<x-layouts.site title="الفروع">
    <x-page-head title="فروعنا" lead="نفس السعر في كل فرع وفي التطبيق. احجز معاد استلام أو معاينة قطعة قبل ما تنزل." />
    <div class="container-site py-10 pb-20">
        @include('site.partials.branches', ['branches' => $branches])
    </div>
</x-layouts.site>
