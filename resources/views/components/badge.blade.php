@props(['type' => 'info'])

@php
$baseClass = 'inline-flex items-center gap-x-1 py-1 px-3 rounded-lg text-xs font-medium border-px';

$typeClass = match ($type) {
'info' => 'bg-blue-50 dark:bg-blue-200 border-blue-500 text-blue-600',
'danger' => 'bg-red-50 dark:bg-red-200 border-red-500 text-red-600',
'warning' => 'bg-yellow-50 dark:bg-yellow-200 border-yellow-500 text-yellow-600 dark:text-yellow-700',
'success' => 'bg-green-50 dark:bg-green-200 border-green-500 text-green-600',
};

$badgeClass = "$baseClass $typeClass";
@endphp

<span class="{{ $badgeClass }}">{{ $slot }}</span>
