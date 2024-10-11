<x-layouts.app>
    <x-section class="max-w-xl mx-auto">
        <h2 class="text-xl font-semibold text-black dark:text-white">Search</h2>
        <p class="mt-0 text-sm/relaxed">
            Look-up your registration or vaccination status by your NID.
        </p>

        <x-form-search class="block w-full" />

        <a href="{{ route('register') }}" class="mt-6 rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">
            Not registered yet? Sign up.
        </a>

    </x-section>
</x-layouts.app>
