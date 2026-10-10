<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassAndStudentStatusTest extends TestCase {
    use RefreshDatabase;

    private User $pembina;
    private Student $student;

    protected function setUp(): void {
        parent::setUp(); $this->withoutVite();
        $this->pembina = $this->account('admin', 'pembina');
        $user = $this->account('2001', 'siswa');
        $this->student = Student::create(['user_id' => $user->id, 'full_name' => $user->name, 'student_number' => '2001', 'joined_year' => 2026, 'class_name' => 'XI RPL 1', 'status' => 'active']);
    }

    private function account(string $code, string $role): User {
        return User::create(['name' => 'User '.$code, 'username' => $code, 'role' => $role, 'password' => 'test-password']);
    }

    private function memberData(string $code, string $class): array {
        return ['student_number' => $code, 'full_name' => 'User '.$code, 'joined_year' => 2026, 'class_name' => $class];
    }

    public function test_forms_share_the_51_classes_with_requested_section_limits(): void {
        $this->actingAs($this->pembina)->get('/students/create')->assertInertia(fn ($page) => $page->component('Students/Create')
            ->has('classGroups', 22)->where('classGroups', fn ($groups) => collect($groups)->flatten()->count() === 51)
            ->where('classGroups.X PPLG.2', 'X PPLG 3')->where('classGroups.XI PPLG - RPL.2', 'XI PPLG - RPL 3')
            ->has('classGroups.XII PPLG - RPL', 2)->where('classGroups.X MPLB.4', 'X MPLB 5')
            ->where('classGroups.XI AKKUL - PB.0', 'XI AKKUL - PB 1')->has('classGroups.XI PS - BD', 1)
            ->where('classGroups.XII TJKT - TR.0', 'XII TJKT - TR 1')->missing('classGroups.X TJKT'));
        $this->actingAs($this->student->user)->get('/profile')->assertInertia(fn ($page) => $page->has('classGroups', 22)->where('classGroups.XII MPLB - ML.1', 'XII MPLB - ML 2'));
    }

    public function test_pembina_can_assign_classes_from_all_departments_and_rejects_unlisted_classes(): void {
        $this->actingAs($this->pembina);
        foreach (['X PPLG 3', 'XI PPLG - RPL 3', 'XII PPLG - RPL 2', 'X MPLB 5', 'XI MPLB - ML 2', 'XII MPLB - MP 3', 'X AKKUL 4', 'XI AKKUL - PB 1', 'XII AKKUL - AK 3', 'X PS 4', 'XI PS - BR 2', 'XII PS - BD 1', 'XI TJKT - TK 2', 'XII TJKT - TR 1'] as $index => $class) {
            $code = '30'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $this->post('/students', $this->memberData($code, $class))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('students', ['student_number' => $code, 'class_name' => $class]);
        }
        foreach (['XII PPLG - RPL 3', 'XI PS - BD 2', 'X TJKT 1', 'Kelas bebas', 'XI RPL 1'] as $class) {
            $this->post('/students', $this->memberData('invalid', $class))->assertSessionHasErrors('class_name');
        }
        $this->assertDatabaseMissing('students', ['student_number' => 'invalid']);
        $this->assertDatabaseMissing('users', ['username' => 'invalid']);
    }

    public function test_legacy_class_can_be_retained_during_edit_but_new_class_choices_are_validated(): void {
        $this->actingAs($this->pembina)->put('/students/'.$this->student->id, $this->memberData('2001', 'XI RPL 1'))->assertSessionHasNoErrors();
        $this->assertSame('XI RPL 1', $this->student->fresh()->class_name);
        $this->actingAs($this->student->user)->patch('/profile', ['class_name' => 'XI RPL 1', 'phone' => '08123'])->assertSessionHasNoErrors();
        $this->patch('/profile', ['class_name' => 'Kelas bebas'])->assertSessionHasErrors('class_name');
        $this->patch('/profile', ['class_name' => 'XI PPLG - RPL 2'])->assertSessionHasNoErrors();
        $this->assertSame('XI PPLG - RPL 2', $this->student->fresh()->class_name);
        $this->patch('/profile', ['class_name' => 'XI RPL 1'])->assertSessionHasErrors('class_name');
    }

    public function test_membership_class_dropdown_uses_same_validation_and_keeps_semester_snapshot_separate(): void {
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $membership = Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $year->id, 'class_name' => 'XI RPL 1', 'status' => 'active']);
        $this->actingAs($this->pembina)->put('/keanggotaan/'.$membership->id, ['status' => 'active', 'class_name' => 'XI RPL 1'])->assertSessionHasNoErrors();
        $this->put('/keanggotaan/'.$membership->id, ['status' => 'active', 'class_name' => 'XII TJKT - TR 2'])->assertSessionHasErrors('class_name');
        $this->put('/keanggotaan/'.$membership->id, ['status' => 'active', 'class_name' => 'XI PPLG - RPL 1'])->assertSessionHasNoErrors();
        $this->assertSame('XI PPLG - RPL 1', $membership->fresh()->class_name);
        $this->assertSame('XI RPL 1', $this->student->fresh()->class_name);
    }

    public function test_new_panitia_classes_are_supported_and_grade_x_remains_ineligible(): void {
        $this->actingAs($this->pembina)->post('/students', $this->memberData('3001', 'XI TJKT - TK 2'))->assertSessionHasNoErrors();
        $dates = ['starts_on' => now('Asia/Jakarta')->toDateString(), 'ends_on' => now('Asia/Jakarta')->addMonth()->toDateString()];
        $this->post('/panitia-ec', array_merge($dates, ['user_id' => User::where('username', '3001')->firstOrFail()->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => '3001', 'role' => 'panitia']);
        $this->post('/students', $this->memberData('3002', 'X MPLB 5'))->assertSessionHasNoErrors();
        $this->post('/panitia-ec', array_merge($dates, ['user_id' => User::where('username', '3002')->firstOrFail()->id]))->assertSessionHasErrors('user_id');
        $this->assertDatabaseHas('users', ['username' => '3002', 'role' => 'siswa']);
    }

    public function test_inline_status_and_graduation_preserve_identity_memberships_and_attendance(): void {
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $membership = Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $year->id, 'class_name' => 'XI RPL 1', 'status' => 'active']);
        $activity = Activity::create(['academic_year_id' => $year->id, 'title' => 'Latihan', 'category' => 'Percakapan', 'activity_date' => '2026-10-09', 'start_time' => '15:00', 'created_by' => $this->pembina->id]);
        $attendance = Attendance::create(['student_id' => $this->student->id, 'activity_id' => $activity->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $user = $this->student->user; $password = $user->password;
        $this->actingAs($this->pembina);
        foreach (['left', 'inactive', 'active', 'alumni'] as $status) {
            $this->patch('/students/'.$this->student->id.'/status', ['status' => $status, 'class_name' => 'X PPLG 1', 'role' => 'panitia', 'full_name' => 'Changed'])->assertSessionHasNoErrors();
            $this->assertSame($status, $this->student->fresh()->status);
        }
        $this->assertSame('XI RPL 1', $this->student->fresh()->class_name);
        $this->assertSame($user->name, $this->student->fresh()->full_name);
        $this->assertSame('siswa', $user->fresh()->role);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('active', $membership->fresh()->status);
        $this->assertSame('hadir', $attendance->fresh()->status);
        $nextActivity = $activity->replicate(); $nextActivity->title = 'Latihan berikutnya'; $nextActivity->save();
        $this->get('/kegiatan/'.$nextActivity->id)->assertInertia(fn ($page) => $page->has('students', 0));
        $this->get('/students?status=alumni')->assertInertia(fn ($page) => $page->has('students', 1)->where('students.0.status', 'alumni'));
    }

    public function test_status_endpoint_requires_pembina_and_rejects_invalid_status(): void {
        $url = '/students/'.$this->student->id.'/status';
        $this->patch($url, ['status' => 'alumni'])->assertRedirect('/login');
        $this->actingAs($this->student->user)->patch($url, ['status' => 'alumni'])->assertForbidden();
        $this->actingAs($this->account('1003', 'panitia'))->patch($url, ['status' => 'alumni'])->assertForbidden();
        $this->actingAs($this->pembina)->patch($url, ['status' => 'graduated'])->assertSessionHasErrors('status');
        $this->assertSame('active', $this->student->fresh()->status);
    }
}
