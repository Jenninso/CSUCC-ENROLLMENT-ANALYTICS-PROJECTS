<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_guests_can_view_about_page(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('About this system')
            ->assertSee('Home')
            ->assertSee('Terms')
            ->assertSee('CEIT use only')
            ->assertDontSee('BSIT use only');
    }

    public function test_guests_can_view_terms_page(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms and Conditions')
            ->assertSee('Acceptance of terms')
            ->assertSee('Sign in');
    }

    public function test_guests_can_view_home_page_without_login_form(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Enrollment Analytics')
            ->assertSee('What this system provides')
            ->assertSee('How to get access')
            ->assertSee('Read the terms')
            ->assertDontSee('Email Address')
            ->assertDontSee('Need an account?');
    }

    public function test_login_page_keeps_footer_without_public_links(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Open menu')
            ->assertSee('CSUCC Cabadbaran Campus')
            ->assertDontSee('CEIT use only')
            ->assertDontSee('BSIT use only')
            ->assertDontSee('>Home<')
            ->assertDontSee('>About<')
            ->assertDontSee('>Terms<');
    }
}
