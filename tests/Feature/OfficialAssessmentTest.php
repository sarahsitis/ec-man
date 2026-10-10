<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class OfficialAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;
    private User $panitia;
    private Student $first;
    private Student $second;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00', 'Asia/Jakarta'));
        $this->pembina = User::factory()->create(['role' => 'pembina']);
        $this->panitia = User::factory()->create(['name' => 'Panitia EC', 'role' => 'panitia']);
        $this->member($this->panitia, 'Panitia', 'XI PPLG - RPL 1');
        $this->panitia->committeeRoles()->create(['starts_on' => '2026-10-01', 'ends_on' => '2026-12-31', 'appointed_by' => $this->pembina->id]);
        $this->first = $this->member(User::factory()->create(), 'Alpha');
        $this->second = $this->member(User::factory()->create(), 'Beta');
    }

    private function member(User $user, string $name, string $class = 'X PPLG 1'): Student
    {
        return Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $name, 'joined_year' => 2026, 'class_name' => $class, 'status' => 'active']);
    }

    private function delegate(): AssessmentAssignment
    {
        $this->actingAs($this->pembina)->post('/penilaian', [
            'student_ids' => [$this->first->id, $this->second->id], 'assessor_id' => $this->panitia->id,
            'title' => 'Latihan speaking', 'aspect' => 'speaking', 'instructions' => 'Amati percakapan dan catat contoh respons.',
            'created_by' => $this->panitia->id, 'status' => 'approved', 'final_score' => 4,
        ])->assertSessionHasNoErrors();
        return AssessmentAssignment::where('student_id', $this->first->id)->firstOrFail();
    }

    private function submit(AssessmentAssignment $assignment, int $score = 3): void
    {
        $this->actingAs($this->panitia)->post('/penilaian/'.$assignment->id.'/rekomendasi', [
            'action' => 'submit', 'proposed_score' => $score, 'observations' => 'Dapat merespons lawan bicara dengan runtut.',
            'feedback' => 'Latih pelafalan dan variasi kosakata.', 'final_score' => 4, 'reviewed_by' => $this->panitia->id,
        ])->assertSessionHasNoErrors();
    }

    private function approve(AssessmentAssignment $assignment, int $score = 3): void
    {
        $this->actingAs($this->pembina)->post('/penilaian/'.$assignment->id.'/keputusan', ['decision' => 'approve', 'final_score' => $score])->assertSessionHasNoErrors();
    }

    public function test_one_assessment_groups_multiple_student_assignments_and_carries_instructions(): void
    {
        $first = $this->delegate();
        $second = AssessmentAssignment::where('student_id', $this->second->id)->firstOrFail();
        $this->assertDatabaseCount('assessments', 1);
        $this->assertSame($first->assessment_id, $second->assessment_id);
        $this->assertSame($this->pembina->id, $first->assessment->created_by);
        $this->assertSame(AssessmentService::rubric('speaking'), $first->assessment->rubric);
        $this->assertSame('assigned', $first->status);
        $this->assertDatabaseCount('scores', 0);
        $this->actingAs($this->panitia)->get('/penilaian/'.$first->id)->assertInertia(fn ($page) => $page
            ->where('assignment.assessment.instructions', 'Amati percakapan dan catat contoh respons.')
            ->where('assignment.official_score', null)->where('nextPendingId', null));
    }

    public function test_nonactive_targets_reject_the_entire_batch_without_orphan_assessments(): void
    {
        foreach (['inactive', 'left', 'alumni'] as $status) {
            $this->second->update(['status' => $status]);
            $this->actingAs($this->pembina)->post('/penilaian', [
                'student_ids' => [$this->first->id, $this->second->id], 'assessor_id' => $this->panitia->id,
                'title' => 'Latihan speaking', 'aspect' => 'speaking',
            ])->assertSessionHasErrors('student_ids.1');
            $this->get('/penilaian')->assertInertia(fn ($page) => $page->has('students', 2));
        }
        $this->assertDatabaseCount('assessments', 0);
        $this->assertDatabaseCount('assessment_assignments', 0);
        $this->assertDatabaseCount('assessment_events', 0);
    }

    public function test_draft_and_submit_do_not_create_scores_or_allow_panitia_to_approve(): void
    {
        $item = $this->delegate();
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi', ['action' => 'draft', 'proposed_score' => 2])->assertSessionHasNoErrors();
        $this->assertSame('draft', $item->fresh()->status);
        $this->assertDatabaseCount('scores', 0);
        $this->submit($item);
        $this->assertNull($item->fresh()->final_score);
        $this->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'approve', 'final_score' => 4])->assertForbidden();
        $this->assertDatabaseCount('scores', 0);
        $this->actingAs($this->first->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page->has('grades.data', 0));
    }

    public function test_approval_publishes_one_official_score_and_audits_the_score_identifier(): void
    {
        $item = $this->delegate();
        $this->submit($item);
        $this->approve($item);
        $score = Score::firstOrFail();
        $this->assertSame($item->id, $score->assessment_assignment_id);
        $this->assertSame($item->assessment_id, $score->assessment_id);
        $this->assertSame($this->first->id, $score->student_id);
        $this->assertSame(3, $score->value);
        $this->assertSame($this->pembina->id, $score->approved_by);
        $this->assertTrue($score->approved_at->equalTo($item->fresh()->reviewed_at));
        $this->assertSame($score->id, $item->events()->where('action', 'approved')->firstOrFail()->details['score_id']);
        $this->assertNull(AssessmentAssignment::where('student_id', $this->second->id)->firstOrFail()->officialScore);
        $this->get('/penilaian/'.$item->id)->assertInertia(fn ($page) => $page->where('assignment.official_score.value', 3));
        foreach (['approve', 'revise', 'reject'] as $decision) {
            $this->post('/penilaian/'.$item->id.'/keputusan', ['decision' => $decision, 'final_score' => 4, 'review_note' => 'Repeated decision'])->assertStatus(409);
        }
        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseCount('assessment_events', 4);
    }

    public function test_revision_and_rejection_require_notes_and_never_publish_scores(): void
    {
        $item = $this->delegate();
        $this->submit($item);
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'revise'])->assertSessionHasErrors('review_note');
        $this->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'revise', 'review_note' => 'Lengkapi bukti percakapan.'])->assertSessionHasNoErrors();
        $this->assertSame('revision', $item->fresh()->status);
        $this->assertDatabaseCount('scores', 0);
        $this->submit($item, 2);
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'reject', 'review_note' => 'Bukti masih belum sesuai tugas.'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $item->fresh()->status);
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_revised_recommendation_can_be_resubmitted_and_approved_with_adjusted_official_score(): void
    {
        $item = $this->delegate();
        $this->submit($item);
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'revise', 'review_note' => 'Lengkapi contoh respons.'])->assertSessionHasNoErrors();
        $this->travel(5)->minutes();
        $this->submit($item, 2);
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'approve', 'final_score' => 4])->assertSessionHasErrors('review_note');
        $this->assertDatabaseCount('scores', 0);
        $this->post('/penilaian/'.$item->id.'/keputusan', ['decision' => 'approve', 'final_score' => 4, 'review_note' => 'Pengamatan tambahan menunjukkan kemampuan konsisten.'])->assertSessionHasNoErrors();
        $this->assertSame(2, $item->fresh()->proposed_score);
        $this->assertSame(4, $item->fresh()->officialScore->value);
        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseCount('assessment_events', 6);
    }

    public function test_failure_to_publish_score_rolls_back_decision_and_audit_event(): void
    {
        $item = $this->delegate();
        $this->submit($item);
        Score::creating(function () { throw new RuntimeException('Simulated score persistence failure'); });
        try {
            app(AssessmentService::class)->review($item, $this->pembina, ['decision' => 'approve', 'final_score' => 3]);
            $this->fail('Expected the score creation to fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated score persistence failure', $e->getMessage());
        } finally {
            Score::flushEventListeners();
        }
        $this->assertSame('submitted', $item->fresh()->status);
        $this->assertNull($item->fresh()->final_score);
        $this->assertNull($item->fresh()->reviewed_by);
        $this->assertSame(0, $item->events()->where('action', 'approved')->count());
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_progress_reads_only_the_students_official_snapshot_and_hides_internal_data(): void
    {
        $item = $this->delegate();
        $this->submit($item);
        $this->approve($item);
        $item->update(['final_score' => 1, 'feedback' => 'Changed recommendation after publication']);
        $this->actingAs($this->first->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
            ->has('grades.data', 1)->where('grades.data.0.final_score', 3)
            ->where('grades.data.0.feedback', 'Latih pelafalan dan variasi kosakata.')
            ->missing('grades.data.0.proposed_score')->missing('grades.data.0.observations')->missing('grades.data.0.events')
            ->where('grades.data.0.reviewer.name', $this->pembina->name));
        $this->actingAs($this->second->user)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page->has('grades.data', 0));
    }

    public function test_queue_is_fifo_searchable_and_filters_each_review_outcome(): void
    {
        $first = $this->delegate();
        $second = AssessmentAssignment::where('student_id', $this->second->id)->firstOrFail();
        $this->submit($second);
        $this->travel(1)->minutes();
        $this->submit($first);
        $this->actingAs($this->pembina)->get('/pemeriksaan-rekomendasi')->assertInertia(fn ($page) => $page
            ->component('Assessments/Queue')->has('assignments.data', 2)->where('assignments.data.0.id', $second->id)->where('counts.submitted', 2));
        $this->get('/penilaian/'.$second->id)->assertInertia(fn ($page) => $page->where('nextPendingId', $first->id));
        $this->get('/pemeriksaan-rekomendasi?search=Alpha')->assertInertia(fn ($page) => $page->has('assignments.data', 1)->where('assignments.data.0.id', $first->id));
        $this->get('/pemeriksaan-rekomendasi?search=Panitia')->assertInertia(fn ($page) => $page->has('assignments.data', 2));
        $this->approve($second);
        $this->post('/penilaian/'.$first->id.'/keputusan', ['decision' => 'revise', 'review_note' => 'Lengkapi bukti.'])->assertSessionHasNoErrors();
        $this->get('/pemeriksaan-rekomendasi')->assertInertia(fn ($page) => $page->has('assignments.data', 0));
        $this->get('/pemeriksaan-rekomendasi?status=approved')->assertInertia(fn ($page) => $page->has('assignments.data', 1)->where('assignments.data.0.official_score.value', 3));
        $this->get('/pemeriksaan-rekomendasi?status=revision')->assertInertia(fn ($page) => $page->has('assignments.data', 1)->where('assignments.data.0.id', $first->id));
        $this->submit($first);
        $this->actingAs($this->pembina)->post('/penilaian/'.$first->id.'/keputusan', ['decision' => 'reject', 'review_note' => 'Belum sesuai tugas.'])->assertSessionHasNoErrors();
        $this->get('/pemeriksaan-rekomendasi?status=rejected')->assertInertia(fn ($page) => $page->has('assignments.data', 1)->where('assignments.data.0.id', $first->id));
    }

    public function test_queue_is_restricted_to_pembina_and_validates_status(): void
    {
        $this->get('/pemeriksaan-rekomendasi')->assertRedirect('/login');
        $this->actingAs($this->panitia)->get('/pemeriksaan-rekomendasi')->assertForbidden();
        $this->actingAs($this->first->user)->get('/pemeriksaan-rekomendasi')->assertForbidden();
        $this->actingAs($this->pembina)->get('/pemeriksaan-rekomendasi?status=draft')->assertSessionHasErrors('status');
    }

    public function test_creator_of_an_assessment_without_assignments_cannot_delete_account(): void
    {
        Assessment::create(['created_by' => $this->pembina->id, 'title' => 'Legacy task', 'aspect' => 'speaking', 'rubric' => AssessmentService::rubric('speaking')]);
        $this->actingAs($this->pembina)->delete('/profile', ['password' => 'password'])->assertSessionHasErrors('password');
        $this->assertNotNull($this->pembina->fresh());
    }
}
