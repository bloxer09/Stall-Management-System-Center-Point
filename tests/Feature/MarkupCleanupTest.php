<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MarkupCleanupTest extends TestCase
{
    public function test_no_tag_repeats_an_accessibility_or_title_attribute(): void
    {
        $problems = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            preg_match_all('/<[a-zA-Z][\w-]*(?:\{\{.*?\}\}|"[^"]*"|\'[^\']*\'|[^>])*>/s', $file->getContents(), $tags);
            foreach ($tags[0] as $tag) {
                preg_match_all('/\s((?:aria-[\w-]+|title|role))=/', $tag, $attrs);
                foreach (array_count_values($attrs[1]) as $name => $count) {
                    if ($count > 1) {
                        $problems[] = $file->getRelativePathname() . ": {$name} x{$count}";
                    }
                }
            }
        }

        $this->assertSame([], $problems);
    }

    public function test_renewal_modal_has_labelled_close_button_with_escape(): void
    {
        $source = file_get_contents(resource_path('views/livewire/partials/renewal-modal.blade.php'));

        $this->assertMatchesRegularExpression('/<button[^>]*wire:click="closeRenewal"[^>]*aria-label="Close"/', $source);
        $this->assertStringContainsString('keydown.escape.window', $source);
    }

    public function test_escape_only_closes_the_topmost_modal(): void
    {
        // Every Escape handler must check it belongs to the last open modal on the page.
        foreach (File::allFiles(resource_path('views/livewire')) as $file) {
            preg_match_all('/x-on:keydown\.escape\.window="([^"]*)"/', $file->getContents(), $handlers);
            foreach ($handlers[1] as $handler) {
                $this->assertStringContainsString('isTopModal($el)', $handler, $file->getRelativePathname() . ': Escape handler must only act on the topmost modal');
            }
        }
    }
}
