<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppThemeTest extends TestCase
{
    public function test_authenticated_ui_uses_the_cavite_state_university_palette(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        foreach ([
            '--cv-dark:#052e0b',
            '--cv-green:#018002',
            '--cv-gold:#e0cb74',
            '--lagoon:var(--cv-green)',
            '--sun:var(--cv-gold)',
            '--bg:var(--cv-page)',
            'rgba(1,128,2,',
        ] as $token) {
            $this->assertStringContainsString($token, $css);
        }

        foreach (['#0F8B8D', '#061A24', '#F6B93B', '#0A2533', '#B9C6CE'] as $old) {
            $this->assertStringNotContainsString($old, $css);
        }
    }

    public function test_shared_views_use_cvsu_colors_and_keep_ring_hooks(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $icons = file_get_contents(resource_path('views/partials/icons.blade.php'));
        $splash = file_get_contents(resource_path('views/partials/splash.blade.php'));
        $ring = file_get_contents(resource_path('views/partials/balance-ring.blade.php'));

        $this->assertStringContainsString('content="#f3f6f2"', $layout);
        $this->assertStringContainsString("href=\"{{ asset('assets/auth/cvsu-favicon.png') }}\"", $icons);
        $this->assertStringContainsString('linear-gradient(135deg, #052e0b, #0a4a12)', $splash);
        $this->assertStringContainsString('stroke="#e0cb74"', $splash);
        $this->assertStringContainsString('stroke="var(--lagoon)"', $ring);
        $this->assertStringContainsString('stroke="var(--sun)"', $ring);
        $this->assertStringContainsString('stroke="#b8c5ba"', $ring);
    }
}
