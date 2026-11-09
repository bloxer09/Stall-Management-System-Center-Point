@props(['href' => null, 'danger' => false])
@php
    $classes = 'btn btn-ghost btn-xs font-bold rounded-lg border transition min-h-11 min-w-11 gap-1.5 group ' . ($danger
        ? 'text-red-700 dark:text-red-400 border-error/20 hover:bg-error hover:text-error-content dark:hover:text-error-content'
        : 'text-primary border-primary/20 hover:bg-primary hover:text-primary-content');
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes])->merge(['data-table-action' => '']) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class([$classes])->merge(['type' => 'button', 'data-table-action' => '']) }}>{{ $slot }}</button>
@endif
