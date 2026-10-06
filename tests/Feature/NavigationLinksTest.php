<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_links_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('admin.reports') . '"', false);
    }

    public function test_tenant_sidebar_links_documents(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);

        $this->actingAs($tenant)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('user.documents') . '"', false);
    }
}
