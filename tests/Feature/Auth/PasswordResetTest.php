<?php
namespace Tests\Feature\Auth;
use Tests\TestCase;
class PasswordResetTest extends TestCase
{
    public function test_unsupported_email_password_reset_routes_are_disabled(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'student@example.com'])->assertNotFound();
        $this->get('/reset-password/test-token')->assertNotFound();
        $this->post('/reset-password', ['token' => 'test-token'])->assertNotFound();
        $this->get('/login')->assertInertia(fn ($page) => $page->where('canResetPassword', false));
    }
}
