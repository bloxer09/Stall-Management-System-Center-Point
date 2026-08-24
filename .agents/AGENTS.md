# Workspace Rules & Learned Behaviors

### Livewire Component Route Pattern
When creating route endpoints for Livewire components in this project, do NOT bind the Livewire component class directly to `Route::get()`. Always wrap the Livewire component inside a wrapper Blade page view (e.g., `resources/views/pages/admin/feature.blade.php`) utilizing `<x-app-layout>`:

```php
// Correct Route Definition
Route::get('/settings', function () {
    return view('pages.admin.settings');
})->name('settings');
```

```blade
<!-- resources/views/pages/admin/settings.blade.php -->
<x-app-layout>
    <livewire:admin.system-settings />
</x-app-layout>
```
