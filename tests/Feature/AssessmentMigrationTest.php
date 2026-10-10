<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\AssessmentEvent;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentMigrationTest extends TestCase
{
    // Schema changes must run outside SQLite test transactions to preserve FK-linked records.
    use DatabaseMigrations;

    public function test_migration_preserves_recommendations_events_and_publishes_only_existing_approvals(): void
    {
        $migration = require database_path('migrations/2026_10_10_080000_create_assessments_and_scores_tables.php');
        $migration->down();
        $pembina = User::factory()->create(['role' => 'pembina']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'joined_year' => 2026]);
        $assignments = collect();
        foreach (['assigned', 'draft', 'submitted', 'revision', 'approved', 'rejected'] as $status) {
            // Raw legacy inserts avoid caching the old schema in Eloquent's guarded-column list.
            $id = DB::table('assessment_assignments')->insertGetId([
                'student_id' => $student->id, 'assessor_id' => $panitia->id, 'created_by' => $pembina->id,
                'title' => 'Legacy '.$status, 'aspect' => 'speaking', 'rubric' => json_encode(AssessmentService::rubric('speaking')),
                'status' => $status, 'proposed_score' => 2, 'observations' => 'Original evidence', 'feedback' => 'Original feedback',
                'review_note' => 'Original review note', 'final_score' => 3,
                'reviewed_by' => $pembina->id, 'reviewed_at' => '2026-10-09 08:00:00',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $item = AssessmentAssignment::findOrFail($id);
            AssessmentEvent::create(['assessment_assignment_id' => $item->id, 'actor_id' => $pembina->id, 'action' => $status, 'details' => ['original' => true]]);
            $assignments->push($item);
        }
        $before = DB::table('assessment_assignments')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();
        $events = DB::table('assessment_events')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all();

        $migration->up();

        $this->assertDatabaseCount('assessments', 6);
        $this->assertDatabaseCount('scores', 1);
        $after = DB::table('assessment_assignments')->orderBy('id')->get()->map(function ($item) {
            $data = (array) $item;
            unset($data['assessment_id']);
            return $data;
        })->all();
        $this->assertSame($before, $after);
        $this->assertSame($events, DB::table('assessment_events')->orderBy('id')->get()->map(fn ($item) => (array) $item)->all());
        foreach ($assignments as $item) {
            $assessment = $item->fresh()->assessment;
            $this->assertSame($item->title, $assessment->title);
            $this->assertSame($item->rubric, $assessment->rubric);
        }
        $approved = $assignments->firstWhere('status', 'approved');
        $this->assertDatabaseHas('scores', [
            'assessment_assignment_id' => $approved->id, 'student_id' => $student->id, 'value' => 3,
            'approved_by' => $pembina->id, 'approved_at' => '2026-10-09 08:00:00',
            'feedback' => 'Original feedback', 'review_note' => 'Original review note',
        ]);
        $this->assertNotNull($student->fresh());
        $migration->down();
        $this->assertDatabaseCount('assessment_assignments', 6);
        $this->assertDatabaseCount('assessment_events', 6);
        $migration->up();
        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseCount('assessments', 6);
    }
}
