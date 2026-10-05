<x-layouts.site title="كود التأكيد">
    <div class="container-site flex justify-center py-16">
        <div class="card w-full max-w-[460px] p-7 sm:p-10">
            <form method="POST" action="{{ route('login.check') }}" class="flex flex-col gap-5">
                @csrf
                <x-flash />
                <div class="flex flex-col gap-2">
                    <h1 class="m-0 font-display text-[2rem] font-bold">اكتب الكود</h1>
                    <p class="m-0 leading-relaxed text-muted">بعتناه على <b dir="ltr">{{ \App\Services\Phone::mask($phone) }}</b>. <a href="{{ route('login') }}">غيّر الرقم</a></p>
                </div>
                <div>
                    <label for="code" class="label">كود التأكيد</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" dir="ltr" required autofocus
                           class="field min-h-16 text-center font-display text-3xl tracking-[.5em]">
                    @error('code')<p class="error">{{ $message }}</p>@enderror
                </div>
                <button class="btn btn-lg btn-gold">تأكيد</button>
            </form>
            <form method="POST" action="{{ route('login.send') }}" class="mt-4 text-center">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">
                <button class="text-sm font-semibold text-gold-dark">ابعت الكود تاني</button>
            </form>
        </div>
    </div>
</x-layouts.site>
