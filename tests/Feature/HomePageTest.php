<?php

namespace Tests\Feature;

use App\Models\Stall;
use App\Models\StallLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_hero_leads_with_browse_stalls_and_live_stats(): void
    {
        $a = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $b = StallLocation::create(['name' => 'Second Floor', 'sort_order' => 2]);
        foreach ([['A-1', $a, 'available'], ['A-2', $a, 'available'], ['A-3', $a, 'occupied'], ['B-1', $b, 'available'], ['B-2', $b, 'maintenance']] as [$n, $loc, $status]) {
            Stall::create(['stall_number' => $n, 'location_id' => $loc->id, 'price' => 1000, 'status' => $status]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-hero-primary', $html);
        $this->assertMatchesRegularExpression('#<a[^>]*href="' . preg_quote(route('stalls.browse'), '#') . '"[^>]*data-hero-primary#', $html);
        $this->assertStringContainsString('2 Floors', $html);
        $this->assertStringContainsString('3 Available Stalls', $html);
        $this->assertStringContainsString('5 Total Stalls', $html);
        $this->assertStringNotContainsString('3 Building Levels', $html);
        $this->assertStringNotContainsString('open-register-modal\')" class="btn btn-neutral', $html);
    }
}
