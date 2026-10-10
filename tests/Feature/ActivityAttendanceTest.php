<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityScheme;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAttendanceTest extends TestCase {
    use RefreshDatabase;

    private User $pembina;
    private User $panitia;
    private AcademicYear $year;
    private Student $first;
    private Student $second;

    protected function setUp(): void {
        parent::setUp(); $this->withoutVite();
        $this->pembina = $this->account('admin', 'pembina');
        $this->panitia = $this->account('1003', 'panitia');
        Student::create(['user_id' => $this->panitia->id, 'student_number' => '1003', 'full_name' => $this->panitia->name, 'joined_year' => 2026, 'class_name' => 'XI PPLG - RPL 1', 'status' => 'active']);
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $this->first = $this->member('2001'); $this->second = $this->member('2002');
        foreach ([$this->first, $this->second] as $student) { $this->enroll($student); }
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

    private function enroll(Student $student, string $status = 'active', ?AcademicYear $year = null): Membership {
        return Membership::create(['student_id' => $student->id, 'academic_year_id' => ($year ?? $this->year)->id, 'class_name' => $student->class_name, 'status' => $status]);
    }

    private function data(): array {
        return ['academic_year_id' => $this->year->id, 'title' => 'Latihan percakapan', 'category' => 'Percakapan', 'activity_date' => '2026-10-09', 'start_time' => '15:00', 'end_time' => '16:00', 'location' => 'Ruang EC', 'pic' => 'Panitia EC', 'objectives' => 'Siswa berlatih percakapan sehari-hari.', 'agenda' => 'Pemanasan, latihan berpasangan, refleksi.', 'status' => 'scheduled'];
    }

    private function activity(string $status = 'scheduled'): Activity {
        return Activity::create(array_merge($this->data(), ['created_by' => $this->pembina->id, 'status' => $status]));
    }

    private function attendanceUrl(Activity $activity): string { return '/kegiatan/'.$activity->id.'/presensi'; }

    public function test_pembina_can_manage_schemes_without_changing_existing_activity_agendas(): void {
        $schemeData = ['name' => 'Percakapan mingguan', 'category' => 'Percakapan', 'objectives' => 'Melatih percakapan sederhana.', 'agenda' => "Pemanasan\nLatihan\nRefleksi", 'duration_minutes' => 60];
        $this->actingAs($this->pembina)->post('/skema-kegiatan', $schemeData)->assertSessionHasNoErrors();
        $scheme = ActivityScheme::firstOrFail();
        $activityData = array_merge($this->data(), ['activity_scheme_id' => $scheme->id, 'objectives' => $scheme->objectives, 'agenda' => $scheme->agenda]);
        $this->post('/kegiatan', $activityData)->assertSessionHasNoErrors();
        $activity = Activity::firstOrFail();
        $this->get('/skema-kegiatan')->assertInertia(fn ($page) => $page->component('ActivitySchemes/Index')->has('schemes', 1)->where('schemes.0.activities_count', 1));
        $this->put('/skema-kegiatan/'.$scheme->id, array_merge($schemeData, ['agenda' => 'Agenda baru']))->assertSessionHasNoErrors();
        $this->assertSame($schemeData['agenda'], $activity->fresh()->agenda);
        $this->delete('/skema-kegiatan/'.$scheme->id)->assertSessionHasNoErrors();
        $this->assertNull($activity->fresh()->activity_scheme_id);
        $this->assertSame($schemeData['agenda'], $activity->fresh()->agenda);
        $this->assertDatabaseCount('activity_schemes', 0);
    }

    public function test_pembina_can_create_edit_filter_and_delete_activities_without_attendance(): void {
        $data = $this->data(); $data['created_by'] = $this->panitia->id;
        $this->actingAs($this->pembina)->get('/kegiatan/buat')->assertOk()->assertInertia(fn ($page) => $page->component('Activities/Form')->has('years', 1));
        $this->post('/kegiatan', $data)->assertSessionHasNoErrors();
        $item = Activity::firstOrFail();
        $this->assertSame($this->pembina->id, $item->created_by);
        $this->get('/kegiatan/'.$item->id.'/edit')->assertOk();
        $this->put('/kegiatan/'.$item->id, array_merge($data, ['title' => 'Latihan debat', 'status' => 'completed']))->assertSessionHasNoErrors();
        $this->assertSame('Latihan debat', $item->fresh()->title);
        $this->assertSame('completed', $item->fresh()->status);
        $this->get('/kegiatan?status=completed&search=debat')->assertInertia(fn ($page) => $page->component('Activities/Index')->has('activities.data', 1));
        $this->get('/kegiatan?status=scheduled')->assertInertia(fn ($page) => $page->has('activities.data', 0));
        $this->delete('/kegiatan/'.$item->id)->assertSessionHasNoErrors()->assertRedirect('/kegiatan');
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_activity_time_and_scheme_inputs_are_validated_before_writing(): void {
        $this->actingAs($this->pembina)->post('/kegiatan', array_merge($this->data(), ['end_time' => '14:00', 'activity_date' => 'invalid', 'activity_scheme_id' => 99999]))->assertSessionHasErrors(['end_time', 'activity_date', 'activity_scheme_id']);
        $this->assertDatabaseCount('activities', 0);
        $this->post('/skema-kegiatan', ['name' => 'Skema', 'category' => 'Debat', 'objectives' => '', 'agenda' => '', 'duration_minutes' => 0])->assertSessionHasErrors(['objectives', 'agenda', 'duration_minutes']);
        $this->assertDatabaseCount('activity_schemes', 0);
    }

    public function test_pembina_and_panitia_can_record_mass_attendance_and_correct_only_selected_rows(): void {
        $item = $this->activity(); $url = $this->attendanceUrl($item);
        $this->actingAs($this->panitia)->post($url, ['recorded_by' => $this->pembina->id, 'attendances' => [
            ['student_id' => $this->first->id, 'status' => 'hadir'], ['student_id' => $this->second->id, 'status' => 'izin'],
        ]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', ['activity_id' => $item->id, 'student_id' => $this->first->id, 'status' => 'hadir', 'recorded_by' => $this->panitia->id]);
        $firstId = Attendance::where('student_id', $this->first->id)->firstOrFail()->id;
        $this->actingAs($this->pembina)->post($url, ['attendances' => [['student_id' => $this->first->id, 'status' => 'sakit']]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', ['id' => $firstId, 'status' => 'sakit', 'recorded_by' => $this->pembina->id]);
        $this->assertDatabaseHas('attendances', ['student_id' => $this->second->id, 'status' => 'izin', 'recorded_by' => $this->panitia->id]);
        foreach (['alpa', 'belum_diisi', 'hadir'] as $status) {
            $this->post($url, ['attendances' => [['student_id' => $this->first->id, 'status' => $status]]])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('attendances', ['id' => $firstId, 'status' => $status]);
        }
        $this->get('/kegiatan/'.$item->id)->assertInertia(fn ($page) => $page->component('Activities/Show')->has('students', 2)->has('attendances', 2)->where('attendances.0.recorder.name', $this->pembina->name));
    }

    public function test_roster_excludes_unregistered_nonactive_and_other_semester_members_and_never_marks_them_alpa(): void {
        $unregistered = $this->member('2003');
        $excluded = [$unregistered];
        foreach (['inactive', 'left', 'alumni'] as $index => $status) { $student = $this->member('201'.($index + 1), $status); $this->enroll($student); $excluded[] = $student; }
        $inactiveMembership = $this->member('2004'); $this->enroll($inactiveMembership, 'inactive'); $excluded[] = $inactiveMembership;
        $next = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $wrongSemester = $this->member('2005'); $this->enroll($wrongSemester, 'active', $next); $excluded[] = $wrongSemester;
        $item = $this->activity();
        $this->actingAs($this->panitia)->get('/kegiatan/'.$item->id)->assertInertia(fn ($page) => $page->has('students', 2)->has('attendances', 0));
        foreach ($excluded as $student) {
            $this->post($this->attendanceUrl($item), ['attendances' => [['student_id' => $student->id, 'status' => 'hadir']]])->assertSessionHasErrors('attendances.0.student_id');
        }
        $this->post($this->attendanceUrl($item), ['attendances' => [['student_id' => $this->first->id, 'status' => 'hadir']]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseMissing('attendances', ['student_id' => $this->second->id]);
    }

    public function test_invalid_mass_attendance_rolls_back_entire_batch_and_validates_unique_students(): void {
        $item = $this->activity(); $url = $this->attendanceUrl($item);
        $unregistered = $this->member('2003');
        $this->actingAs($this->panitia)->post($url, ['attendances' => [
            ['student_id' => $this->first->id, 'status' => 'hadir'], ['student_id' => $unregistered->id, 'status' => 'alpa'],
        ]])->assertSessionHasErrors('attendances.1.student_id');
        $this->assertDatabaseCount('attendances', 0);
        $this->post($url, ['attendances' => [['student_id' => $this->first->id, 'status' => 'hadir'], ['student_id' => $this->first->id, 'status' => 'izin']]])->assertSessionHasErrors('attendances.1.student_id');
        $this->post($url, ['attendances' => [['student_id' => $this->first->id, 'status' => 'invalid'], ['student_id' => 99999, 'status' => 'hadir']]])->assertSessionHasErrors(['attendances.0.status', 'attendances.1.student_id']);
        $this->post($url, ['attendances' => [['student_id' => $this->first->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]]])->assertSessionHasErrors('attendances.0');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_cancelled_activity_blocks_presensi_without_erasing_existing_records(): void {
        $item = $this->activity('cancelled');
        Attendance::create(['activity_id' => $item->id, 'student_id' => $this->first->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $this->actingAs($this->panitia)->post($this->attendanceUrl($item), ['attendances' => [['student_id' => $this->first->id, 'status' => 'alpa']]])->assertSessionHasErrors('attendances');
        $this->assertDatabaseHas('attendances', ['student_id' => $this->first->id, 'status' => 'hadir']);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_historical_attendance_remains_correctable_after_membership_and_status_changes(): void {
        $item = $this->activity('completed');
        Attendance::create(['activity_id' => $item->id, 'student_id' => $this->first->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $this->first->update(['status' => 'alumni']);
        $this->first->memberships()->delete();
        $this->actingAs($this->panitia)->get('/kegiatan/'.$item->id)->assertInertia(fn ($page) => $page->has('students', 2));
        $this->post($this->attendanceUrl($item), ['attendances' => [['student_id' => $this->first->id, 'status' => 'izin']]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['student_id' => $this->first->id, 'status' => 'izin']);
        $this->actingAs($this->first->user)->get('/kegiatan/'.$item->id)->assertOk();
    }

    public function test_students_see_only_their_semester_activities_and_own_attendance(): void {
        $item = $this->activity();
        Attendance::create(['activity_id' => $item->id, 'student_id' => $this->first->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        Attendance::create(['activity_id' => $item->id, 'student_id' => $this->second->id, 'status' => 'sakit', 'recorded_by' => $this->pembina->id]);
        $next = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $foreign = Activity::create(array_merge($this->data(), ['academic_year_id' => $next->id, 'created_by' => $this->pembina->id]));
        $this->actingAs($this->first->user)->get('/kegiatan')->assertInertia(fn ($page) => $page->has('activities.data', 1)->where('activities.data.0.id', $item->id));
        $this->get('/kegiatan/'.$item->id)->assertInertia(fn ($page) => $page->has('students', 0)->has('attendances', 0)->where('myAttendance.student_id', $this->first->id)->where('myAttendance.status', 'hadir'));
        $this->get('/kegiatan/'.$foreign->id)->assertForbidden();
        $this->post($this->attendanceUrl($item), ['attendances' => [['student_id' => $this->first->id, 'status' => 'hadir']]])->assertForbidden();
        $unregistered = $this->member('2003');
        $this->actingAs($unregistered->user)->get('/kegiatan')->assertInertia(fn ($page) => $page->has('activities.data', 0));
        $this->get('/kegiatan/'.$item->id)->assertForbidden();
    }

    public function test_activity_with_attendance_cannot_be_deleted_or_moved_to_another_semester(): void {
        $item = $this->activity();
        Attendance::create(['activity_id' => $item->id, 'student_id' => $this->first->id, 'status' => 'hadir', 'recorded_by' => $this->panitia->id]);
        $next = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $this->actingAs($this->pembina)->put('/kegiatan/'.$item->id, array_merge($this->data(), ['academic_year_id' => $next->id]))->assertSessionHasErrors('academic_year_id');
        $this->assertSame($this->year->id, $item->fresh()->academic_year_id);
        $this->delete('/kegiatan/'.$item->id)->assertSessionHasErrors('activity');
        $this->assertNotNull($item->fresh());
        $this->assertDatabaseCount('attendances', 1);
        $this->delete('/profile', ['password' => 'test-password'])->assertSessionHasErrors('password');
        $this->actingAs($this->panitia)->delete('/profile', ['password' => 'test-password'])->assertSessionHasErrors('password');
        $this->assertNotNull($this->panitia->fresh());
    }

    public function test_only_pembina_can_manage_activity_schemes_and_schedules(): void {
        $item = $this->activity();
        $scheme = ActivityScheme::create(['name' => 'Debat', 'category' => 'Debat', 'objectives' => 'Berlatih debat', 'agenda' => 'Pembukaan dan debat', 'duration_minutes' => 60]);
        foreach ([$this->panitia, $this->first->user] as $actor) {
            $this->actingAs($actor)->get('/kegiatan/buat')->assertForbidden();
            $this->post('/kegiatan', $this->data())->assertForbidden();
            $this->get('/kegiatan/'.$item->id.'/edit')->assertForbidden();
            $this->put('/kegiatan/'.$item->id, $this->data())->assertForbidden();
            $this->delete('/kegiatan/'.$item->id)->assertForbidden();
            $this->get('/skema-kegiatan')->assertForbidden();
            $this->post('/skema-kegiatan', $scheme->toArray())->assertForbidden();
            $this->put('/skema-kegiatan/'.$scheme->id, $scheme->toArray())->assertForbidden();
            $this->delete('/skema-kegiatan/'.$scheme->id)->assertForbidden();
        }
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseCount('activity_schemes', 1);
    }
}
