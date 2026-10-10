<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $username, string $role = 'siswa'): User
    {
        return User::create([
            'name' => 'Pengguna Uji', 'username' => $username,
            'role' => $role, 'password' => Hash::make('test-password'),
        ]);
    }

    private function member(User $user): Student
    {
        return Student::create([
            'user_id' => $user->id, 'student_number' => $user->username,
            'full_name' => $user->name, 'joined_year' => 2026,
        ]);
    }

    public function test_profile_update_synchronizes_member_without_email(): void
    {
        $user = $this->account('1001');
        $student = $this->member($user);
        $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru', 'username' => '1003',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertSame('1003', $user->fresh()->username);
        $this->assertSame('Nama Baru', $student->fresh()->full_name);
        $this->assertSame('1003', $student->fresh()->student_number);
    }

    public function test_duplicate_profile_username_is_rejected(): void
    {
        $user = $this->account('1001');
        $this->account('1002');
        $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru', 'username' => '1002',
        ])->assertSessionHasErrors('username');
        $this->assertSame('1001', $user->fresh()->username);
    }

    public function test_pembina_can_edit_member_without_changing_password_or_role(): void
    {
        $admin = $this->account('admin', 'pembina');
        $user = $this->account('1001');
        $student = $this->member($user);
        $hash = $user->password;
        $this->actingAs($admin)->get('/students/'.$student->id.'/edit')->assertOk();
        $this->put('/students/'.$student->id, [
            'full_name' => 'Nama Baru', 'student_number' => '1003',
            'joined_year' => 2025, 'role' => 'pembina',
        ])->assertSessionHasNoErrors()->assertRedirect('/students');
        $this->assertSame('1003', $user->fresh()->username);
        $this->assertSame('siswa', $user->fresh()->role);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('Nama Baru', $student->fresh()->full_name);
    }

    public function test_siswa_cannot_edit_other_members(): void
    {
        $user = $this->account('1001');
        $other = $this->member($this->account('1002'));
        $this->actingAs($user)->get('/students/'.$other->id.'/edit')->assertForbidden();
        $this->put('/students/'.$other->id, [
            'full_name' => 'Changed', 'student_number' => '1003', 'joined_year' => 2026,
        ])->assertForbidden();
        $this->assertSame('1002', $other->fresh()->student_number);
    }

    public function test_existing_account_username_cannot_be_reused_for_member(): void
    {
        $admin = $this->account('admin', 'pembina');
        $this->account('reserved');
        $this->actingAs($admin)->post('/students', [
            'student_number' => 'reserved', 'full_name' => 'Nama Uji', 'joined_year' => 2026,
        ])->assertSessionHasErrors('student_number');
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_member_update_rejects_duplicate_username_without_partial_changes(): void
    {
        $admin = $this->account('admin', 'pembina');
        $user = $this->account('1001');
        $student = $this->member($user);
        $this->account('reserved');
        $this->actingAs($admin)->put('/students/'.$student->id, [
            'student_number' => 'reserved', 'full_name' => 'Changed', 'joined_year' => 2026,
        ])->assertSessionHasErrors('student_number');
        $this->assertSame('1001', $student->fresh()->student_number);
        $this->assertSame('1001', $user->fresh()->username);
    }
}
