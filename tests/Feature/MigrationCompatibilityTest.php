<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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
        $this->assertTrue($user->fresh()->isPanitia());
    }
}
