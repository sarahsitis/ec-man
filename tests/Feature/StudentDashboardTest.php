<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;

    private Student $student;

    private AcademicYear $year;

    private int $assessmentNumber = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-11 15:30:00', 'Asia/Jakarta'));
        $this->pembina = $this->account('pembina', 'pembina');
        $user = $this->account('siswa');
        $this->student = Student::create(['user_id' => $user->id, 'student_number' => '1001', 'full_name' => $user->name, 'class_name' => 'XI RPL 1', 'status' => 'active', 'joined_year' => 2026]);
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'status' => 'active']);
    }

    private function account(string $name, string $role = 'siswa'): User
    {
        return User::create(['name' => $name, 'username' => $name, 'role' => $role, 'password' => 'test-password']);
    }

    private function activity(array $data = []): Activity
    {
        return Activity::create(array_merge(['academic_year_id' => $this->year->id, 'title' => 'Debate', 'category' => 'Speaking', 'activity_date' => '2026-10-16', 'start_time' => '13:00', 'end_time' => '14:00', 'location' => 'Ruang EC', 'pic' => 'Panitia EC', 'status' => 'scheduled', 'created_by' => $this->pembina->id], $data));
    }

    public function test_newly_created_meeting_appears_in_student_dashboard_with_details(): void
    {
        $this->actingAs($this->pembina)->post('/kegiatan', [
            'academic_year_id' => $this->year->id, 'title' => 'Debate Jumat', 'category' => 'Speaking',
            'activity_date' => '2026-10-16', 'start_time' => '13:00', 'end_time' => '14:00',
            'location' => 'Ruang EC', 'pic' => 'Panitia EC', 'status' => 'scheduled',
        ])->assertSessionHasNoErrors();
        $activity = Activity::where('title', 'Debate Jumat')->sole();
        $this->actingAs($this->student->user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->component('Dashboard')->where('upcomingActivityCount', 1)->has('upcomingActivities', 1)
            ->where('upcomingActivities.0.id', $activity->id)->where('upcomingActivities.0.title', 'Debate Jumat')
            ->where('upcomingActivities.0.activity_date', '2026-10-16')->where('upcomingActivities.0.start_time', '13:00')
            ->where('upcomingActivities.0.location', 'Ruang EC')->where('upcomingActivities.0.pic', 'Panitia EC')
            ->where('upcomingActivities.0.academic_year.semester', 'ganjil')->where('upcomingActivities.0.academic_year.is_active', true)->where('upcomingActivities.0.phase', 'upcoming')
            ->missing('upcomingActivities.0.attendances')->missing('upcomingActivities.0.created_by'));
        $this->get('/kegiatan/'.$activity->id)->assertOk();
    }

    public function test_dashboard_shows_five_nearest_meetings_with_full_visible_count(): void
    {
        $future = [];
        for ($day = 18; $day >= 12; $day--) {
            $future[$day] = $this->activity(['activity_date' => '2026-10-'.$day]);
        }
        $ongoing = $this->activity(['activity_date' => '2026-10-11', 'start_time' => '15:00', 'end_time' => '16:00']);
        $this->actingAs($this->student->user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('upcomingActivityCount', 8)->has('upcomingActivities', 5)
            ->where('upcomingActivities.0.id', $ongoing->id)->where('upcomingActivities.0.phase', 'ongoing')
            ->where('upcomingActivities.1.id', $future[12]->id)->where('upcomingActivities.4.id', $future[15]->id));
    }

    public function test_dashboard_uses_wib_end_time_boundaries_and_excludes_ended_completed_and_cancelled(): void
    {
        $boundary = $this->activity(['activity_date' => '2026-10-11', 'start_time' => '14:00', 'end_time' => '15:30:00']);
        $noEnd = $this->activity(['activity_date' => '2026-10-11', 'start_time' => '15:00', 'end_time' => null]);
        $this->activity(['activity_date' => '2026-10-11', 'start_time' => '14:00', 'end_time' => '15:29:59']);
        $this->activity(['activity_date' => '2026-10-10', 'end_time' => null]);
        $this->activity(['status' => 'completed']);
        $this->activity(['status' => 'cancelled']);
        $this->travelTo(Carbon::parse('2026-10-11 08:30:00', 'UTC'));
        $this->actingAs($this->student->user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('upcomingActivityCount', 2)->has('upcomingActivities', 2)
            ->where('upcomingActivities.0.id', $boundary->id)->where('upcomingActivities.1.id', $noEnd->id));
        $this->travelTo(Carbon::parse('2026-10-12 00:00:00', 'Asia/Jakarta'));
        $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('upcomingActivityCount', 0)->has('upcomingActivities', 0));
    }

    public function test_dashboard_only_includes_accessible_activities_and_ignores_forged_student_id(): void
    {
        $enrolled = $this->activity();
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $ownRecord = $this->activity(['academic_year_id' => $otherYear->id, 'pic' => null]);
        Attendance::create(['activity_id' => $ownRecord->id, 'student_id' => $this->student->id, 'status' => 'belum_diisi', 'recorded_by' => $this->pembina->id]);
        $this->activity(['academic_year_id' => $otherYear->id]);
        $this->actingAs($this->student->user)->get('/dashboard?student_id=999')->assertInertia(fn ($page) => $page
            ->where('upcomingActivityCount', 2)->has('upcomingActivities', 2)
            ->where('upcomingActivities.0.id', $enrolled->id)->where('upcomingActivities.1.id', $ownRecord->id)
            ->where('upcomingActivities.1.pic', 'pembina'));
        $this->actingAs($this->account('outsider'))->get('/dashboard?student_id='.$this->student->id)->assertInertia(fn ($page) => $page
            ->where('upcomingActivityCount', 0)->has('upcomingActivities', 0));
    }

    public function test_existing_dashboard_access_for_guests_pembina_and_active_panitia_is_preserved(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs($this->pembina)->get('/dashboard')->assertInertia(fn ($page) => $page->component('Dashboard')->missing('upcomingActivities'));
        $user = $this->student->user;
        $user->update(['role' => 'panitia']);
        $user->committeeRoles()->create(['starts_on' => '2026-10-01', 'ends_on' => '2026-12-31']);
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/panitia/dashboard');
    }

    public function test_attendance_chart_counts_only_completed_activities_and_distinguishes_missing_from_absent(): void
    {
        foreach (['hadir', 'hadir', 'izin', 'sakit', 'alpa', 'belum_diisi', null] as $status) {
            $activity = $this->activity(['status' => 'completed', 'activity_date' => '2026-10-01']);
            if ($status) {
                Attendance::create(['activity_id' => $activity->id, 'student_id' => $this->student->id, 'status' => $status, 'recorded_by' => $this->pembina->id]);
            }
        }
        foreach (['scheduled', 'cancelled'] as $status) {
            $activity = $this->activity(['status' => $status]);
            Attendance::create(['activity_id' => $activity->id, 'student_id' => $this->student->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        }
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $this->activity(['status' => 'completed', 'academic_year_id' => $otherYear->id]);
        $this->actingAs($this->student->user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('attendanceSummary', ['counts' => ['hadir' => 2, 'izin' => 1, 'sakit' => 1, 'alpa' => 1, 'belum_diisi' => 2], 'total' => 7, 'recorded' => 5, 'attendanceRate' => 40]));
        $this->student->memberships()->delete();
        $this->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('attendanceSummary.total', 6)->where('attendanceSummary.counts.belum_diisi', 1));
    }

    public function test_dashboard_without_recorded_attendance_or_grades_does_not_invent_values(): void
    {
        $this->activity(['status' => 'completed']);
        $this->actingAs($this->student->user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('attendanceSummary.total', 1)->where('attendanceSummary.recorded', 0)
            ->where('attendanceSummary.counts.belum_diisi', 1)->where('attendanceSummary.attendanceRate', null)
            ->has('recentGrades', 0)->where('gradeCount', 0));
        $this->actingAs($this->account('no-profile'))->get('/dashboard?student_id='.$this->student->id)->assertInertia(fn ($page) => $page
            ->where('attendanceSummary.total', 0)->where('attendanceSummary.recorded', 0)
            ->where('attendanceSummary.counts.hadir', 0)->where('attendanceSummary.attendanceRate', null)
            ->has('recentGrades', 0)->where('gradeCount', 0));
    }

    private function assignment(Student $student, string $status = 'approved'): AssessmentAssignment
    {
        $assessment = Assessment::create(['created_by' => $this->pembina->id, 'title' => 'Speaking '.$status.' '.++$this->assessmentNumber, 'aspect' => 'speaking', 'rubric' => AssessmentService::rubric('speaking')]);

        return $assessment->assignments()->create(['student_id' => $student->id, 'assessor_id' => $this->pembina->id, 'created_by' => $this->pembina->id, 'title' => $assessment->title, 'aspect' => 'speaking', 'rubric' => $assessment->rubric, 'status' => $status, 'proposed_score' => 4, 'observations' => 'Internal observation must stay private.']);
    }

    public function test_assessment_history_shows_five_latest_own_official_grades_and_excludes_drafts(): void
    {
        $ids = [];
        foreach (['2026-10-01 08:00:00', '2026-10-02 08:00:00', '2026-10-03 08:00:00', '2026-10-04 08:00:00', '2026-10-05 08:00:00', '2026-10-05 08:00:00'] as $date) {
            $assignment = $this->assignment($this->student);
            $ids[] = Score::create(['assessment_assignment_id' => $assignment->id, 'assessment_id' => $assignment->assessment_id, 'student_id' => $this->student->id, 'value' => 3, 'approved_at' => $date, 'approved_by' => $this->pembina->id])->id;
        }
        foreach (['draft', 'submitted', 'revision', 'rejected', 'approved'] as $status) {
            $this->assignment($this->student, $status);
        }
        $otherUser = $this->account('other');
        $other = Student::create(['user_id' => $otherUser->id, 'student_number' => '1002', 'full_name' => $otherUser->name, 'joined_year' => 2026]);
        $otherAssignment = $this->assignment($other);
        Score::create(['assessment_assignment_id' => $otherAssignment->id, 'assessment_id' => $otherAssignment->assessment_id, 'student_id' => $other->id, 'value' => 4, 'approved_at' => '2026-10-11 00:00:00', 'approved_by' => $this->pembina->id]);
        $this->actingAs($this->student->user)->get('/dashboard?student_id='.$other->id)->assertInertia(fn ($page) => $page
            ->where('gradeCount', 6)->has('recentGrades', 5)->where('recentGrades.0.id', $ids[5])->where('recentGrades.4.id', $ids[1])
            ->where('recentGrades.0.title', 'Speaking approved 6')->where('recentGrades.0.aspect', 'speaking')
            ->where('recentGrades.0.value', 3)->where('recentGrades.0.approver', 'pembina')->where('recentGrades.0.approvedAt', '2026-10-05T08:00:00.000000Z')
            ->missing('recentGrades.0.observations')->missing('recentGrades.0.proposed_score')->missing('recentGrades.0.student_id'));
        $this->actingAs($this->account('outsider-grades'))->get('/dashboard?student_id='.$this->student->id)->assertInertia(fn ($page) => $page
            ->where('gradeCount', 0)->has('recentGrades', 0)->where('attendanceSummary.total', 0));
    }

    public function test_legacy_official_grade_with_missing_approval_metadata_is_still_available(): void
    {
        $assignment = $this->assignment($this->student);
        Score::create(['assessment_assignment_id' => $assignment->id, 'assessment_id' => $assignment->assessment_id, 'student_id' => $this->student->id, 'value' => 2]);
        $this->actingAs($this->student->user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('gradeCount', 1)->has('recentGrades', 1)->where('recentGrades.0.approvedAt', null)->where('recentGrades.0.approver', null));
    }
}
