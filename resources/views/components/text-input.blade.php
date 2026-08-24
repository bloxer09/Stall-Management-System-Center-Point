@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input input-bordered rounded-xl text-sm w-full bg-base-100 text-base-content border-base-300 focus:outline-none focus:border-primary']) }}>
