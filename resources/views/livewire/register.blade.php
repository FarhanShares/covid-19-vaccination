<x-section class="max-w-xl mx-auto">
    <form wire:submit="register" class="w-full">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-input-text wire:model="name" id="name" class="block w-full mt-1" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- NID -->
        <div class="mt-4">
            <x-input-label for="nid" :value="__('National ID')" />
            <x-input-text wire:model="nid" id="nid" class="block w-full mt-1" type="number" name="nid" required autocomplete="nid" />
            <x-input-error :messages="$errors->get('nid')" class="mt-2" />
        </div>

        <!-- DOB -->
        <div class="mt-4">
            <x-input-label for="dob" :value="__('Date of birth')" />
            <x-input-text wire:model="dob" id="dob" class="block w-full mt-1" type="date" name="dob" required autocomplete="date_of_birth" />
            <x-input-error :messages="$errors->get('dob')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-input-text wire:model="email" id="email" class="block w-full mt-1" type="email" name="email" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Vaccine Center -->
        <div class="mt-4">
            <x-input-label for="vaccineCenter" :value="__('Choose Vaccine Center')" />
            <x-input-select name="vaccineCenter" id="vaccineCenter" wire:model='vaccineCenter' class="block w-full mt-1" :data="$vaccineCenters" />
            <x-input-error :messages="$errors->get('vaccineCenter')" class="mt-2" />
        </div>


        <div class="flex items-center justify-end mt-4">
            <a href="{{ route('search') }}" class="text-sm text-gray-600 underline rounded-md dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" wire:navigate>

                {{ __('Already registered? View status.') }}
            </a>

            <x-button-primary class="ms-4">
                {{ __('Register') }}
            </x-button-primary>
        </div>
    </form>

</x-section>
