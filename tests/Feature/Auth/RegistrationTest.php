<?php
namespace Tests\Feature\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Test User', 'username' => '12345', 'role' => 'pembina',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->where('canRegister', false));
    }
}
