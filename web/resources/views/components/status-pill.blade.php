@props(['status'])
<span {{ $attributes->merge(['class' => 'pill pill-'.$status->tone()]) }}><span class="size-1.5 rounded-full bg-current opacity-80"></span>{{ $status->label() }}</span>
