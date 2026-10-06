<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every save/submit/destructive action button disables itself and shows a spinner while its
 * Livewire action runs. Checked on the Blade source because most of these buttons live in modals.
 */
class LoadingStatesTest extends TestCase
{
    public static function actionButtons(): array
    {
        return [
            ['admin/stall-manager', 'saveStall'],
            ['admin/stall-manager', 'deleteStall'],
            ['admin/floor-manager', 'saveFloor'],
            ['admin/floor-manager', 'deleteFloor'],
            ['admin/payment-manager', 'recordManualPayment'],
            ['admin/payment-manager', 'markAsPaid'],
            ['admin/lease-mapping-manager', 'saveMapping'],
            ['admin/lease-mapping-manager', 'recordInitialCashPayment'],
            ['admin/lease-mapping-manager', 'terminateLease'],
            ['admin/system-settings', 'saveSettings'],
            ['user/invoices-checkout', 'payWithPayMongo'],
            ['user/invoices-checkout', 'paySingleInvoice'],
        ];
    }

    #[DataProvider('actionButtons')]
    public function test_action_button_shows_busy_state(string $view, string $method): void
    {
        $source = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

        // A button tag, allowing ">" inside Blade echoes such as {{ $lease->id }}.
        preg_match_all('/<button(?:\{\{.*?\}\}|[^>])*>/s', $source, $tags);
        $busyButtons = array_filter($tags[0], fn ($tag) => str_contains($tag, 'wire:target="' . $method . '"')
            && str_contains($tag, 'wire:loading.attr="disabled"'));

        $this->assertNotEmpty($busyButtons, "{$view}: the {$method} button must be disabled while it runs");
        $this->assertStringContainsString(
            'wire:loading wire:target="' . $method . '" class="loading loading-spinner',
            $source,
            "{$view}: the {$method} button must show a spinner while it runs"
        );
    }

    public static function filteredLists(): array
    {
        return [['admin/stall-manager'], ['admin/payment-manager'], ['admin/lease-mapping-manager'], ['admin/application-manager']];
    }

    #[DataProvider('filteredLists')]
    public function test_list_dims_while_filtering(string $view): void
    {
        $source = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));

        $this->assertStringContainsString('wire:loading.class="opacity-60"', $source, "{$view}: list should dim while search/filter results load");
    }
}
