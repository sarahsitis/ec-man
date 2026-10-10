<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MigrationCompatibilityTest extends TestCase
{
    // SQLite table rebuilds must run outside a transaction so the schema
    // builder can suspend foreign keys instead of triggering cascading deletes.
    use DatabaseMigrations;

    public function test_role_extension_preserves_existing_accounts_and_member_data(): void
    {
        $migration = require database_path('migrations/2026_10_08_070000_add_panitia_role_to_users_table.php');
        $migration->down();
        $user = User::factory()->create();
        $student = Student::create([
            'user_id' => $user->id, 'student_number' => $user->username,
            'full_name' => $user->name, 'joined_year' => 2026,
            'class_name' => 'XI RPL 1', 'phone' => '08123456789',
        ]);
        $hash = $user->password;

        $migration->up();

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('siswa', $user->fresh()->role);
        $this->assertSame($user->id, $student->fresh()->user_id);
        $this->assertSame('08123456789', $student->fresh()->phone);
        $user->update(['role' => 'panitia']);
        $this->assertFalse($user->fresh()->isPanitia());
        $user->committeeRoles()->create(['starts_on' => now('Asia/Jakarta')->toDateString(), 'ends_on' => now('Asia/Jakarta')->addMonth()->toDateString()]);
        $this->assertTrue($user->fresh()->isPanitia());
    }

    public static function legacySemesters(): array
    {
        return [['2026-10-09', '2026-12-31', '2027-01-01'], ['2026-03-31', '2026-06-30', '2026-07-01']];
    }

    #[DataProvider('legacySemesters')]
    public function test_existing_panitia_gets_bounded_appointment_without_changing_identity(string $today, string $end, string $expired): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse($today.' 12:00:00', 'Asia/Jakarta'));
        $migration = require database_path('migrations/2026_10_09_120000_create_committee_roles_table.php');
        $migration->down();
        $user = User::factory()->create(['role' => 'panitia']);
        $student = Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'joined_year' => 2026, 'class_name' => 'XI RPL 1', 'status' => 'active']);
        $password = $user->password;
        $migration->up();
        $this->assertDatabaseHas('committee_roles', ['user_id' => $user->id, 'starts_on' => $today, 'ends_on' => $end, 'appointed_by' => null]);
        $this->assertTrue($user->fresh()->isPanitia());
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame($user->id, $student->fresh()->user_id);
        $this->travelTo(\Illuminate\Support\Carbon::parse($expired.' 00:00:00', 'Asia/Jakarta'));
        $this->assertFalse($user->fresh()->isPanitia());
    }
}
