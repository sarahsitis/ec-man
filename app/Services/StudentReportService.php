<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StudentReportService
{
    public const SKILLS = ['listening' => 'Listening', 'speaking' => 'Speaking', 'reading' => 'Reading', 'writing' => 'Writing'];

    private function scores(?Student $student, array $filters = []): Builder
    {
        $query = Score::where('student_id', $student?->id ?? 0);
        if (!empty($filters['from'])) {
            $query->where('approved_at', '>=', Carbon::createFromFormat('Y-m-d', $filters['from'], 'Asia/Jakarta')->startOfDay()->utc());
        }
        if (!empty($filters['until'])) {
            $query->where('approved_at', '<=', Carbon::createFromFormat('Y-m-d', $filters['until'], 'Asia/Jakarta')->endOfDay()->utc());
        }
        return $query;
    }

    public function summary(?Student $student, array $filters = []): array
    {
        $query = $this->scores($student, $filters);
        $groups = (clone $query)->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->select('assessments.aspect')->selectRaw('count(*) as total, avg(scores.value) as average')
            ->groupBy('assessments.aspect')->get()->keyBy('aspect');
        $baseline = $student?->preTestResult;
        $skills = [];
        foreach (self::SKILLS as $aspect => $label) {
            $group = $groups->get($aspect);
            $latest = $group ? (clone $query)->whereHas('assessment', fn ($q) => $q->where('aspect', $aspect))
                ->orderByDesc('approved_at')->orderByDesc('id')->value('value') : null;
            $skills[] = [
                'aspect' => $aspect, 'label' => $label, 'count' => (int) ($group?->total ?? 0),
                'average' => $group ? round((float) $group->average, 2) : null,
                'latest' => $latest === null ? null : (int) $latest,
                'self_assessment' => isset($baseline?->self_assessment[$aspect]) ? (int) $baseline->self_assessment[$aspect] : null,
            ];
        }
        $interests = $student?->interests()->with('category:id,name')->orderByDesc('is_primary')->orderBy('interest_category_id')->get();
        $average = (clone $query)->avg('value');
        $lastApproved = (clone $query)->max('approved_at');
        return [
            'skills' => $skills, 'total_scores' => (clone $query)->count(),
            'graded_aspects' => collect($skills)->where('count', '>', 0)->count(),
            'overall_average' => $average === null ? null : round((float) $average, 2),
            'last_approved_at' => $lastApproved ? Carbon::parse($lastApproved, config('app.timezone'))->toISOString() : null,
            'baseline_submitted_at' => $baseline?->submitted_at?->toISOString(),
            'interests' => $interests?->map(fn ($interest) => ['id' => $interest->interest_category_id, 'name' => $interest->category->name, 'is_primary' => $interest->is_primary])->all() ?? [],
            'learning_goal' => $baseline?->learning_goal ?? $interests?->firstWhere('is_primary', true)?->learning_goal,
        ];
    }

    public function grades(?Student $student, array $filters = [])
    {
        return $this->scores($student, $filters)->with(['assessment:id,title,aspect', 'approver:id,name'])
            ->orderByDesc('approved_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($score) => [
                'id' => $score->id, 'title' => $score->assessment->title, 'aspect' => $score->assessment->aspect,
                'final_score' => $score->value, 'feedback' => $score->feedback, 'review_note' => $score->review_note,
                'reviewed_at' => $score->approved_at?->toISOString(), 'reviewer' => $score->approver?->only(['id', 'name']),
            ]);
    }
}
