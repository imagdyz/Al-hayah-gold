<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>دخول لوحة التحكم · الحياة جولد</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-ink p-4 font-sans" style="background: radial-gradient(900px 500px at 50% 20%, #3A2E1A 0%, #12100C 70%)">
    <form method="POST" action="{{ route('admin.login.store') }}" class="card flex w-full max-w-[420px] flex-col gap-5 p-8">
        @csrf
        <x-logo :size="40" />
        <h1 class="m-0 font-display text-2xl font-bold">لوحة تحكم الفروع</h1>
        <div><label for="email" class="label">الإيميل</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field" dir="ltr">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
        <div><label for="password" class="label">كلمة السر</label><input id="password" name="password" type="password" required class="field" dir="ltr"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="size-4 accent-gold-deep">افتكرني</label>
        <button class="btn btn-lg btn-gold">دخول</button>
    </form>
</body>
</html>
