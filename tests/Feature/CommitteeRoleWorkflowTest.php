<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\AssessmentAssignment;
use App\Models\CommitteeRole;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommitteeRoleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;
    private User $user;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00', 'Asia/Jakarta'));
        $this->pembina = User::factory()->create(['role' => 'pembina']);
        $this->user = User::factory()->create(['role' => 'siswa']);
        $this->student = Student::create([
            'user_id' => $this->user->id, 'student_number' => $this->user->username,
            'full_name' => $this->user->name, 'joined_year' => 2026, 'class_name' => 'XI PPLG - RPL 1', 'status' => 'active',
        ]);
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['user_id' => $this->user->id, 'starts_on' => '2026-10-09', 'ends_on' => '2026-12-31', 'note' => 'Asisten penilai dan presensi'], $overrides);
    }

    private function appoint(array $overrides = []): CommitteeRole
    {
        $this->actingAs($this->pembina)->post('/panitia-ec', $this->data($overrides))->assertSessionHasNoErrors();
        return $this->user->committeeRoles()->latest('id')->firstOrFail();
    }

    public function test_pembina_can_appoint_edit_and_revoke_without_losing_student_identity(): void
    {
        $password = $this->user->password;
        $role = $this->appoint(['appointed_by' => $this->user->id, 'revoked_at' => now()]);
        $this->assertTrue($this->user->fresh()->isPanitia());
        $this->assertSame($password, $this->user->fresh()->password);
        $this->assertSame($this->pembina->id, $role->appointed_by);
        $this->assertNull($role->revoked_at);
        $this->get('/panitia-ec')->assertOk()->assertInertia(fn ($page) => $page
            ->component('CommitteeRoles/Index')->has('appointments', 1)->where('appointments.0.status', 'active')
            ->where('appointments.0.appointed_by.name', $this->pembina->name));
        $this->put('/panitia-ec/'.$role->id, $this->data(['user_id' => $this->pembina->id, 'ends_on' => '2027-06-30']))->assertSessionHasNoErrors();
        $this->assertSame($this->user->id, $role->fresh()->user_id);
        $this->assertSame('2027-06-30', $role->fresh()->ends_on->toDateString());
        $this->delete('/panitia-ec/'.$role->id, ['revoke_reason' => 'Penggantian panitia'])->assertSessionHasNoErrors();
        $this->assertFalse($this->user->fresh()->isPanitia());
        $this->assertSame('siswa', $this->user->fresh()->role);
        $this->assertSame($this->pembina->id, $role->fresh()->revoked_by);
        $this->assertSame('Penggantian panitia', $role->fresh()->revoke_reason);
        $this->assertDatabaseCount('committee_roles', 1);
        $this->assertNotNull($this->student->fresh());
        $revokedAt = $role->fresh()->revoked_at;
        $this->delete('/panitia-ec/'.$role->id)->assertSessionHasNoErrors();
        $this->assertTrue($revokedAt->equalTo($role->fresh()->revoked_at));
    }

    public function test_only_pembina_can_manage_appointments_and_guests_must_login(): void
    {
        $this->get('/panitia-ec')->assertRedirect('/login');
        $this->post('/panitia-ec', $this->data())->assertRedirect('/login');
        $role = $this->appoint();
        foreach ([$this->user->fresh(), User::factory()->create(['role' => 'siswa'])] as $actor) {
            $this->actingAs($actor)->get('/panitia-ec')->assertForbidden();
            $this->post('/panitia-ec', $this->data())->assertForbidden();
            $this->put('/panitia-ec/'.$role->id, $this->data())->assertForbidden();
            $this->delete('/panitia-ec/'.$role->id)->assertForbidden();
        }
        $this->assertNull($role->fresh()->revoked_at);
    }

    public function test_date_ranges_are_required_ordered_and_not_already_expired(): void
    {
        $this->actingAs($this->pembina);
        foreach ([
            ['starts_on' => '', 'ends_on' => ''], ['starts_on' => 'invalid'],
            ['starts_on' => '2026-12-31', 'ends_on' => '2026-10-09'], ['ends_on' => '2026-10-08'],
        ] as $invalid) {
            $this->post('/panitia-ec', $this->data($invalid))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('committee_roles', 0);
        $this->assertSame('siswa', $this->user->fresh()->role);
    }

    public function test_candidates_exclude_pembina_grade_x_nonactive_and_missing_profiles(): void
    {
        $this->actingAs($this->pembina)->get('/panitia-ec')->assertInertia(fn ($page) => $page->has('candidates', 1)->where('candidates.0.user_id', $this->user->id));
        foreach (['inactive', 'left', 'alumni'] as $status) {
            $this->student->update(['status' => $status]);
            $this->post('/panitia-ec', $this->data())->assertSessionHasErrors('user_id');
            $this->get('/panitia-ec')->assertInertia(fn ($page) => $page->has('candidates', 0));
        }
        $this->student->update(['status' => 'active', 'class_name' => 'X PPLG 1']);
        $this->post('/panitia-ec', $this->data())->assertSessionHasErrors('user_id');
        foreach ([$this->pembina, User::factory()->create()] as $ineligible) {
            $this->post('/panitia-ec', $this->data(['user_id' => $ineligible->id]))->assertSessionHasErrors('user_id');
        }
        $this->assertDatabaseCount('committee_roles', 0);
    }

    public function test_overlapping_periods_are_rejected_but_nonoverlapping_future_periods_are_allowed(): void
    {
        $role = $this->appoint(['ends_on' => '2026-10-31']);
        $this->post('/panitia-ec', $this->data(['starts_on' => '2026-10-31']))->assertSessionHasErrors('starts_on');
        $future = $this->appoint(['starts_on' => '2026-11-01']);
        $this->put('/panitia-ec/'.$future->id, $this->data(['starts_on' => '2026-10-31']))->assertSessionHasErrors('starts_on');
        $this->assertSame('2026-11-01', $future->fresh()->starts_on->toDateString());
        $this->delete('/panitia-ec/'.$future->id)->assertSessionHasNoErrors();
        $this->assertTrue($this->user->fresh()->isPanitia());
        $this->assertNull($role->fresh()->revoked_at);
        $this->assertDatabaseCount('committee_roles', 2);
    }

    public function test_revoked_appointment_cannot_be_restored_by_edit_but_new_appointment_can_be_created(): void
    {
        $role = $this->appoint();
        $this->delete('/panitia-ec/'.$role->id)->assertSessionHasNoErrors();
        $this->put('/panitia-ec/'.$role->id, $this->data())->assertSessionHasErrors('starts_on');
        $this->assertFalse($this->user->fresh()->isPanitia());
        $new = $this->appoint();
        $this->assertNotSame($role->id, $new->id);
        $this->assertTrue($this->user->fresh()->isPanitia());
        $this->assertNotNull($role->fresh()->revoked_at);
    }

    public function test_access_starts_and_expires_at_jakarta_midnight_inclusively(): void
    {
        $role = $this->appoint(['starts_on' => '2026-10-10', 'ends_on' => '2026-10-10']);
        $this->actingAs($this->user->fresh())->get('/panitia/dashboard')->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page->where('auth.user.is_panitia', false));
        $this->travelTo(Carbon::parse('2026-10-09 17:00:00', 'UTC'));
        $this->get('/panitia/dashboard')->assertOk()->assertInertia(fn ($page) => $page
            ->where('auth.user.is_panitia', true)->where('committeeTerm.ends_on', '2026-10-10'));
        $this->travelTo(Carbon::parse('2026-10-10 16:59:59', 'UTC'));
        $this->get('/panitia/penugasan')->assertOk();
        $this->travelTo(Carbon::parse('2026-10-10 17:00:00', 'UTC'));
        $this->get('/panitia/penugasan')->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page->where('auth.user.is_panitia', false));
        $this->assertNull($role->fresh()->revoked_at);
    }

    public function test_nominal_panitia_role_without_an_appointment_has_no_privileges(): void
    {
        $this->user->update(['role' => 'panitia']);
        $this->assertFalse($this->user->fresh()->isPanitia());
        $this->actingAs($this->user->fresh())->get('/panitia/dashboard')->assertForbidden();
        $this->get('/panitia/penugasan')->assertForbidden();
        $this->actingAs($this->pembina)->get('/penilaian')->assertInertia(fn ($page) => $page->has('assessors', 0));
    }

    public static function suspendedStatuses(): array
    {
        return [['inactive'], ['left'], ['alumni']];
    }

    #[DataProvider('suspendedStatuses')]
    public function test_student_status_suspends_committee_access_and_menu(string $status): void
    {
        $role = $this->appoint();
        $this->patch('/students/'.$this->student->id.'/status', ['status' => $status])->assertSessionHasNoErrors();
        $this->actingAs($this->user->fresh())->get('/panitia/dashboard')->assertForbidden();
        $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('auth.user.is_panitia', false));
        $this->actingAs($this->pembina)->get('/panitia-ec')->assertInertia(fn ($page) => $page->where('appointments.0.status', 'suspended'));
        $this->get('/penilaian')->assertInertia(fn ($page) => $page->has('assessors', 0));
        $this->assertNull($role->fresh()->revoked_at);
    }

    public function test_pending_appointments_lock_self_service_class_changes_and_cannot_be_granted_from_member_form(): void
    {
        $this->appoint(['starts_on' => '2026-11-01']);
        $this->actingAs($this->user->fresh())->patch('/profile', ['class_name' => 'XII PPLG - RPL 1'])->assertSessionHasErrors('class_name');
        $this->get('/profile')->assertInertia(fn ($page) => $page->where('auth.user.committee_class_locked', true)->where('auth.user.is_panitia', false));
        $other = User::factory()->create();
        $student = Student::create(['user_id' => $other->id, 'student_number' => $other->username, 'full_name' => $other->name, 'joined_year' => 2026, 'class_name' => 'XI PPLG - RPL 1']);
        $this->actingAs($this->pembina)->put('/students/'.$student->id, [
            'student_number' => $student->student_number, 'full_name' => $student->full_name, 'joined_year' => 2026, 'class_name' => $student->class_name, 'role' => 'panitia',
        ])->assertSessionHasErrors('role');
        $this->assertSame('siswa', $other->fresh()->role);
    }

    public function test_expired_panitia_cannot_assess_read_pretests_or_record_attendance_and_keeps_own_student_access(): void
    {
        $role = $this->appoint(['ends_on' => '2026-10-09']);
        $other = User::factory()->create();
        $student = Student::create(['user_id' => $other->id, 'student_number' => $other->username, 'full_name' => $other->name, 'joined_year' => 2026, 'class_name' => 'X PPLG 1']);
        $assignment = AssessmentAssignment::create([
            'student_id' => $student->id, 'assessor_id' => $this->user->id, 'created_by' => $this->pembina->id,
            'title' => 'Speaking', 'aspect' => 'speaking', 'rubric' => AssessmentService::rubric('speaking'), 'status' => 'assigned',
        ]);
        $year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        foreach ([$student, $this->student] as $member) {
            Membership::create(['student_id' => $member->id, 'academic_year_id' => $year->id, 'class_name' => $member->class_name, 'status' => 'active']);
        }
        $activity = Activity::create(['academic_year_id' => $year->id, 'title' => 'Latihan', 'category' => 'Percakapan', 'activity_date' => '2026-10-10', 'start_time' => '15:00', 'created_by' => $this->pembina->id]);
        $this->actingAs($this->user->fresh())->get('/penilaian/'.$assignment->id)->assertOk();
        $this->get('/pre-test/siswa/'.$student->id)->assertOk();
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'Asia/Jakarta'));
        $this->get('/penilaian/'.$assignment->id)->assertForbidden();
        $this->post('/penilaian/'.$assignment->id.'/rekomendasi', ['action' => 'draft'])->assertForbidden();
        $this->get('/pre-test/siswa/'.$student->id)->assertForbidden();
        $this->post('/kegiatan/'.$activity->id.'/presensi', ['attendances' => [['student_id' => $student->id, 'status' => 'hadir']]])->assertForbidden();
        $this->get('/kegiatan/'.$activity->id)->assertOk()->assertInertia(fn ($page) => $page->has('students', 0)->has('attendances', 0));
        $this->get('/pre-test')->assertOk();
        $this->get('/perkembangan-saya')->assertOk();
        $this->actingAs($this->pembina)->get('/penilaian')->assertInertia(fn ($page) => $page->has('assessors', 0));
        $this->post('/penilaian', ['student_ids' => [$student->id], 'assessor_id' => $this->user->id, 'title' => 'Reading', 'aspect' => 'reading'])->assertSessionHasErrors('assessor_id');
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame('assigned', $assignment->fresh()->status);
        $this->assertNull($role->fresh()->revoked_at);
    }

    public function test_appointment_history_prevents_student_and_account_deletion_even_after_revocation(): void
    {
        $role = $this->appoint();
        $this->delete('/panitia-ec/'.$role->id)->assertSessionHasNoErrors();
        $this->delete('/students/'.$this->student->id)->assertSessionHasErrors('student');
        $this->actingAs($this->user->fresh())->delete('/profile', ['password' => 'password'])->assertSessionHasErrors('password');
        $this->actingAs($this->pembina)->delete('/profile', ['password' => 'password'])->assertSessionHasErrors('password');
        $this->assertNotNull($this->user->fresh());
        $this->assertNotNull($this->student->fresh());
        $this->assertDatabaseCount('committee_roles', 1);
    }
}
