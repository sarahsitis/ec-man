<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_default_language_is_indonesian(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Welcome')->where('locale', 'id'));
    }

    public function test_guest_can_change_language_and_keep_it_on_navigation(): void
    {
        $this->from('/login')->post('/language', ['locale' => 'en'])
            ->assertStatus(303)->assertRedirect('/login')->assertSessionHas('locale', 'en')->assertCookie('ec_locale', 'en');
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_cookie_restores_language_in_a_new_session(): void
    {
        $this->withCookie('ec_locale', 'en')->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_unsupported_language_does_not_change_preference(): void
    {
        $this->withSession(['locale' => 'id'])->from('/')->post('/language', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale')->assertSessionHas('locale', 'id');
        $this->withCookie('ec_locale', 'fr')->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'id'));
    }

    public function test_changing_back_to_indonesian_is_supported(): void
    {
        $this->withSession(['locale' => 'en'])->from('/')->post('/language', ['locale' => 'id'])->assertSessionHas('locale', 'id');
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'id'));
    }
}
