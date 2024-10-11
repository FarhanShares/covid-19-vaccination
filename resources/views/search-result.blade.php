<x-layouts.app>
    <x-section class="max-w-xl mx-auto">
        <h2 class="text-xl font-semibold text-black dark:text-white">Search Result</h2>
        <p class="mt-0 text-sm/relaxed">
            NID: 123123123
        </p>

        Status: {{ $status->label() }}

        <a href="{{ route('search') }}" class="mt-6 rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">
            Search Again? Click here.
        </a>

    </x-section>
</x-layouts.app>
