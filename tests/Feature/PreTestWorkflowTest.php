<?php

namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\InterestCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreTestWorkflowTest extends TestCase {
    use RefreshDatabase;

    private User $pembina;
    private User $siswa;
    private Student $student;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutVite();
        $this->pembina = $this->account('admin', 'pembina');
        $this->siswa = $this->account('2001', 'siswa');
        $this->student = $this->member($this->siswa, 'X RPL 1');
    }

    private function account(string $code, string $role): User {
        return User::create(['name' => 'User '.$code, 'username' => $code, 'role' => $role, 'password' => 'test-password']);
    }

    private function member(User $user, string $class): Student {
        return Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'joined_year' => 2026, 'class_name' => $class]);
    }

    private function data(): array {
        $conversation = InterestCategory::where('name', 'Percakapan')->firstOrFail();
        $storytelling = InterestCategory::where('name', 'Storytelling')->firstOrFail();
        return [
            'version' => 'ec-pretest-v1',
            'answers' => ['r1' => 'b', 'r2' => 'c', 'r3' => 'a', 'r4' => 'd', 'r5' => 'b', 'r6' => 'c', 'u1' => 'd', 'u2' => 'a', 'u3' => 'b', 'u4' => 'c', 'u5' => 'd', 'u6' => 'a'],
            'self_assessment' => ['listening' => 1, 'speaking' => 2, 'reading' => 3, 'writing' => 4],
            'interest_ids' => [$conversation->id, $storytelling->id],
            'primary_interest_id' => $storytelling->id,
            'learning_goal' => 'Saya ingin percaya diri bercerita dalam bahasa Inggris.',
        ];
    }

    public function test_student_sees_questions_without_answer_keys_and_guests_cannot_access_pre_test(): void {
        $this->get('/pre-test')->assertRedirect('/login');
        $this->post('/pre-test', $this->data())->assertRedirect('/login');
        $this->get('/pre-test/hasil')->assertRedirect('/login');
        $this->get('/pre-test/siswa/'.$this->student->id)->assertRedirect('/login');
        $this->actingAs($this->siswa)->get('/pre-test')->assertOk()->assertInertia(function ($page) {
            $page->component('PreTests/Index')->where('student.id', $this->student->id)
                ->where('result', null)->has('questions', 12)->has('categories', 6)->has('skills', 4);
            for ($i = 0; $i < 12; $i++) { $page->missing('questions.'.$i.'.correct'); }
        });
    }

    public function test_submission_scores_on_server_and_saves_minat_for_authenticated_student_only(): void {
        $other = $this->member($this->account('2002', 'siswa'), 'X RPL 2');
        $oldCategory = InterestCategory::where('name', 'Debat')->firstOrFail();
        $this->student->interests()->create(['interest_category_id' => $oldCategory->id, 'is_primary' => true, 'learning_goal' => 'Tujuan lama']);
        $data = $this->data();
        $data['answers'] = array_merge($data['answers'], ['r1' => 'a', 'r2' => 'a', 'u1' => 'a', 'u2' => 'b']);
        $data['student_id'] = $other->id;
        $data['score'] = 12;
        $data['level'] = 'Siap pengayaan';
        $this->actingAs($this->siswa)->post('/pre-test', $data)->assertSessionHasNoErrors()->assertRedirect(route('pretests.index'));
        $this->assertDatabaseCount('pre_test_results', 1);
        $this->assertDatabaseHas('pre_test_results', ['student_id' => $this->student->id, 'score' => 8, 'max_score' => 12, 'level' => 'Dasar berkembang']);
        $this->assertDatabaseMissing('pre_test_results', ['student_id' => $other->id]);
        $result = $this->student->fresh()->preTestResult;
        $this->assertSame(4, $result->domain_scores['reading']['score']);
        $this->assertSame(6, $result->domain_scores['reading']['max_score']);
        $this->assertSame(4, $result->domain_scores['language_use']['score']);
        $this->assertSame($data['self_assessment'], $result->self_assessment);
        $this->assertSame($data['answers'], $result->answers);
        $this->assertNotNull($result->submitted_at);
        $this->assertDatabaseCount('student_interests', 2);
        $this->assertDatabaseMissing('student_interests', ['student_id' => $this->student->id, 'interest_category_id' => $oldCategory->id]);
        $this->assertDatabaseHas('student_interests', ['student_id' => $this->student->id, 'interest_category_id' => $data['primary_interest_id'], 'is_primary' => true, 'learning_goal' => $data['learning_goal']]);
        $this->assertSame(1, $this->student->interests()->where('is_primary', true)->count());
        $this->get('/pre-test')->assertInertia(fn ($page) => $page->where('result.score', 8)->has('questions', 0)->missing('result.answers'));
    }

    public static function levels(): array {
        return [[0, 'Perlu pendampingan'], [5, 'Perlu pendampingan'], [6, 'Dasar berkembang'], [8, 'Dasar berkembang'], [9, 'Siap pengayaan'], [12, 'Siap pengayaan']];
    }

    #[DataProvider('levels')]
    public function test_level_thresholds_use_objective_score_only(int $correctCount, string $expectedLevel): void {
        $data = $this->data();
        $wrong = ['r1' => 'a', 'r2' => 'a', 'r3' => 'b', 'r4' => 'a', 'r5' => 'a', 'r6' => 'a', 'u1' => 'a', 'u2' => 'b', 'u3' => 'a', 'u4' => 'a', 'u5' => 'a', 'u6' => 'b'];
        $data['answers'] = array_merge($wrong, array_slice($data['answers'], 0, $correctCount, true));
        $data['self_assessment'] = ['listening' => 4, 'speaking' => 4, 'reading' => 4, 'writing' => 4];
        $this->actingAs($this->siswa)->post('/pre-test', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pre_test_results', ['student_id' => $this->student->id, 'score' => $correctCount, 'level' => $expectedLevel]);
    }

    public function test_invalid_answers_and_incomplete_survey_create_no_result_or_interests(): void {
        $data = $this->data();
        unset($data['answers']['r1'], $data['self_assessment']['speaking']);
        $data['answers']['r2'] = 'z';
        $data['self_assessment']['writing'] = 5;
        $data['interest_ids'] = [];
        $data['learning_goal'] = 'abc';
        $this->actingAs($this->siswa)->post('/pre-test', $data)->assertSessionHasErrors([
            'answers.r1', 'answers.r2', 'self_assessment.speaking', 'self_assessment.writing', 'interest_ids', 'learning_goal',
        ]);
        $data = $this->data();
        $data['answers']['extra'] = 'a';
        $data['self_assessment']['extra'] = 3;
        $data['version'] = 'old-version';
        $this->post('/pre-test', $data)->assertSessionHasErrors(['answers', 'self_assessment', 'version']);
        $this->assertDatabaseCount('pre_test_results', 0);
        $this->assertDatabaseCount('student_interests', 0);
    }

    public function test_interest_ids_must_exist_be_distinct_and_include_primary_interest(): void {
        $data = $this->data();
        $data['primary_interest_id'] = InterestCategory::where('name', 'Debat')->firstOrFail()->id;
        $this->actingAs($this->siswa)->post('/pre-test', $data)->assertSessionHasErrors('primary_interest_id');
        $data = $this->data();
        $data['interest_ids'][] = $data['interest_ids'][0];
        $data['interest_ids'][] = 99999;
        $this->post('/pre-test', $data)->assertSessionHasErrors(['interest_ids.2', 'interest_ids.3']);
        $this->assertDatabaseCount('pre_test_results', 0);
        $this->assertDatabaseCount('student_interests', 0);
    }

    public function test_repeat_submission_cannot_overwrite_baseline_or_interests(): void {
        $data = $this->data();
        $this->actingAs($this->siswa)->post('/pre-test', $data)->assertSessionHasNoErrors();
        $repeat = $data;
        $repeat['answers']['r1'] = 'a';
        $repeat['primary_interest_id'] = $repeat['interest_ids'][0];
        $repeat['learning_goal'] = 'Tujuan baru yang berbeda dari awal.';
        $this->post('/pre-test', $repeat)->assertSessionHasErrors('pre_test');
        $this->assertDatabaseCount('pre_test_results', 1);
        $this->assertDatabaseHas('pre_test_results', ['student_id' => $this->student->id, 'score' => 12, 'learning_goal' => $data['learning_goal']]);
        $this->assertDatabaseHas('student_interests', ['student_id' => $this->student->id, 'interest_category_id' => $data['primary_interest_id'], 'is_primary' => true]);
        InterestCategory::findOrFail($data['primary_interest_id'])->update(['name' => 'Nama diubah']);
        $this->get('/pre-test')->assertInertia(fn ($page) => $page->where('result.interests', fn ($items) => collect($items)->contains(fn ($i) => $i['name'] === 'Storytelling' && $i['is_primary'])));
    }

    public function test_pembina_reports_include_completed_and_pending_students_with_search_and_status_filters(): void {
        $second = $this->member($this->account('2002', 'siswa'), 'X RPL 2');
        $this->actingAs($this->siswa)->post('/pre-test', $this->data())->assertSessionHasNoErrors();
        $this->get('/pre-test/hasil')->assertForbidden();
        $this->actingAs($this->pembina)->get('/pre-test')->assertRedirect(route('pretests.reports'));
        $this->post('/pre-test', $this->data())->assertForbidden();
        $this->get('/pre-test/hasil')->assertOk()->assertInertia(fn ($page) => $page->component('PreTests/Reports')
            ->where('counts.total', 2)->where('counts.completed', 1)->where('counts.pending', 1)->has('students.data', 2));
        $this->get('/pre-test/hasil?status=completed')->assertInertia(fn ($page) => $page->has('students.data', 1)->where('students.data.0.id', $this->student->id)->missing('students.data.0.pre_test_result.answers'));
        $this->get('/pre-test/hasil?status=pending&search=RPL%202')->assertInertia(fn ($page) => $page->has('students.data', 1)->where('students.data.0.id', $second->id)->where('students.data.0.pre_test_result', null));
        $this->get('/pre-test/hasil?search=tidakada')->assertInertia(fn ($page) => $page->has('students.data', 0));
    }

    public function test_only_owner_pembina_and_assigned_panitia_can_view_result(): void {
        $this->actingAs($this->siswa)->post('/pre-test', $this->data())->assertSessionHasNoErrors();
        $url = '/pre-test/siswa/'.$this->student->id;
        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component('PreTests/Show')->where('result.score', 12)->missing('result.answers'));
        $this->actingAs($this->pembina)->get($url)->assertOk();
        $other = $this->account('2002', 'siswa');
        $this->member($other, 'X RPL 2');
        $this->actingAs($other)->get($url)->assertForbidden();
        $panitia = $this->account('1003', 'panitia');
        $this->member($panitia, 'XI RPL 1');
        $this->actingAs($panitia)->get($url)->assertForbidden();
        $this->get('/pre-test/hasil')->assertForbidden();
        AssessmentAssignment::create([
            'student_id' => $this->student->id, 'assessor_id' => $panitia->id, 'created_by' => $this->pembina->id,
            'title' => 'Storytelling 1', 'aspect' => 'speaking', 'rubric' => AssessmentService::rubric('speaking'), 'status' => 'assigned',
        ]);
        $this->get($url)->assertOk();
        $unassigned = $this->member($this->account('2003', 'siswa'), 'X RPL 3');
        $this->get('/pre-test/siswa/'.$unassigned->id)->assertForbidden();
        $panitia->update(['role' => 'siswa']);
        $this->actingAs($panitia->fresh())->get($url)->assertForbidden();
    }

    public function test_panitia_can_take_own_pre_test_and_missing_member_profile_has_helpful_state(): void {
        $panitia = $this->account('1003', 'panitia');
        $this->actingAs($panitia)->get('/pre-test')->assertOk()->assertInertia(fn ($page) => $page->where('student', null));
        $this->post('/pre-test', $this->data())->assertSessionHasErrors('pre_test');
        $member = $this->member($panitia, 'XI RPL 1');
        $this->actingAs($panitia->fresh())->post('/pre-test', $this->data())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pre_test_results', ['student_id' => $member->id, 'score' => 12]);
    }

    public function test_account_with_pre_test_history_cannot_be_deleted(): void {
        $this->actingAs($this->siswa)->post('/pre-test', $this->data())->assertSessionHasNoErrors();
        $this->delete('/profile', ['password' => 'test-password'])->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($this->siswa);
        $this->assertDatabaseCount('pre_test_results', 1);
        $this->assertNotNull($this->siswa->fresh());
    }
}
