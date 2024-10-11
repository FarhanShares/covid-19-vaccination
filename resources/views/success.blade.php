<x-layouts.app>
    <x-section class="max-w-xl mx-auto">
        <h2 class="text-xl font-semibold text-black dark:text-white">
            Congrats! You have successfully registerd and will be notified a day earlier on your appointment day.
        </h2>
        <p class="mt-0 text-sm/relaxed">
            NID: {{ $nid }}
        </p>

        {{-- Status: {{ $user->status->label() }} --}}

        <a href="{{ route('search') }}" class="mt-6 rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">
            Want update on appoinntment status? Click here.
        </a>

    </x-section>
</x-layouts.app>
