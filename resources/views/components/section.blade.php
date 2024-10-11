<div {{ $attributes->merge(['class' => 'flex w-full flex-col items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800']) }}>
    {{ $slot }}
</div>
