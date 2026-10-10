<?php
namespace App\Services;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentEvent;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService {
    public const ASPECTS = ['listening', 'speaking', 'reading', 'writing'];
    public static function rubric(string $aspect): array {
        $tasks = [
            'listening' => 'memahami gagasan utama dan informasi penting dari materi lisan',
            'speaking' => 'menyampaikan pesan lisan yang dapat dipahami dan merespons lawan bicara',
            'reading' => 'menemukan gagasan utama serta informasi pendukung dari teks',
            'writing' => 'menulis pesan yang runtut dan dapat dipahami sesuai tugas',
        ];
        $task = $tasks[$aspect];
        return ['version' => 'internal-v1', 'aspect' => $aspect, 'levels' => [
            '1' => 'Memerlukan banyak bantuan untuk '.$task.'.',
            '2' => 'Mampu '.$task.' dengan bimbingan.',
            '3' => 'Mampu '.$task.' secara mandiri.',
            '4' => 'Mampu '.$task.' secara konsisten pada tugas yang lebih menantang.',
        ]];
    }
    public function event(AssessmentAssignment $assignment, User $actor, string $action, array $details): void {
        AssessmentEvent::create(['assessment_assignment_id' => $assignment->id, 'actor_id' => $actor->id, 'action' => $action, 'details' => $details]);
    }

    public function delegate(User $actor, array $data): void {
        abort_unless($actor->isPembina(), 403);
        DB::transaction(function () use ($actor, $data) {
            $assessor = User::lockForUpdate()->findOrFail($data['assessor_id']);
            if (!$assessor->isPanitia()) {
                throw ValidationException::withMessages(['assessor_id' => 'Pilih panitia siswa aktif kelas XI/XII dengan masa tugas yang masih berlaku.']);
            }
            $studentIds = $data['student_ids'] ?? [$data['student_id']];
            $students = Student::whereIn('id', $studentIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $duplicates = AssessmentAssignment::whereIn('student_id', $studentIds)
                ->where('title', $data['title'])->where('aspect', $data['aspect'])->lockForUpdate()->get()->pluck('student_id')->all();
            $errors = [];
            foreach ($studentIds as $index => $studentId) {
                $key = isset($data['student_ids']) ? 'student_ids.'.$index : 'student_id';
                $student = $students->get($studentId);
                if (!$student || $student->status !== 'active') { $errors[$key] = 'Pilih siswa yang masih aktif.'; }
                elseif ($student->user_id === $assessor->id) { $errors[$key] = 'Panitia tidak boleh menilai dirinya sendiri.'; }
                elseif (in_array((int) $studentId, $duplicates)) {
                    $errors[isset($data['student_ids']) ? $key : 'title'] = 'Penugasan dengan judul dan aspek ini sudah ada untuk '.$student->full_name.'.';
                }
            }
            if ($errors) { throw ValidationException::withMessages($errors); }
            $assessment = Assessment::create([
                'created_by' => $actor->id, 'title' => $data['title'], 'aspect' => $data['aspect'],
                'rubric' => self::rubric($data['aspect']), 'instructions' => $data['instructions'] ?? null,
            ]);
            foreach ($studentIds as $studentId) {
                $assignment = $assessment->assignments()->create([
                    'student_id' => $studentId, 'assessor_id' => $assessor->id, 'created_by' => $actor->id,
                    'title' => $assessment->title, 'aspect' => $assessment->aspect, 'rubric' => $assessment->rubric,
                    'due_date' => $data['due_date'] ?? null, 'status' => 'assigned',
                ]);
                $this->event($assignment, $actor, 'assigned', ['assessment_id' => $assessment->id, 'assessor_id' => $assessor->id, 'student_id' => $studentId]);
            }
        });
    }
    public function recommend(AssessmentAssignment $assignment, User $actor, array $data): void {
        DB::transaction(function () use ($assignment, $actor, $data) {
            $item = AssessmentAssignment::lockForUpdate()->findOrFail($assignment->id);
            abort_unless($actor->isPanitia() && $item->assessor_id === $actor->id && $item->student->user_id !== $actor->id, 403);
            abort_unless($item->editable(), 409, 'Rekomendasi sudah diajukan atau diputuskan.');
            if ($item->expired()) { throw ValidationException::withMessages(['deadline' => 'Batas waktu penilaian telah lewat. Hubungi pembina.']); }
            $submit = $data['action'] === 'submit';
            $item->update([
                'proposed_score' => $data['proposed_score'] ?? null,
                'observations' => $data['observations'] ?? null, 'feedback' => $data['feedback'] ?? null,
                'status' => $submit ? 'submitted' : 'draft', 'submitted_at' => $submit ? now() : null,
            ]);
            $this->event($item, $actor, $submit ? 'submitted' : 'draft', [
                'score' => $item->proposed_score, 'observations' => $item->observations, 'feedback' => $item->feedback,
            ]);
        });
    }
    public function review(AssessmentAssignment $assignment, User $actor, array $data): void {
        DB::transaction(function () use ($assignment, $actor, $data) {
            abort_unless($actor->isPembina(), 403);
            $item = AssessmentAssignment::lockForUpdate()->findOrFail($assignment->id);
            abort_unless($item->status === 'submitted', 409, 'Rekomendasi ini sudah diproses atau belum diajukan.');
            abort_if($item->officialScore()->exists(), 409, 'Penugasan ini sudah memiliki nilai resmi.');
            $approve = $data['decision'] === 'approve';
            if ($approve && (int)$data['final_score'] !== $item->proposed_score && empty($data['review_note'])) {
                throw ValidationException::withMessages(['review_note' => 'Tuliskan alasan perubahan skor.']);
            }
            $status = match ($data['decision']) { 'approve' => 'approved', 'revise' => 'revision', 'reject' => 'rejected' };
            $item->update([
                'status' => $status, 'final_score' => $approve ? $data['final_score'] : null,
                'review_note' => $data['review_note'] ?? null, 'reviewed_by' => $actor->id, 'reviewed_at' => now(),
            ]);
            $score = $approve ? $item->officialScore()->create([
                'assessment_id' => $item->assessment_id, 'student_id' => $item->student_id, 'value' => $item->final_score,
                'feedback' => $item->feedback, 'review_note' => $item->review_note, 'approved_by' => $actor->id, 'approved_at' => $item->reviewed_at,
            ]) : null;
            $this->event($item, $actor, $status, ['proposed_score' => $item->proposed_score, 'final_score' => $item->final_score, 'note' => $item->review_note, 'score_id' => $score?->id]);
        });
    }
}
