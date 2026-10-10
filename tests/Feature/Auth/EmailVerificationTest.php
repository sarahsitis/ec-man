<?php
namespace Tests\Feature\Auth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    public function test_username_accounts_do_not_expose_email_verification_routes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/verify-email')->assertNotFound();
        $this->get('/verify-email/1/test-hash')->assertNotFound();
        $this->post('/email/verification-notification')->assertNotFound();
        $this->get('/dashboard')->assertOk();
    }
}
