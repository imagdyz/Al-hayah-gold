<x-layouts.site title="تسجيل الدخول">
    <div class="container-site flex justify-center py-16">
        <div class="card w-full max-w-[460px] p-7 sm:p-10">
            <form method="POST" action="{{ route('login.send') }}" class="flex flex-col gap-5">
                @csrf
                <span class="flex size-14 items-center justify-center rounded-2xl bg-ink"><x-logo :size="32" tone="dark" layout="mark" /></span>
                <div class="flex flex-col gap-2">
                    <h1 class="m-0 font-display text-[2rem] font-bold">ادخل برقم موبايلك</h1>
                    <p class="m-0 leading-relaxed text-muted">هنبعتلك كود من 6 أرقام. مفيش كلمة سر.</p>
                </div>
                <div>
                    <label for="phone" class="label">رقم الموبايل</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" value="{{ old('phone') }}" placeholder="01X XXXX XXXX" required class="field min-h-14 text-right text-lg">
                    @error('phone')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="label">اسمك <span class="font-normal text-muted">(لو أول مرة)</span></label>
                    <input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" class="field">
                </div>
                <button class="btn btn-lg btn-gold">ابعت الكود</button>
                <p class="m-0 text-xs leading-relaxed text-muted">بالدخول إنت موافق على <a href="{{ route('page', 'terms') }}">الشروط والأحكام</a> و<a href="{{ route('page', 'privacy') }}">سياسة الخصوصية</a>.</p>
            </form>
        </div>
    </div>
</x-layouts.site>
