<?php
namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanitiaRoleTest extends TestCase
{
    use RefreshDatabase;
    private function account(string $code, string $role): User {
        return User::create(['name' => 'Pengguna Uji', 'username' => $code, 'role' => $role, 'password' => 'test-password']);
    }
    private function member(User $user): Student {
        return Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'joined_year' => 2026, 'class_name' => 'XI RPL 1']);
    }
    private function data(Student $student): array {
        return ['student_number' => $student->student_number, 'full_name' => $student->full_name, 'joined_year' => 2026, 'class_name' => 'XI RPL 1'];
    }
    public function test_pembina_can_assign_and_revoke_panitia_role(): void {
        $admin = $this->account('admin', 'pembina');
        $user = $this->account('1003', 'siswa');
        $student = $this->member($user);
        $hash = $user->password;
        $this->actingAs($admin)->put('/students/'.$student->id, array_merge($this->data($student), ['role' => 'panitia']))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->isPanitia());
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame($user->id, $student->fresh()->user_id);
        $this->put('/students/'.$student->id, array_merge($this->data($student), ['role' => 'siswa']))->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->isPanitia());
    }
    public function test_panitia_cannot_access_member_management(): void {
        $user = $this->account('1003', 'panitia');
        $student = $this->member($user);
        $this->actingAs($user)->get('/students')->assertForbidden();
        $this->get('/students/'.$student->id.'/edit')->assertForbidden();
        $this->put('/students/'.$student->id, array_merge($this->data($student), ['role' => 'siswa']))->assertForbidden();
    }
    public function test_students_cannot_assign_their_own_role(): void {
        $user = $this->account('1003', 'siswa');
        $this->member($user);
        $this->actingAs($user)->patch('/profile', ['role' => 'panitia'])->assertSessionHasErrors('role');
        $this->assertSame('siswa', $user->fresh()->role);
    }
    public function test_panitia_keeps_contact_access_but_cannot_change_identity_or_class(): void {
        $user = $this->account('1003', 'panitia');
        $student = $this->member($user);
        $this->actingAs($user)->patch('/profile', ['phone' => '08123'])->assertSessionHasNoErrors();
        $this->assertSame('08123', $student->fresh()->phone);
        $this->patch('/profile', ['name' => 'Changed', 'username' => 'different', 'class_name' => 'X RPL 1'])->assertSessionHasErrors(['name', 'username', 'class_name']);
        $this->assertSame('1003', $user->fresh()->username);
        $this->assertSame('XI RPL 1', $student->fresh()->class_name);
    }
    public function test_pembina_cannot_assign_panitia_to_class_x_or_promote_member_to_pembina(): void {
        $admin = $this->account('admin', 'pembina');
        $user = $this->account('1003', 'siswa');
        $student = $this->member($user);
        $this->actingAs($admin)->put('/students/'.$student->id, array_merge($this->data($student), ['role' => 'panitia', 'class_name' => 'X RPL 1']))->assertSessionHasErrors('class_name');
        $this->put('/students/'.$student->id, array_merge($this->data($student), ['role' => 'pembina']))->assertSessionHasErrors('role');
        $this->assertSame('siswa', $user->fresh()->role);
    }
    public function test_dashboard_displays_panitia_role(): void {
        $user = $this->account('1003', 'panitia');
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard')->where('auth.user.role', 'panitia'));
    }
}
