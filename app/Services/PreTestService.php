<?php

namespace App\Services;

use App\Models\InterestCategory;
use App\Models\PreTestResult;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreTestService {
    public function publicQuestions(): array {
        return array_map(function (array $question) {
            unset($question['correct']);
            return $question;
        }, config('pretest.questions'));
    }

    public function score(array $answers): array {
        $domains = [];
        $score = 0;
        foreach (config('pretest.questions') as $question) {
            $domain = $question['domain'];
            $domains[$domain] ??= ['label' => config('pretest.domains')[$domain], 'score' => 0, 'max_score' => 0];
            $domains[$domain]['max_score']++;
            if ($answers[$question['id']] === $question['correct']) {
                $domains[$domain]['score']++;
                $score++;
            }
        }
        $maxScore = count(config('pretest.questions'));
        $percentage = $score / $maxScore * 100;
        $level = $percentage < 50 ? 'Perlu pendampingan' : ($percentage < 75 ? 'Dasar berkembang' : 'Siap pengayaan');

        return ['score' => $score, 'max_score' => $maxScore, 'level' => $level, 'domain_scores' => $domains];
    }

    public function submit(Student $student, array $data): PreTestResult {
        return DB::transaction(function () use ($student, $data) {
            $member = Student::lockForUpdate()->findOrFail($student->id);
            if ($member->preTestResult()->exists()) {
                throw ValidationException::withMessages(['pre_test' => 'Pre-test sudah dikirim. Hasil awal tidak dapat ditimpa.']);
            }
            $categories = InterestCategory::whereIn('id', $data['interest_ids'])->orderBy('name')->get();
            $interests = $categories->map(fn ($category) => [
                'id' => $category->id, 'name' => $category->name,
                'is_primary' => $category->id === (int) $data['primary_interest_id'],
            ])->all();
            $result = PreTestResult::create(array_merge($this->score($data['answers']), [
                'student_id' => $member->id, 'version' => config('pretest.version'),
                'answers' => $data['answers'],
                'self_assessment' => array_map('intval', $data['self_assessment']),
                'interests' => $interests, 'learning_goal' => $data['learning_goal'], 'submitted_at' => now(),
            ]));

            $member->interests()->whereNotIn('interest_category_id', $data['interest_ids'])->delete();
            foreach ($interests as $interest) {
                $member->interests()->updateOrCreate(['interest_category_id' => $interest['id']], [
                    'is_primary' => $interest['is_primary'], 'learning_goal' => $data['learning_goal'],
                ]);
            }
            return $result;
        });
    }
}
