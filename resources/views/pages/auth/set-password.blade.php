<x-app-layout>
    @push('page-title')Set Password — @endpush
    <livewire:auth.set-password :user="$user" :signature-valid="$signatureValid" :fingerprint="$fingerprint" />
</x-app-layout>
