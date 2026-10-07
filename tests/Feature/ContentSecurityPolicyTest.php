<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function nonceFrom(string $header): string
    {
        $this->assertMatchesRegularExpression("/'nonce-([A-Za-z0-9+\\/=]+)'/", $header);
        preg_match("/'nonce-([A-Za-z0-9+\\/=]+)'/", $header, $m);

        return $m[1];
    }

    public function test_csp_enforced_with_nonce(): void
    {
        $header = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertNotNull($header);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $header);
        $this->assertStringContainsString("'unsafe-eval'", $header);
        $this->assertStringContainsString("frame-ancestors 'self'", $header);
        $this->assertStringContainsString("object-src 'none'", $header);
        $this->assertStringContainsString("form-action 'self'", $header);
        $this->assertStringContainsString('https://fonts.googleapis.com', $header);
    }

    public function test_rendered_scripts_carry_csp_nonce(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([[null, '/'], [null, '/stalls'], [$admin, '/admin/dashboard'], [$admin, '/admin/reports']] as [$user, $url]) {
            $response = $user ? $this->actingAs($user)->get($url) : $this->get($url);
            $nonce = $this->nonceFrom($response->headers->get('Content-Security-Policy'));

            preg_match_all('/<script\b[^>]*>/i', $response->getContent(), $tags);
            $this->assertNotEmpty($tags[0], $url);
            foreach ($tags[0] as $tag) {
                $this->assertStringContainsString('nonce="' . $nonce . '"', $tag, "{$url}: {$tag}");
            }
        }
    }

    public function test_nonce_is_stable_within_a_session_for_wire_navigate(): void
    {
        $first = $this->nonceFrom($this->get('/')->headers->get('Content-Security-Policy'));
        $second = $this->nonceFrom($this->get('/stalls')->headers->get('Content-Security-Policy'));

        $this->assertSame($first, $second);
    }

    public function test_report_only_mode(): void
    {
        config(['security.csp_mode' => 'report']);

        $response = $this->get('/');

        $response->assertHeaderMissing('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_off_mode(): void
    {
        config(['security.csp_mode' => 'off']);

        $this->get('/')
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_no_inline_event_handlers(): void
    {
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (preg_match_all('/\son(click|change|submit|input|load|keydown|keyup|focus|blur|error)\s*=/i', $file->getContents(), $m)) {
                $offenders[] = $file->getRelativePathname() . ' (' . count($m[0]) . ')';
            }
        }

        $this->assertSame([], $offenders);
    }
}
