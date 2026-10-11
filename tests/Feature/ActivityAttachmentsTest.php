<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ActivityAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;

    private User $panitia;

    private Student $member;

    private AcademicYear $year;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 11));
        Storage::fake('local');
        $this->pembina = $this->account('pembina', 'pembina');
        $this->panitia = $this->account('panitia', 'panitia');
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $this->member = $this->student($this->account('siswa', 'siswa'));
        Membership::create(['student_id' => $this->member->id, 'academic_year_id' => $this->year->id, 'class_name' => $this->member->class_name, 'status' => 'active']);
        $this->activity = $this->makeActivity();
    }

    private function account(string $name, string $role): User
    {
        $user = User::create(['name' => $name, 'username' => $name, 'role' => $role, 'password' => 'test-password']);
        if ($role === 'panitia') {
            $this->student($user);
            $user->committeeRoles()->create(['starts_on' => '2026-10-01', 'ends_on' => '2026-12-31']);
        }

        return $user;
    }

    private function student(User $user): Student
    {
        return Student::create(['user_id' => $user->id, 'student_number' => '100'.$user->id, 'full_name' => $user->name, 'joined_year' => 2026, 'class_name' => 'XI RPL 1', 'status' => 'active']);
    }

    private function makeActivity(?AcademicYear $year = null): Activity
    {
        return Activity::create(['academic_year_id' => ($year ?? $this->year)->id, 'title' => 'Speaking practice', 'category' => 'Percakapan', 'activity_date' => '2026-10-11', 'start_time' => '15:00', 'status' => 'scheduled', 'created_by' => $this->pembina->id]);
    }

    private function url(?ActivityAttachment $file = null, string $action = '', ?Activity $activity = null): string
    {
        return '/kegiatan/'.($activity ?? $this->activity)->id.'/berkas'.($file ? '/'.$file->id.($action ? '/'.$action : '') : '');
    }

    private function upload(?User $actor = null, string $kind = 'material'): ActivityAttachment
    {
        $file = $kind === 'documentation' ? UploadedFile::fake()->image('foto.jpg') : UploadedFile::fake()->createWithContent('materi.txt', 'English Club speaking practice.');
        $this->actingAs($actor ?? $this->pembina)->post($this->url(), ['kind' => $kind, 'description' => 'Latihan percakapan', 'files' => [$file]])->assertStatus(303)->assertSessionHasNoErrors();

        return ActivityAttachment::latest('id')->firstOrFail();
    }

    public function test_staff_can_upload_multiple_private_files_with_verified_uploader_metadata(): void
    {
        $this->actingAs($this->panitia)->post($this->url(), ['kind' => 'material', 'description' => 'Panduan latihan', 'uploaded_by' => $this->pembina->id, 'uploader_name' => 'Palsu', 'files' => [UploadedFile::fake()->createWithContent('materi.txt', 'Speaking practice guide.'), UploadedFile::fake()->image('latihan.png')]])->assertStatus(303)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('activity_attachments', 2);
        foreach (ActivityAttachment::all() as $file) {
            Storage::disk('local')->assertExists($file->path);
            $this->assertSame($this->panitia->id, $file->uploaded_by);
            $this->assertSame($this->panitia->name, $file->uploader_name);
            $this->assertSame('Panduan latihan', $file->description);
            $this->assertNotSame($file->original_name, basename($file->path));
        }
        $this->get('/kegiatan/'.$this->activity->id)->assertInertia(fn ($page) => $page->component('Activities/Show')->has('attachments', 2)->missing('attachments.0.path')->where('canUploadAttachments', true)->where('attachments.0.can_delete', true)->where('attachmentOptions.maxFileMb', 20)->where('attachmentOptions.maxBatchMb', 30));
        $this->upload($this->pembina, 'documentation');
        $this->get('/kegiatan/'.$this->activity->id)->assertInertia(fn ($page) => $page->where('canUploadAttachments', true));
    }

    public function test_students_can_download_only_accessible_activity_files_and_preview_photos(): void
    {
        $text = $this->upload();
        $photo = $this->upload(null, 'documentation');
        $this->actingAs($this->member->user)->get($this->url($text, 'unduh'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertDownload('materi.txt')->assertStreamedContent('English Club speaking practice.');
        $this->get($this->url($photo, 'pratinjau'))->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $this->get($this->url($text, 'pratinjau'))->assertNotFound();
        $this->get('/kegiatan/'.$this->activity->id)->assertInertia(fn ($page) => $page->has('attachments', 2)->where('canUploadAttachments', false)->where('attachments.0.can_delete', false)->missing('attachments.0.path'));
        $outsider = $this->student($this->account('outsider', 'siswa'));
        foreach (['unduh', 'pratinjau'] as $action) {
            $this->actingAs($outsider->user)->get($this->url($photo, $action))->assertForbidden();
        }
        $next = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $foreign = $this->makeActivity($next);
        $this->actingAs($this->member->user)->get($this->url($photo, 'unduh', $foreign))->assertNotFound();
        $photo->update(['activity_id' => $foreign->id]);
        $this->get($this->url($photo, 'unduh', $foreign))->assertForbidden();
    }

    public function test_historical_participant_keeps_read_access_after_membership_is_removed(): void
    {
        $file = $this->upload();
        Attendance::create(['activity_id' => $this->activity->id, 'student_id' => $this->member->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $this->member->memberships()->delete();
        $this->member->update(['status' => 'alumni']);
        $this->actingAs($this->member->user)->get($this->url($file, 'unduh'))->assertOk();
        $this->post($this->url(), ['kind' => 'material', 'files' => [UploadedFile::fake()->image('foto.jpg')]])->assertForbidden();
        $this->delete($this->url($file))->assertForbidden();
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_expired_panitia_and_students_cannot_upload_or_delete_files(): void
    {
        $file = $this->upload($this->panitia);
        $this->panitia->committeeRoles()->update(['ends_on' => '2026-10-10']);
        Membership::create(['student_id' => $this->panitia->student->id, 'academic_year_id' => $this->year->id, 'status' => 'active']);
        foreach ([$this->panitia, $this->member->user] as $user) {
            $this->actingAs($user)->get('/kegiatan/'.$this->activity->id)->assertInertia(fn ($page) => $page->where('canUploadAttachments', false));
            foreach (['material', 'documentation'] as $kind) {
                $this->post($this->url(), ['kind' => $kind, 'canUploadAttachments' => true, 'is_panitia' => true, 'files' => [UploadedFile::fake()->image('foto.jpg')]])->assertForbidden();
            }
            $this->delete($this->url($file))->assertForbidden();
        }
        $this->assertDatabaseCount('activity_attachments', 1);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_panitia_can_delete_only_own_files_and_pembina_can_delete_any_file(): void
    {
        $own = $this->upload($this->panitia);
        $other = $this->upload($this->pembina);
        $this->actingAs($this->panitia)->delete($this->url($other))->assertForbidden();
        $this->delete($this->url($own, '', $this->makeActivity()))->assertNotFound();
        $this->delete($this->url($own))->assertStatus(303)->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($own->path);
        $this->assertDatabaseMissing('activity_attachments', ['id' => $own->id]);
        $this->actingAs($this->pembina)->delete($this->url($other))->assertStatus(303);
        Storage::disk('local')->assertMissing($other->path);
        $this->assertDatabaseCount('activity_attachments', 0);
    }

    public function test_validation_rejects_invalid_batches_before_saving_any_files(): void
    {
        $this->actingAs($this->pembina);
        foreach ([
            ['kind', ['kind' => ['material'], 'files' => [UploadedFile::fake()->image('foto.jpg')]]],
            ['kind', ['kind' => 'invalid', 'files' => [UploadedFile::fake()->image('foto.jpg')]]],
            ['files', ['kind' => 'material', 'files' => []]],
            ['files', ['kind' => 'material', 'files' => array_map(fn () => UploadedFile::fake()->image('foto.jpg'), range(1, 11))]],
            ['files.0', ['kind' => 'documentation', 'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'Plain text notes.')]]],
            ['files.1', ['kind' => 'material', 'files' => [UploadedFile::fake()->image('photo.jpg'), UploadedFile::fake()->createWithContent('script.php', '<?php echo "test";')]]],
            ['files.0', ['kind' => 'material', 'files' => [UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf')]]],
            ['files', ['kind' => 'material', 'files' => [UploadedFile::fake()->create('a.pdf', 20000, 'application/pdf'), UploadedFile::fake()->create('b.pdf', 15000, 'application/pdf')]]],
            ['description', ['kind' => 'material', 'description' => str_repeat('a', 2001), 'files' => [UploadedFile::fake()->image('foto.jpg')]]],
        ] as [$error, $data]) {
            $this->postJson($this->url(), $data)->assertUnprocessable()->assertJsonValidationErrors($error);
            $this->assertDatabaseCount('activity_attachments', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_storage_is_cleaned_and_batch_rolled_back_when_metadata_save_fails(): void
    {
        $attempts = 0;
        ActivityAttachment::creating(function () use (&$attempts) {
            if (++$attempts === 2) {
                throw new RuntimeException('Simulated metadata failure');
            }
        });
        try {
            $this->withoutExceptionHandling()->actingAs($this->pembina)->post($this->url(), ['kind' => 'material', 'files' => [UploadedFile::fake()->image('one.png'), UploadedFile::fake()->image('two.png')]]);
            $this->fail('Expected the simulated failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated metadata failure', $e->getMessage());
        } finally {
            ActivityAttachment::flushEventListeners();
        }
        $this->assertSame(2, $attempts);
        $this->assertDatabaseCount('activity_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_activity_deletion_cleans_files_but_attendance_preserves_activity_and_files(): void
    {
        $file = $this->upload();
        $this->delete('/kegiatan/'.$this->activity->id)->assertRedirect('/kegiatan')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('activity_attachments', 0);
        Storage::disk('local')->assertMissing($file->path);
        $this->activity = $this->makeActivity();
        $protected = $this->upload(null, 'documentation');
        Attendance::create(['activity_id' => $this->activity->id, 'student_id' => $this->member->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $this->delete('/kegiatan/'.$this->activity->id)->assertSessionHasErrors('activity');
        $this->assertDatabaseHas('activity_attachments', ['id' => $protected->id]);
        Storage::disk('local')->assertExists($protected->path);
    }

    public function test_guest_and_missing_files_are_handled_without_exposing_storage(): void
    {
        $file = $this->upload();
        auth()->logout();
        $this->get($this->url($file, 'unduh'))->assertRedirect('/login');
        $this->get($this->url($file, 'pratinjau'))->assertRedirect('/login');
        $this->post($this->url())->assertRedirect('/login');
        Storage::disk('local')->delete($file->path);
        $this->actingAs($this->pembina)->get($this->url($file, 'unduh'))->assertNotFound();
    }
}
