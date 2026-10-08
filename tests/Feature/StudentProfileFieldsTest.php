<?php
namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentProfileFieldsTest extends TestCase
{
    use RefreshDatabase;
    private function member(string $code): Student {
        $user = User::create(['name' => 'Nama Tetap', 'username' => $code, 'role' => 'siswa', 'password' => 'test-password']);
        return Student::create(['user_id' => $user->id, 'student_number' => $code, 'full_name' => $user->name, 'joined_year' => 2026]);
    }
    public function test_student_updates_contact_fields_without_modifying_identity(): void {
        $student = $this->member('1001');
        $this->actingAs($student->user)->patch('/profile', [
            'phone' => '081234567890', 'class_name' => 'XI RPL 1',
            'email' => 'siswa@example.com', 'address' => 'Cianjur',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');
        $student->refresh();
        $this->assertSame('081234567890', $student->phone);
        $this->assertSame('XI RPL 1', $student->class_name);
        $this->assertSame('siswa@example.com', $student->email);
        $this->assertSame('Cianjur', $student->address);
        $this->assertSame('1001', $student->student_number);
        $this->assertSame('Nama Tetap', $student->full_name);
        $this->assertSame('siswa', $student->user->role);
    }
    public function test_photo_upload_is_private_and_replaces_old_file(): void {
        Storage::fake('local');
        $student = $this->member('1001');
        Storage::disk('local')->put('profile-photos/old.png', 'old');
        $student->update(['profile_photo_path' => 'profile-photos/old.png']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->actingAs($student->user)->post('/profile', [
            '_method' => 'PATCH', 'profile_photo' => UploadedFile::fake()->createWithContent('avatar.png', $png),
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');
        $student->refresh();
        Storage::disk('local')->assertExists($student->profile_photo_path);
        Storage::disk('local')->assertMissing('profile-photos/old.png');
        $this->assertArrayNotHasKey('profile_photo_path', $student->toArray());
        $url = route('students.photo', $student);
        $this->get($url)->assertOk();
        $other = $this->member('1002');
        $this->actingAs($other->user)->get($url)->assertForbidden();
        $admin = User::create(['name' => 'Pembina', 'username' => 'admin', 'role' => 'pembina', 'password' => 'test-password']);
        $this->actingAs($admin)->get($url)->assertOk();
    }
    public function test_invalid_photo_and_contact_are_rejected(): void {
        Storage::fake('local');
        $student = $this->member('1001');
        $this->actingAs($student->user)->post('/profile', [
            '_method' => 'PATCH', 'profile_photo' => UploadedFile::fake()->create('script.php', 1, 'text/plain'),
            'email' => 'not-an-email', 'phone' => 'abc',
        ])->assertSessionHasErrors(['profile_photo', 'email', 'phone']);
        $this->assertNull($student->fresh()->profile_photo_path);
        $this->assertNull($student->fresh()->phone);
    }
    public function test_oversized_photo_is_rejected(): void {
        $student = $this->member('1001');
        $this->actingAs($student->user)->post('/profile', [
            '_method' => 'PATCH', 'profile_photo' => UploadedFile::fake()->create('large.jpg', 2049, 'image/jpeg'),
        ])->assertSessionHasErrors('profile_photo');
    }
    public function test_pembina_updates_all_member_fields_and_preserves_password(): void {
        $student = $this->member('1001');
        $hash = $student->user->password;
        $admin = User::create(['name' => 'Pembina', 'username' => 'admin', 'role' => 'pembina', 'password' => 'test-password']);
        $this->actingAs($admin)->put('/students/'.$student->id, [
            'student_number' => '1003', 'full_name' => 'Nama Koreksi', 'joined_year' => 2026,
            'class_name' => 'XII RPL 1', 'phone' => '+6281234', 'email' => 'koreksi@example.com', 'address' => 'Alamat Baru',
        ])->assertSessionHasNoErrors()->assertRedirect('/students');
        $student->refresh();
        $this->assertSame('1003', $student->user->username);
        $this->assertSame('Nama Koreksi', $student->full_name);
        $this->assertSame('XII RPL 1', $student->class_name);
        $this->assertSame($hash, $student->user->password);
    }
    public function test_existing_accounts_without_member_profile_can_save_contacts(): void {
        $user = User::create(['name' => 'Siswa Lama', 'username' => '2001', 'role' => 'siswa', 'password' => 'test-password']);
        $this->actingAs($user)->patch('/profile', ['phone' => '08123'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', ['user_id' => $user->id, 'student_number' => '2001', 'full_name' => 'Siswa Lama', 'phone' => '08123']);
    }
}
