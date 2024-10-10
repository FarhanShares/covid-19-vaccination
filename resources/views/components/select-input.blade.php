@props(['disabled' => false, 'data' => [], 'selected' => null])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm']) }}>
    <!-- Placeholder option that is selected by default and not selectable again -->
    <option value="" @if(is_null($selected) || $selected==='' ) selected @endif hidden>Choose One</option>

    <!-- Dynamic options from the data prop -->
    @foreach ($data as $value => $label)
    <option value="{{ $value }}" @if($value==$selected) selected @endif>{{ $label }}</option>
    @endforeach

    <!-- Additional slot options if needed -->
    {{ $slot }}
</select>
