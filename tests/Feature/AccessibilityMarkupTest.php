<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdvancedReports;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class AccessibilityMarkupTest extends TestCase
{
    use RefreshDatabase;

    private function bladeSource(string $path): string
    {
        return file_get_contents(resource_path("views/{$path}.blade.php"));
    }

    /** @return array<string, string[]> view path => button tags */
    private function buttonTags(): array
    {
        $tags = [];
        foreach (File::allFiles(resource_path('views/livewire')) as $file) {
            preg_match_all('/<button(?:\{\{.*?\}\}|[^>])*>/s', $file->getContents(), $m);
            $tags[$file->getRelativePathname()] = $m[0];
        }

        return $tags;
    }

    public function test_every_modal_close_button_is_labelled_and_closes_on_escape(): void
    {
        $checked = 0;
        foreach ($this->buttonTags() as $file => $tags) {
            foreach ($tags as $tag) {
                if (!str_contains($tag, 'btn-circle') || !preg_match('/wire:click="((?:request)?[cC]lose\w*)"/', $tag, $m)) {
                    continue;
                }
                $checked++;
                $this->assertStringContainsString('aria-label="Close"', $tag, "{$file}: {$m[1]} close button needs aria-label");
                $this->assertStringContainsString('keydown.escape.window', $tag, "{$file}: {$m[1]} modal should close on Escape");
            }
        }
        $this->assertGreaterThanOrEqual(12, $checked);
    }

    public function test_icon_only_controls_have_labels(): void
    {
        $this->assertStringContainsString('aria-live="polite"', $this->bladeSource('layouts/app'));
        $this->assertStringContainsString('aria-label="Toggle dark mode"', $this->bladeSource('layouts/navigation'));
        $this->assertStringContainsString('aria-label="Notifications"', $this->bladeSource('livewire/navbar-notification-bell'));
        foreach (['livewire/admin/ticket-center', 'livewire/user/ticket-manager'] as $chat) {
            $this->assertStringContainsString('aria-label="Send message"', $this->bladeSource($chat));
            $this->assertStringContainsString('aria-label="Attach files"', $this->bladeSource($chat));
        }
    }

    public function test_revenue_chart_has_table_alternative(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = Livewire::actingAs($admin)->test(AdvancedReports::class)->html();

        $this->assertStringContainsString('View as table', $html);
        $this->assertMatchesRegularExpression('/<table[^>]*data-revenue-table/', $html);
        $this->assertStringContainsString(now()->format('M Y'), $html);
    }
}
