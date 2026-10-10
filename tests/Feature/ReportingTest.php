<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Interest;
use App\Models\PreTestResult;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;
    private User $panitia;
    private Student $student;
    private Student $other;
    private Student $committee;
    private int $taskNumber = 0;

    protected function setUp(): void
    {
        parent::setUp(); $this->withoutVite();
        $this->pembina = User::factory()->create(['role' => 'pembina']);
        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->committee = $this->member($this->panitia, 'XI PPLG - RPL 1');
        $this->panitia->committeeRoles()->create(['starts_on' => now('Asia/Jakarta')->subMonth()->toDateString(), 'ends_on' => now('Asia/Jakarta')->addMonth()->toDateString()]);
        $this->student = $this->member(User::factory()->create());
        $this->other = $this->member(User::factory()->create());
    }

    private function member(User $user, string $class = 'X PPLG 1', string $status = 'active'): Student
    {
        return Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'joined_year' => 2026, 'class_name' => $class, 'status' => $status]);
    }

    private function assignment(Student $student, string $aspect = 'listening', string $status = 'submitted'): AssessmentAssignment
    {
        $title = 'Report task '.++$this->taskNumber;
        $assessment = Assessment::create(['created_by' => $this->pembina->id, 'title' => $title, 'aspect' => $aspect, 'rubric' => AssessmentService::rubric($aspect)]);
        return $assessment->assignments()->create([
            'student_id' => $student->id, 'assessor_id' => $this->panitia->id, 'created_by' => $this->pembina->id,
            'title' => $title, 'aspect' => $aspect, 'rubric' => $assessment->rubric, 'status' => $status,
            'proposed_score' => 4, 'final_score' => 4, 'observations' => 'Internal evidence must stay private.',
        ]);
    }

    private function score(Student $student, string $aspect, int $value, ?string $date = '2026-10-10 08:00:00'): Score
    {
        $assignment = $this->assignment($student, $aspect, 'approved');
        return $assignment->officialScore()->create([
            'assessment_id' => $assignment->assessment_id, 'student_id' => $student->id, 'value' => $value,
            'approved_by' => $this->pembina->id, 'approved_at' => $date, 'feedback' => 'Published feedback', 'review_note' => 'Published approval note',
        ]);
    }

    private function interest(Student $student, string $name, bool $primary = false): Interest
    {
        $interest = Interest::where('name', $name)->firstOrFail();
        $student->interests()->create(['interest_category_id' => $interest->id, 'is_primary' => $primary, 'learning_goal' => 'Practice speaking confidently.']);
        return $interest;
    }

    public function test_student_report_aggregates_official_scores_only_and_keeps_missing_aspects_null(): void
    {
        $this->score($this->student, 'listening', 2, '2026-10-09 08:00:00');
        $this->score($this->student, 'listening', 4);
        $this->score($this->student, 'speaking', 1);
        $this->score($this->other, 'reading', 4);
        foreach (['assigned', 'draft', 'submitted', 'revision', 'approved', 'rejected'] as $status) { $this->assignment($this->student, 'writing', $status); }
        $this->actingAs($this->student->user)->get('/perkembangan-saya?student_id='.$this->other->id)->assertInertia(fn ($page) => $page
            ->where('report.total_scores', 3)->where('report.graded_aspects', 2)->where('report.overall_average', 2.33)
            ->has('report.skills', 4)->where('report.skills.0.aspect', 'listening')->where('report.skills.0.count', 2)
            ->where('report.skills.0.average', 3)->where('report.skills.0.latest', 4)->where('report.skills.1.average', 1)
            ->where('report.skills.2.average', null)->where('report.skills.2.count', 0)->where('report.skills.3.latest', null)
            ->has('grades.data', 3)->missing('grades.data.0.observations')->missing('grades.data.0.proposed_score'));
    }

    public function test_delegation_submission_and_approval_publish_all_four_skills_to_the_student_report(): void
    {
        foreach (['listening' => 1, 'speaking' => 2, 'reading' => 3, 'writing' => 4] as $aspect => $value) {
            $title = 'End-to-end '.$aspect;
            $this->actingAs($this->pembina)->post('/penilaian', [
                'student_ids' => [$this->student->id, $this->other->id], 'assessor_id' => $this->panitia->id,
                'title' => $title, 'aspect' => $aspect,
            ])->assertSessionHasNoErrors()->assertRedirect();
            $assignment = AssessmentAssignment::where('student_id', $this->student->id)->where('title', $title)->firstOrFail();
            $this->actingAs($this->panitia)->post('/penilaian/'.$assignment->id.'/rekomendasi', [
                'action' => 'submit', 'proposed_score' => $value, 'observations' => 'Bukti pengamatan untuk '.$aspect,
                'feedback' => 'Latihan lanjutan untuk '.$aspect,
            ])->assertSessionHasNoErrors()->assertRedirect();
            $this->actingAs($this->student->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
                ->where('report.total_scores', $value - 1));
            $this->actingAs($this->pembina)->post('/penilaian/'.$assignment->id.'/keputusan', [
                'decision' => 'approve', 'final_score' => $value,
            ])->assertSessionHasNoErrors()->assertRedirect();
        }

        $this->assertDatabaseCount('scores', 4);
        foreach (['/perkembangan-saya', '/profile', '/laporan/siswa/'.$this->student->id] as $url) {
            $this->actingAs($this->student->user)->get($url)->assertInertia(fn ($page) => $page
                ->where('report.total_scores', 4)->where('report.graded_aspects', 4)->where('report.overall_average', 2.5)
                ->where('report.skills.0.average', 1)->where('report.skills.1.average', 2)
                ->where('report.skills.2.average', 3)->where('report.skills.3.average', 4));
        }
        $this->actingAs($this->other->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
            ->where('report.total_scores', 0)->where('report.overall_average', null)->has('grades.data', 0));
    }

    public function test_pretest_submission_populates_profile_and_interest_distribution_without_official_scores(): void
    {
        $conversation = Interest::where('name', 'Percakapan')->firstOrFail();
        $storytelling = Interest::where('name', 'Storytelling')->firstOrFail();
        $data = [
            'version' => config('pretest.version'),
            'answers' => collect(config('pretest.questions'))->pluck('correct', 'id')->all(),
            'self_assessment' => ['listening' => 1, 'speaking' => 2, 'reading' => 3, 'writing' => 4],
            'interest_ids' => [$conversation->id, $storytelling->id], 'primary_interest_id' => $storytelling->id,
            'learning_goal' => 'Saya ingin percaya diri bercerita dalam bahasa Inggris.', 'student_id' => $this->other->id,
        ];
        $this->actingAs($this->student->user)->post('/pre-test', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/profile')->assertInertia(fn ($page) => $page
            ->where('report.total_scores', 0)->where('report.overall_average', null)
            ->where('report.skills.3.self_assessment', 4)->where('report.interests.0.name', 'Storytelling')
            ->where('report.interests.0.is_primary', true)->where('report.learning_goal', $data['learning_goal']));

        $this->actingAs($this->pembina)->get('/laporan/minat')->assertInertia(fn ($page) => $page
            ->where('counts', ['total' => 3, 'respondents' => 1, 'pending' => 2, 'selections' => 2])
            ->where('distribution', function ($rows) {
                $byName = collect($rows)->keyBy('name');
                return $byName['Percakapan']['percentage'] === 100 && $byName['Percakapan']['primary_count'] === 0
                    && $byName['Storytelling']['primary_percentage'] === 100 && $byName['Storytelling']['primary_count'] === 1;
            }));
        $this->actingAs($this->other->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
            ->where('report.baseline_submitted_at', null)->has('report.interests', 0));
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_revocation_or_student_ineligibility_removes_assigned_committee_report_access(): void
    {
        $this->assignment($this->student);
        $url = '/laporan/siswa/'.$this->student->id;
        foreach (['inactive', 'left', 'alumni'] as $status) {
            $this->committee->update(['status' => $status]);
            $this->actingAs($this->panitia->fresh())->get($url)->assertForbidden();
        }
        $this->committee->update(['status' => 'active', 'class_name' => 'X PPLG 1']);
        $this->actingAs($this->panitia->fresh())->get($url)->assertForbidden();
        $this->committee->update(['class_name' => 'XI PPLG - RPL 1']);
        $this->actingAs($this->panitia->fresh())->get($url)->assertOk();
        $this->panitia->committeeRoles()->update(['revoked_at' => now()]);
        $this->actingAs($this->panitia->fresh())->get($url)->assertForbidden();
        $this->get('/laporan/siswa/'.$this->committee->id)->assertOk();
    }

    public function test_summary_uses_all_scores_instead_of_only_the_current_page(): void
    {
        for ($index = 0; $index < 21; $index++) { $this->score($this->student, 'reading', $index === 20 ? 4 : 2); }
        $this->actingAs($this->student->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
            ->has('grades.data', 20)->where('report.total_scores', 21)->where('report.skills.2.count', 21)->where('report.skills.2.average', 2.1));
        $this->get('/perkembangan-saya?page=2')->assertInertia(fn ($page) => $page->has('grades.data', 1)->where('report.total_scores', 21));
    }

    public function test_date_filters_follow_inclusive_jakarta_days_for_summary_and_history(): void
    {
        $this->score($this->student, 'writing', 1, '2026-10-09 16:59:59');
        $this->score($this->student, 'writing', 2, '2026-10-09 17:00:00');
        $this->score($this->student, 'writing', 4, '2026-10-10 16:59:59');
        $this->score($this->student, 'writing', 1, '2026-10-10 17:00:00');
        $this->score($this->student, 'writing', 3, null);
        $this->actingAs($this->student->user)->get('/perkembangan-saya?from=2026-10-10&until=2026-10-10')->assertInertia(fn ($page) => $page
            ->where('report.total_scores', 2)->where('report.skills.3.average', 3)->has('grades.data', 2));
        $this->get('/perkembangan-saya')->assertInertia(fn ($page) => $page->where('report.total_scores', 5));
        $this->get('/perkembangan-saya?from=2026-10-11&until=2026-10-10')->assertSessionHasErrors('until');
        $this->get('/perkembangan-saya?from=not-a-date')->assertSessionHasErrors('from');
        $this->get('/perkembangan-saya?until=2026-10-09')->assertInertia(fn ($page) => $page->where('report.total_scores', 1));
    }

    public function test_report_and_profile_show_interests_and_baseline_separately_from_official_scores(): void
    {
        $interest = $this->interest($this->student, 'Percakapan', true);
        PreTestResult::create([
            'student_id' => $this->student->id, 'version' => 'ec-pretest-v1', 'answers' => ['r1' => 'b'], 'score' => 12, 'max_score' => 12,
            'level' => 'Siap pengayaan', 'domain_scores' => [], 'self_assessment' => ['listening' => 4, 'speaking' => 3, 'reading' => 2, 'writing' => 1],
            'interests' => [['id' => $interest->id, 'name' => $interest->name, 'is_primary' => true]], 'learning_goal' => 'My original learning goal.', 'submitted_at' => now(),
        ]);
        foreach (['/perkembangan-saya', '/profile', '/laporan/siswa/'.$this->student->id] as $url) {
            $this->actingAs($this->student->user)->get($url)->assertInertia(fn ($page) => $page
                ->where('report.total_scores', 0)->where('report.skills.0.average', null)->where('report.skills.0.self_assessment', 4)
                ->where('report.interests.0.name', 'Percakapan')->where('report.interests.0.is_primary', true)
                ->where('report.learning_goal', 'My original learning goal.')->missing('report.answers')->missing('report.observations'));
        }
    }

    public function test_empty_or_missing_student_profile_has_four_unknown_aspects_without_fake_zero_scores(): void
    {
        $this->actingAs(User::factory()->create())->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
            ->where('student', null)->where('report.total_scores', 0)->where('report.overall_average', null)
            ->where('report.skills.0.average', null)->where('report.skills.3.average', null)->has('report.interests', 0));
        $this->actingAs($this->pembina)->get('/profile')->assertInertia(fn ($page) => $page->where('report', null));
    }

    public function test_student_reports_allow_owner_pembina_and_active_assigned_panitia_only(): void
    {
        $url = '/laporan/siswa/'.$this->student->id;
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->other->user)->get($url)->assertForbidden();
        $this->actingAs($this->panitia)->get($url)->assertForbidden();
        $this->assignment($this->student);
        $this->student->update(['email' => 'private@example.test', 'phone' => '0812345', 'address' => 'Private address', 'profile_photo_path' => 'private/test.jpg']);
        $this->actingAs($this->panitia)->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->where('student.profile_photo_url', null)->missing('student.email')->missing('student.phone')->missing('student.address')->missing('student.user_id'));
        $this->panitia->committeeRoles()->update(['ends_on' => now('Asia/Jakarta')->subDay()->toDateString()]);
        $this->get($url)->assertForbidden();
        $this->actingAs($this->student->user)->get($url)->assertOk();
        $this->actingAs($this->pembina)->get($url)->assertOk();
    }

    public function test_distribution_counts_people_per_interest_and_uses_respondents_as_denominator(): void
    {
        $this->interest($this->student, 'Percakapan', true); $this->interest($this->student, 'Storytelling');
        $this->interest($this->other, 'Percakapan', true); $this->interest($this->committee, 'Debat', true);
        $this->member(User::factory()->create(), 'X MPLB 1');
        $this->actingAs($this->pembina)->get('/laporan/minat')->assertInertia(function ($page) {
            $page->component('Reports/Interests')->where('counts', ['total' => 4, 'respondents' => 3, 'pending' => 1, 'selections' => 4])
                ->has('distribution', 6)->where('distribution', function ($rows) {
                    $byName = collect($rows)->keyBy('name');
                    return $byName['Percakapan']['selections_count'] === 2 && $byName['Percakapan']['percentage'] === 66.7
                        && $byName['Storytelling']['primary_count'] === 0 && $byName['Storytelling']['percentage'] === 33.3
                        && $byName['Menulis kreatif']['selections_count'] === 0;
                });
        });
    }

    public function test_distribution_filters_status_and_class_and_includes_students_without_interests(): void
    {
        $this->interest($this->student, 'Percakapan', true); $this->interest($this->other, 'Storytelling', true);
        $inactive = $this->member(User::factory()->create(), 'X PPLG 1', 'inactive');
        $this->interest($inactive, 'Storytelling', true);
        $this->actingAs($this->pembina)->get('/laporan/minat?class_name=X%20PPLG%201')->assertInertia(fn ($page) => $page
            ->where('counts.total', 2)->where('counts.respondents', 2)->where('filters.status', 'active'));
        $this->get('/laporan/minat?status=inactive')->assertInertia(fn ($page) => $page->where('counts.total', 1)->where('counts.respondents', 1));
        $this->get('/laporan/minat?status=all')->assertInertia(fn ($page) => $page->where('counts.total', 4)->where('counts.respondents', 3)->where('counts.pending', 1));
        $this->get('/laporan/minat?class_name=X%20MPLB%205')->assertInertia(fn ($page) => $page->where('counts.total', 0)->where('counts.respondents', 0)
            ->where('distribution', fn ($rows) => collect($rows)->every(fn ($row) => $row['percentage'] === 0 && $row['primary_percentage'] === 0)));
        $this->get('/laporan/minat?status=unknown')->assertSessionHasErrors('status');
        $this->get('/laporan/minat?class_name=Unknown')->assertSessionHasErrors('class_name');
    }

    public function test_distribution_is_pembina_only_and_empty_cohort_does_not_divide_by_zero(): void
    {
        $this->get('/laporan/minat')->assertRedirect('/login');
        $this->actingAs($this->student->user)->get('/laporan/minat')->assertForbidden();
        $this->actingAs($this->panitia)->get('/laporan/minat')->assertForbidden();
        $this->actingAs($this->pembina)->get('/laporan/minat')->assertInertia(fn ($page) => $page
            ->where('counts.respondents', 0)->where('counts.pending', 3)
            ->where('distribution', fn ($rows) => collect($rows)->every(fn ($row) => $row['percentage'] === 0)));
    }
}
