@php $inputName = 'nid_search'; @endphp

<form {{ $attributes }} method="POST" action="{{ route('search.result') }}">
    <label for="{{ $inputName }}" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
    <div class="relative">
        <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
            </svg>
        </div>

        @csrf

        <input name="{{ $inputName }}" type="search" autofocus placeholder="13 or 17-digit NID" required id="nid_search" class="block w-full p-4 text-sm border border-gray-300 rounded-lg text-gray-950 ps-10 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-950 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />

        <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Search</button>
    </div>

    <x-input-error :messages="$errors->get($inputName)" class="mt-2" />

</form>
