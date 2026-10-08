<?php
namespace Tests\Feature;

use App\Models\AssessmentAssignment;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentWorkflowTest extends TestCase
{
    use RefreshDatabase;
    private User $pembina;
    private User $panitia;
    private Student $student;
    protected function setUp(): void {
        parent::setUp();
        $this->pembina = $this->account('admin', 'pembina');
        $this->panitia = $this->account('1003', 'panitia');
        $this->member($this->panitia, 'XI RPL 1');
        $this->student = $this->member($this->account('2001', 'siswa'), 'X RPL 1');
    }
    private function account(string $code, string $role): User {
        return User::create(['name' => 'User '.$code, 'username' => $code, 'role' => $role, 'password' => 'test-password']);
    }
    private function member(User $user, string $class): Student {
        return Student::create(['user_id'=>$user->id,'student_number'=>$user->username,'full_name'=>$user->name,'joined_year'=>2026,'class_name'=>$class]);
    }
    private function assignment(): AssessmentAssignment {
        return AssessmentAssignment::create([
            'student_id'=>$this->student->id,'assessor_id'=>$this->panitia->id,'created_by'=>$this->pembina->id,
            'title'=>'Storytelling 1','aspect'=>'speaking','rubric'=>AssessmentService::rubric('speaking'),
            'status'=>'assigned',
        ]);
    }
    private function recommendation(): array {
        return ['action'=>'submit','proposed_score'=>3,'observations'=>'Mampu berbicara dengan runtut.','feedback'=>'Latih pelafalan dan variasi kosakata.'];
    }
    public function test_pembina_creates_assignment_but_self_assessment_is_rejected(): void {
        $data=['student_id'=>$this->student->id,'assessor_id'=>$this->panitia->id,'title'=>'Storytelling 1','aspect'=>'speaking'];
        $this->actingAs($this->pembina)->post('/penilaian',$data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assessment_assignments',['student_id'=>$this->student->id,'status'=>'assigned']);
        $data['student_id']=$this->panitia->student->id;
        $this->post('/penilaian',$data)->assertSessionHasErrors('student_id');
        $this->assertDatabaseCount('assessment_assignments',1);
    }
    public function test_recommendation_is_not_published_until_pembina_approval(): void {
        $item=$this->assignment();
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertSessionHasNoErrors();
        $this->assertSame('submitted',$item->fresh()->status);
        $this->assertNull($item->fresh()->final_score);
        $this->actingAs($this->student->user)->get('/perkembangan-saya')->assertInertia(fn($page)=>$page->component('Progress/Index')->has('grades.data',0));
        $this->get('/penilaian/'.$item->id)->assertForbidden();
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan',['decision'=>'approve','final_score'=>4,'review_note'=>'Kelancaran lebih baik pada pengamatan tambahan.'])->assertSessionHasNoErrors();
        $this->assertSame('approved',$item->fresh()->status);
        $this->assertSame(4,$item->fresh()->final_score);
        $this->assertSame(3,$item->fresh()->proposed_score);
        $this->actingAs($this->student->user)->get('/perkembangan-saya')->assertInertia(fn($page)=>$page->has('grades.data',1)->where('grades.data.0.final_score',4)->missing('grades.data.0.proposed_score')->missing('grades.data.0.observations'));
        $other=$this->account('2002','siswa');
        $this->member($other,'X RPL 2');
        $this->actingAs($other)->get('/perkembangan-saya')->assertInertia(fn($page)=>$page->has('grades.data',0));
    }
    public function test_assigned_panitia_only_can_access_and_score_assigned_students(): void {
        $item=$this->assignment();
        $other=$this->account('1004','panitia');$this->member($other,'XII RPL 1');
        $this->actingAs($other)->get('/penilaian/'.$item->id)->assertForbidden();
        $this->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertForbidden();
        $this->get('/panitia/penugasan')->assertInertia(fn($page)=>$page->has('assignments.data',0));
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/keputusan',['decision'=>'approve','final_score'=>4])->assertForbidden();
        $this->assertNull($item->fresh()->final_score);
    }
    public function test_draft_can_be_incomplete_but_submit_requires_complete_evidence(): void {
        $item=$this->assignment();
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',['action'=>'draft'])->assertSessionHasNoErrors();
        $this->assertSame('draft',$item->fresh()->status);
        $this->post('/penilaian/'.$item->id.'/rekomendasi',['action'=>'submit','proposed_score'=>5])->assertSessionHasErrors(['proposed_score','observations','feedback']);
        $this->assertSame('draft',$item->fresh()->status);
    }
    public function test_submitted_recommendation_is_locked_and_revision_can_be_resubmitted(): void {
        $item=$this->assignment();
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertSessionHasNoErrors();
        $this->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertStatus(409);
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan',['decision'=>'revise','review_note'=>'Tambahkan bukti kemampuan pelafalan.'])->assertSessionHasNoErrors();
        $this->assertSame('revision',$item->fresh()->status);
        $this->assertNull($item->fresh()->final_score);
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertSessionHasNoErrors();
        $this->assertSame('submitted',$item->fresh()->status);
        $this->assertDatabaseCount('assessment_events',3);
    }
    public function test_changed_official_score_requires_reason_and_repeated_approval_is_rejected(): void {
        $item=$this->assignment();
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation());
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/keputusan',['decision'=>'approve','final_score'=>4])->assertSessionHasErrors('review_note');
        $this->assertSame('submitted',$item->fresh()->status);
        $data=['decision'=>'approve','final_score'=>3];
        $this->post('/penilaian/'.$item->id.'/keputusan',$data)->assertSessionHasNoErrors();
        $this->post('/penilaian/'.$item->id.'/keputusan',$data)->assertStatus(409);
        $this->assertDatabaseCount('assessment_assignments',1);
        $this->assertDatabaseCount('assessment_events',2);
    }
    public function test_expired_assignment_requires_deadline_extension(): void {
        $item=$this->assignment();$item->update(['due_date'=>now('Asia/Jakarta')->subDay()->toDateString()]);
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertSessionHasErrors('deadline');
        $this->actingAs($this->pembina)->post('/penilaian/'.$item->id.'/batas-waktu',['due_date'=>now('Asia/Jakarta')->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->actingAs($this->panitia)->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertSessionHasNoErrors();
    }
    public function test_role_revocation_removes_committee_access(): void {
        $item=$this->assignment();$this->panitia->update(['role'=>'siswa']);
        $this->actingAs($this->panitia)->get('/panitia/dashboard')->assertForbidden();
        $this->post('/penilaian/'.$item->id.'/rekomendasi',$this->recommendation())->assertForbidden();
    }
    public function test_duplicate_task_for_same_student_and_aspect_is_rejected(): void {
        $this->assignment();
        $this->actingAs($this->pembina)->post('/penilaian',[
            'student_id'=>$this->student->id,'assessor_id'=>$this->panitia->id,'title'=>'Storytelling 1','aspect'=>'speaking',
        ])->assertSessionHasErrors('title');
        $this->assertDatabaseCount('assessment_assignments',1);
    }
}
