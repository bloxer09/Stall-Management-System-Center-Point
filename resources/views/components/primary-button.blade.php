<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary rounded-xl font-bold text-xs uppercase shadow-xs']) }}>
    {{ $slot }}
</button>
