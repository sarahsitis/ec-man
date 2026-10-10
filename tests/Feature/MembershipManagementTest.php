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

class MembershipManagementTest extends TestCase {
    use RefreshDatabase;

    private User $pembina;
    private User $panitia;
    private Student $student;
    private AcademicYear $year;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutVite();
        $this->pembina = $this->account('admin', 'pembina');
        $this->panitia = $this->account('1003', 'panitia');
        $this->student = $this->member('2001');
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
    }

    private function account(string $code, string $role): User {
        $user = User::create(['name' => 'User '.$code, 'username' => $code, 'role' => $role, 'password' => 'test-password']);
        if ($role === 'panitia') {
            $user->committeeRoles()->create(['starts_on' => now('Asia/Jakarta')->toDateString(), 'ends_on' => now('Asia/Jakarta')->addYear()->toDateString()]);
        }
        return $user;
    }

    private function member(string $code, string $status = 'active'): Student {
        $user = $this->account($code, 'siswa');
        return Student::create(['user_id' => $user->id, 'student_number' => $code, 'full_name' => $user->name, 'joined_year' => 2026, 'class_name' => 'X RPL 1', 'status' => $status]);
    }

    private function enrollment(array $students, string $status = 'active'): array {
        return ['academic_year_id' => $this->year->id, 'student_ids' => $students, 'status' => $status];
    }

    public function test_pembina_can_create_semesters_and_switch_active_semester_without_losing_memberships(): void {
        $membership = Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'class_name' => 'X RPL 1', 'status' => 'active']);
        $this->actingAs($this->pembina)->post('/tahun-ajaran', ['name' => '2026/2027', 'semester' => 'genap', 'is_active' => true])->assertSessionHasNoErrors();
        $next = AcademicYear::where('semester', 'genap')->firstOrFail();
        $this->assertFalse($this->year->fresh()->is_active);
        $this->assertTrue($next->is_active);
        $this->assertDatabaseHas('memberships', ['id' => $membership->id, 'academic_year_id' => $this->year->id]);
        $this->post('/tahun-ajaran/'.$this->year->id.'/aktifkan')->assertSessionHasNoErrors();
        $this->assertTrue($this->year->fresh()->is_active);
        $this->assertFalse($next->fresh()->is_active);
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
        $this->post('/tahun-ajaran', ['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => false])->assertSessionHasErrors('name');
        $this->post('/tahun-ajaran', ['name' => '2026/2029', 'semester' => 'genap', 'is_active' => false])->assertSessionHasErrors('name');
        $this->post('/tahun-ajaran', ['name' => ['bad'], 'semester' => 'genap', 'is_active' => false])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('academic_years', 2);
    }

    public function test_pembina_can_bulk_enroll_edit_and_delete_memberships_while_preserving_attendance(): void {
        $second = $this->member('2002');
        $this->actingAs($this->pembina)->post('/keanggotaan', $this->enrollment([$this->student->id, $second->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('memberships', 2);
        $this->assertDatabaseHas('memberships', ['student_id' => $this->student->id, 'class_name' => 'X RPL 1', 'status' => 'active']);
        $membership = $this->student->memberships()->firstOrFail();
        foreach (['inactive', 'left', 'alumni', 'active'] as $status) {
            $this->put('/keanggotaan/'.$membership->id, ['status' => $status, 'class_name' => 'XI PPLG - RPL 1'])->assertSessionHasNoErrors();
            $this->assertSame($status, $membership->fresh()->status);
        }
        $this->assertSame('X RPL 1', $this->student->fresh()->class_name);
        $activity = Activity::create(['academic_year_id' => $this->year->id, 'title' => 'Latihan', 'category' => 'Percakapan', 'activity_date' => '2026-10-09', 'start_time' => '15:00', 'created_by' => $this->pembina->id]);
        $attendance = Attendance::create(['activity_id' => $activity->id, 'student_id' => $this->student->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $this->delete('/keanggotaan/'.$membership->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('memberships', ['id' => $membership->id]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'hadir']);
        $this->assertNotNull($this->student->fresh());
        $this->post('/keanggotaan', $this->enrollment([$this->student->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('memberships', 2);
    }

    public function test_invalid_bulk_enrollment_has_no_partial_writes(): void {
        $second = $this->member('2002');
        $inactive = $this->member('2003', 'inactive');
        $this->actingAs($this->pembina)->post('/keanggotaan', $this->enrollment([$this->student->id, $inactive->id]))->assertSessionHasErrors('student_ids');
        $this->assertDatabaseCount('memberships', 0);
        $this->post('/keanggotaan', $this->enrollment([$this->student->id, 99999]))->assertSessionHasErrors('student_ids.1');
        $this->post('/keanggotaan', $this->enrollment([$this->student->id, $this->student->id]))->assertSessionHasErrors('student_ids.1');
        $this->post('/keanggotaan', $this->enrollment([$this->student->id]))->assertSessionHasNoErrors();
        $this->post('/keanggotaan', $this->enrollment([$second->id, $this->student->id]))->assertSessionHasErrors('student_ids');
        $this->assertDatabaseCount('memberships', 1);
        $this->assertDatabaseMissing('memberships', ['student_id' => $second->id]);
        $this->post('/keanggotaan', $this->enrollment([$inactive->id], 'inactive'))->assertSessionHasNoErrors();
        $membership = $inactive->memberships()->firstOrFail();
        $this->put('/keanggotaan/'.$membership->id, ['status' => 'active'])->assertSessionHasErrors('status');
        $this->assertSame('inactive', $membership->fresh()->status);
    }

    public function test_memberships_are_isolated_by_semester_and_filterable(): void {
        $this->actingAs($this->pembina)->post('/keanggotaan', $this->enrollment([$this->student->id]))->assertSessionHasNoErrors();
        $next = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $this->post('/keanggotaan', ['academic_year_id' => $next->id, 'student_ids' => [$this->student->id], 'status' => 'alumni'])->assertSessionHasNoErrors();
        $this->get('/keanggotaan')->assertInertia(fn ($page) => $page->component('Memberships/Index')->has('memberships.data', 1)->where('memberships.data.0.academic_year_id', $this->year->id)->has('students', 0));
        $this->get('/keanggotaan?academic_year_id='.$next->id.'&status=alumni&search=2001')->assertInertia(fn ($page) => $page->has('memberships.data', 1)->where('memberships.data.0.status', 'alumni'));
        $this->get('/keanggotaan?academic_year_id='.$next->id.'&status=active')->assertInertia(fn ($page) => $page->has('memberships.data', 0));
        $this->assertDatabaseCount('memberships', 2);
    }

    public function test_pembina_controls_all_student_statuses_and_bulk_updates_do_not_change_historical_memberships(): void {
        $second = $this->member('2002');
        $membership = Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'status' => 'active']);
        $this->actingAs($this->pembina)->post('/students', ['student_number' => '2003', 'full_name' => 'Alumni EC', 'joined_year' => 2026, 'status' => 'alumni'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', ['student_number' => '2003', 'status' => 'alumni']);
        foreach (['inactive', 'left', 'alumni', 'active'] as $status) {
            $this->post('/students/status-massal', ['student_ids' => [$this->student->id, $second->id], 'status' => $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $this->student->fresh()->status);
            $this->assertSame($status, $second->fresh()->status);
        }
        $this->assertSame('active', $membership->fresh()->status);
        $this->post('/students/status-massal', ['student_ids' => [$this->student->id, 99999], 'status' => 'left'])->assertSessionHasErrors('student_ids.1');
        $this->assertSame('active', $this->student->fresh()->status);
        $this->post('/students/status-massal', ['student_ids' => [$this->student->id], 'status' => 'invalid'])->assertSessionHasErrors('status');
        $this->get('/students?status=alumni&search=Alumni')->assertInertia(fn ($page) => $page->has('students', 1)->where('students.0.student_number', '2003'));
        $this->actingAs($this->student->user)->patch('/profile', ['status' => 'active'])->assertSessionHasErrors('status');
    }

    public function test_pembina_can_delete_unused_student_but_cannot_erase_membership_history(): void {
        $unused = $this->member('2002'); $userId = $unused->user_id;
        $this->actingAs($this->pembina)->delete('/students/'.$unused->id)->assertSessionHasNoErrors()->assertRedirect('/students');
        $this->assertDatabaseMissing('students', ['id' => $unused->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->post('/keanggotaan', $this->enrollment([$this->student->id]))->assertSessionHasNoErrors();
        $this->delete('/students/'.$this->student->id)->assertSessionHasErrors('student');
        $this->assertNotNull($this->student->fresh());
        $this->assertDatabaseCount('memberships', 1);
        $this->actingAs($this->student->user)->delete('/profile', ['password' => 'test-password'])->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($this->student->user);
    }

    public function test_panitia_and_students_cannot_manage_memberships_years_or_student_status(): void {
        Student::create(['user_id' => $this->panitia->id, 'student_number' => '1003', 'full_name' => $this->panitia->name, 'joined_year' => 2026, 'class_name' => 'XI PPLG - RPL 1', 'status' => 'active']);
        $membership = Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'status' => 'active']);
        foreach ([$this->panitia, $this->student->user] as $actor) {
            $this->actingAs($actor)->get('/keanggotaan')->assertForbidden();
            $this->post('/keanggotaan', $this->enrollment([$this->student->id]))->assertForbidden();
            $this->put('/keanggotaan/'.$membership->id, ['status' => 'left'])->assertForbidden();
            $this->delete('/keanggotaan/'.$membership->id)->assertForbidden();
            $this->get('/tahun-ajaran')->assertForbidden();
            $this->post('/tahun-ajaran', ['name' => '2026/2027', 'semester' => 'genap', 'is_active' => true])->assertForbidden();
            $this->post('/tahun-ajaran/'.$this->year->id.'/aktifkan')->assertForbidden();
            $this->post('/students/status-massal', ['student_ids' => [$this->student->id], 'status' => 'left'])->assertForbidden();
            $this->delete('/students/'.$this->student->id)->assertForbidden();
        }
        $this->assertSame('active', $this->student->fresh()->status);
        $this->assertNotNull($membership->fresh());
    }
}
