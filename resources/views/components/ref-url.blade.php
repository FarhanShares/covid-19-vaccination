@props(['href'])

<a class="underline rounded-sm hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-blue-500 dark:hover:text-white dark:focus-visible:ring-blue-500" href="{{ $href }}">{{ $slot }}</a>
