<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginThemeTest extends TestCase
{
    public function test_login_page_uses_cavite_state_university_branding(): void
    {
        $response = $this->get(route('login'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Sign in | Cavite State University', $html);
        $this->assertStringContainsString('content="Cavite State University leave system', $html);
        $this->assertStringContainsString('content="#052e0b"', $html);
        $this->assertStringContainsString('href="'.asset('assets/auth/cvsu-favicon.png').'"', $html);
        $this->assertStringContainsString('src="'.asset('assets/auth/cvsu-seal.webp').'"', $html);
        $this->assertStringContainsString('src="'.asset('assets/auth/cvsu-logo.webp').'"', $html);
        $this->assertStringContainsString('src="'.asset('assets/auth/cv-mail.svg').'"', $html);
        $this->assertStringContainsString('src="'.asset('assets/auth/cv-lock.svg').'"', $html);
        $this->assertStringContainsString('src="'.asset('assets/auth/cv-eye.svg').'"', $html);
        $this->assertStringContainsString('src="'.asset('assets/auth/cv-check.svg').'"', $html);
        $this->assertStringContainsString('Republic of the Philippines', $html);
        $this->assertStringContainsString('Established 1906', $html);
        $this->assertStringContainsString('Use your work email to continue.', $html);
        $this->assertStringContainsString('Keep me signed in on this device', $html);
    }

    public function test_login_page_keeps_every_form_and_script_hook(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        foreach ([
            'id="loginCard"',
            'id="loginForm"',
            'id="email"',
            'id="password"',
            'id="passwordToggle"',
            'id="remember"',
            'id="loginBtn"',
            'name="email"',
            'name="password"',
            'name="remember"',
            'value="1"',
            'class="login-btn-label"',
            'class="login-spinner"',
            'aria-pressed="false"',
        ] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }

        $this->assertStringContainsString('action="'.route('login').'"', $html);
        $this->assertStringContainsString('href="'.route('password.request').'"', $html);
        $this->assertStringContainsString('data-icon-on="'.asset('assets/auth/cv-eye.svg').'"', $html);
        $this->assertStringContainsString('data-icon-off="'.asset('assets/auth/cv-eye-off.svg').'"', $html);
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('<input type="hidden" name="_token"', $html);
        $this->assertStringContainsString('<script src="'.asset('js/login.js').'"></script>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_login_page_has_no_font_cdn_or_inline_styles(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('fonts.gstatic.com', $html);

        preg_match('/<body[^>]*>(.*)<\/body>/s', $html, $matches);
        $body = $matches[1] ?? $html;
        $this->assertStringNotContainsString('style=', $body);
    }

    public function test_login_css_and_brand_assets_are_present(): void
    {
        $css = file_get_contents(public_path('css/login.css'));

        foreach ([
            '--cv-dark: #052e0b',
            '--cv-green: #018002',
            '--cv-gold: #e0cb74',
            '--cv-page: #f3f6f2',
            'prefers-reduced-motion',
            'cv-shake',
            'cv-spin',
        ] as $token) {
            $this->assertStringContainsString($token, $css);
        }

        foreach ([
            'cvsu-logo.webp',
            'cvsu-seal.webp',
            'cvsu-pattern.svg',
            'cvsu-favicon.png',
            'cv-mail.svg',
            'cv-lock.svg',
            'cv-eye.svg',
            'cv-eye-off.svg',
            'cv-alert.svg',
            'cv-check.svg',
        ] as $asset) {
            $this->assertFileExists(public_path('assets/auth/'.$asset));
        }
    }
}
