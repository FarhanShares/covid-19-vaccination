<x-layouts.app>
    <x-section class="max-w-xl mx-auto">
        <h2 class="text-xl font-semibold text-black dark:text-white">Search Result</h2>

        <section class="flex items-center gap-2 text-sm/relaxed">
            <div class="w-20">NID</div>
            <div class="w-2">:</div>
            <div class="w-full">{{ $nid }}</div>
        </section>

        <section class="flex items-center gap-2 text-sm/relaxed">
            <div class="w-20">Status</div>
            <div class="w-2">:</div>
            <div class="w-full">
                <x-badge type="{{ $status->badge() }}">
                    {{ $status->label() }}
                </x-badge>
            </div>
        </section>

        @if($scheduledAt)
        <section class="flex items-center gap-2 text-sm/relaxed">
            <div class="w-20">Date</div>
            <div class="w-2">:</div>
            <div class="w-full">{{ $scheduledAt }}</div>
        </section>
        @endif

        @if($centerName)
        <section class="flex items-center gap-2 text-sm/relaxed">
            <div class="w-[4.25rem]">Center</div>
            <div class="w-2">:</div>
            <div class="w-full">{{ $centerName }}</div>
        </section>
        @endif



        <a href="{{ $linkHref }}" class="mt-6 rounded-sm underline hover:text-black focus:outline-none focus-visible:ring-1 focus-visible:ring-[#FF2D20] dark:hover:text-white">
            {{ $linkTitle }}
        </a>

    </x-section>
</x-layouts.app>
