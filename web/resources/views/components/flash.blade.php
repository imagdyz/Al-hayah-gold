@if (session('status'))
    <div role="status" class="flex items-center gap-3 rounded-2xl bg-up-bg px-4 py-3 text-up"><x-icon name="check" />{{ session('status') }}</div>
@endif
@if ($errors->has('trading') || $errors->has('order'))
    <div role="alert" class="flex items-start gap-3 rounded-2xl bg-warn-bg px-4 py-3 text-warn"><x-icon name="info" class="mt-0.5" />{{ $errors->first('trading') ?: $errors->first('order') }}</div>
@endif
