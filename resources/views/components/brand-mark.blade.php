@props([
    'size' => 38,
])

{{--
    AcadVault emblem (vault + cap only, no wordmark) with a transparent
    background — generated from public/images/logo.png.
    The artwork is navy, so on dark surfaces place it on a light tile.
--}}
@php
    $mark = public_path('images/logo-mark.png');
@endphp

@if (is_file($mark))
    <img src="{{ asset('images/logo-mark.png') }}" alt=""
         width="{{ $size }}" height="{{ $size }}"
         {{ $attributes->merge(['class' => 'av-logo-img']) }}
         style="object-fit:contain;display:block;">
@else
    {{-- Fallback if the derivative hasn't been generated --}}
    <svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48"
         fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="AcadVault">
        <rect x="6" y="14" width="36" height="28" rx="5" fill="#22375a"/>
        <circle cx="24" cy="28" r="8.5" fill="#f7f3e8"/>
        <circle cx="24" cy="26.2" r="2.6" fill="#22375a"/>
        <path d="M22.6 27.6h2.8l.7 5h-4.2z" fill="#22375a"/>
        <path d="M24 3.5 44 11l-20 7.5L4 11z" fill="#c8963a"/>
    </svg>
@endif
