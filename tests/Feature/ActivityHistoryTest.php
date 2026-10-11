<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;

    private AcademicYear $year;

    private AcademicYear $otherYear;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-11 15:30:00', 'Asia/Jakarta'));
        $this->pembina = $this->account('pembina');
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $this->otherYear = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $this->student = $this->member($this->account('siswa'));
        Membership::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'status' => 'active']);
    }

    private function account(string $role, ?string $name = null): User
    {
        return User::create(['name' => $name ?? $role, 'username' => $name ?? $role, 'role' => $role, 'password' => 'test-password']);
    }

    private function member(User $user): Student
    {
        return Student::create(['user_id' => $user->id, 'student_number' => '100'.$user->id, 'full_name' => $user->name, 'status' => 'active', 'class_name' => 'XI RPL 1', 'joined_year' => 2026]);
    }

    private function activity(array $data = []): Activity
    {
        return Activity::create(array_merge(['academic_year_id' => $this->year->id, 'title' => 'Latihan debat', 'category' => 'Speaking', 'activity_date' => '2026-10-12', 'start_time' => '15:00', 'end_time' => '16:00', 'location' => 'Ruang EC', 'status' => 'scheduled', 'created_by' => $this->pembina->id], $data));
    }

    public function test_upcoming_and_ongoing_are_sorted_by_nearest_schedule_and_do_not_include_past_or_cancelled(): void
    {
        $future = $this->activity();
        $ongoing = $this->activity(['activity_date' => '2026-10-11']);
        $laterToday = $this->activity(['activity_date' => '2026-10-11', 'start_time' => '17:00', 'end_time' => '18:00']);
        $this->activity(['activity_date' => '2026-10-10']);
        $this->activity(['status' => 'completed']);
        $this->activity(['status' => 'cancelled']);
        $this->actingAs($this->pembina)->get('/kegiatan/histori')->assertInertia(fn ($page) => $page
            ->component('Activities/History')->has('activities.data', 3)
            ->where('activities.data.0.id', $ongoing->id)->where('activities.data.0.phase', 'ongoing')
            ->where('activities.data.1.id', $laterToday->id)->where('activities.data.1.phase', 'upcoming')
            ->where('activities.data.2.id', $future->id)->where('counts', ['upcoming' => 3, 'completed' => 1, 'pending' => 1, 'cancelled' => 1])
            ->where('asOf', '2026-10-11T15:30:00+07:00'));
    }

    public function test_end_time_boundaries_and_meetings_without_end_time_use_wib(): void
    {
        $boundary = $this->activity(['activity_date' => '2026-10-11', 'end_time' => '15:30:00']);
        $ended = $this->activity(['activity_date' => '2026-10-11', 'start_time' => '14:00', 'end_time' => '15:29:59']);
        $withoutEnd = $this->activity(['activity_date' => '2026-10-11', 'end_time' => null]);
        $oldWithoutEnd = $this->activity(['activity_date' => '2026-10-10', 'end_time' => null]);
        $this->actingAs($this->pembina)->get('/kegiatan/histori')->assertInertia(fn ($page) => $page
            ->has('activities.data', 2)->where('activities.data.0.id', $boundary->id)->where('activities.data.1.id', $withoutEnd->id));
        $this->get('/kegiatan/histori?period=pending')->assertInertia(fn ($page) => $page
            ->has('activities.data', 2)->where('activities.data.0.id', $ended->id)->where('activities.data.0.phase', 'pending')->where('activities.data.1.id', $oldWithoutEnd->id));
        // Same instant expressed in UTC must produce the same WIB classification.
        $this->travelTo(Carbon::parse('2026-10-11 08:30:00', 'UTC'));
        $this->get('/kegiatan/histori')->assertInertia(fn ($page) => $page->where('counts.upcoming', 2)->where('counts.pending', 2));
        $this->travelTo(Carbon::parse('2026-10-12 00:00:00', 'Asia/Jakarta'));
        $this->get('/kegiatan/histori?period=pending')->assertInertia(fn ($page) => $page->where('counts.pending', 4)->has('activities.data', 4));
        $this->assertSame('scheduled', $ended->fresh()->status);
    }

    public function test_completed_and_cancelled_history_follow_explicit_status_with_newest_first(): void
    {
        $old = $this->activity(['status' => 'completed', 'activity_date' => '2026-09-01']);
        $new = $this->activity(['status' => 'completed', 'activity_date' => '2026-10-01']);
        $cancelled = $this->activity(['status' => 'cancelled']);
        $this->activity(['activity_date' => '2026-09-02']);
        $this->actingAs($this->pembina)->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page
            ->has('activities.data', 2)->where('activities.data.0.id', $new->id)->where('activities.data.0.phase', 'completed')->where('activities.data.1.id', $old->id));
        $this->get('/kegiatan/histori?period=cancelled')->assertInertia(fn ($page) => $page->has('activities.data', 1)->where('activities.data.0.id', $cancelled->id));
    }

    public function test_students_only_see_accessible_semesters_or_their_own_attendance_history(): void
    {
        $enrolled = $this->activity(['status' => 'completed']);
        $attended = $this->activity(['status' => 'completed', 'academic_year_id' => $this->otherYear->id]);
        $this->activity(['status' => 'completed', 'academic_year_id' => $this->otherYear->id]);
        Attendance::create(['activity_id' => $attended->id, 'student_id' => $this->student->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        $otherStudent = $this->member($this->account('siswa', 'other'));
        Attendance::create(['activity_id' => $attended->id, 'student_id' => $otherStudent->id, 'status' => 'sakit', 'recorded_by' => $this->pembina->id]);
        $this->actingAs($this->student->user)->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page
            ->where('counts.completed', 2)->has('activities.data', 2)
            ->where('activities.data.0.id', $attended->id)->where('activities.data.0.my_attendance', 'hadir')
            ->missing('activities.data.0.attendances')->missing('activities.data.0.present_count')
            ->where('activities.data.1.id', $enrolled->id)->where('activities.data.1.my_attendance', null));
        $this->actingAs($this->account('siswa', 'outsider'))->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page->where('counts.completed', 0)->has('activities.data', 0));
        $this->actingAs($this->student->user)->get('/kegiatan/histori?period=completed&academic_year_id='.$this->otherYear->id)->assertInertia(fn ($page) => $page->has('activities.data', 1));
    }

    public function test_pembina_and_active_panitia_see_full_history_and_aggregate_counts(): void
    {
        $activity = $this->activity(['status' => 'completed', 'academic_year_id' => $this->otherYear->id]);
        Attendance::create(['activity_id' => $activity->id, 'student_id' => $this->student->id, 'status' => 'hadir', 'recorded_by' => $this->pembina->id]);
        foreach (['material', 'documentation', 'documentation'] as $index => $kind) {
            $activity->attachments()->create(['kind' => $kind, 'original_name' => 'fixture-'.$index.'.pdf', 'path' => 'fixture-'.$index, 'mime_type' => 'application/pdf', 'size' => 1024, 'uploaded_by' => $this->pembina->id, 'uploader_name' => 'Pembina']);
        }
        $panitia = $this->account('panitia');
        $this->member($panitia);
        $panitia->committeeRoles()->create(['starts_on' => '2026-10-01', 'ends_on' => '2026-12-31']);
        foreach ([$this->pembina, $panitia] as $actor) {
            $this->actingAs($actor)->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page
                ->has('activities.data', 1)->where('activities.data.0.present_count', 1)
                ->where('activities.data.0.material_count', 1)->where('activities.data.0.documentation_count', 2)
                ->where('activities.data.0.creator.name', 'pembina')->missing('activities.data.0.attendances'));
        }
        $panitia->committeeRoles()->update(['ends_on' => '2026-10-10']);
        $this->actingAs($panitia)->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page->has('activities.data', 0));
    }

    public function test_filters_apply_to_all_category_counts_and_date_range_is_inclusive(): void
    {
        $keep = $this->activity(['activity_date' => '2026-10-12']);
        $this->activity(['status' => 'completed', 'activity_date' => '2026-10-14']);
        $this->activity(['status' => 'cancelled', 'activity_date' => '2026-10-13']);
        $this->activity(['activity_date' => '2026-10-15']);
        $this->activity(['academic_year_id' => $this->otherYear->id]);
        $this->activity(['title' => 'Other', 'category' => 'Writing']);
        $this->actingAs($this->pembina)->get('/kegiatan/histori?search=Speaking&date_from=2026-10-12&date_to=2026-10-14&academic_year_id='.$this->year->id)->assertInertia(fn ($page) => $page
            ->where('counts', ['upcoming' => 1, 'completed' => 1, 'pending' => 0, 'cancelled' => 1])
            ->has('activities.data', 1)->where('activities.data.0.id', $keep->id));
        $this->get('/kegiatan/histori?search=debat&date_to=2026-10-12')->assertSessionHasNoErrors()->assertInertia(fn ($page) => $page->where('counts.upcoming', 2));
    }

    public function test_pagination_is_stable_and_preserves_history_filters(): void
    {
        $ids = [];
        for ($i = 0; $i < 26; $i++) {
            $ids[] = $this->activity(['status' => 'completed'])->id;
        }
        $ids = array_reverse($ids);
        $url = '/kegiatan/histori?period=completed&search=debat&per_page=25';
        $this->actingAs($this->pembina)->get($url)->assertInertia(fn ($page) => $page
            ->has('activities.data', 25)->where('activities.total', 26)->where('activities.data.0.id', $ids[0])
            ->where('activities.next_page_url', fn ($url) => str_contains($url, 'period=completed') && str_contains($url, 'search=debat') && str_contains($url, 'per_page=25') && str_contains($url, 'page=2')));
        $this->get($url.'&page=2')->assertInertia(fn ($page) => $page->has('activities.data', 1)->where('activities.data.0.id', $ids[25]));
    }

    public function test_confirmed_completion_and_rescheduling_move_history_categories_without_resending_notifications(): void
    {
        $activity = $this->activity(['activity_date' => '2026-10-10']);
        $this->actingAs($this->pembina)->get('/kegiatan/histori?period=pending')->assertInertia(fn ($page) => $page->where('counts.pending', 1));
        $data = ['academic_year_id' => $this->year->id, 'title' => 'Latihan debat', 'category' => 'Speaking', 'activity_date' => '2026-10-10', 'start_time' => '15:00', 'end_time' => '16:00', 'status' => 'completed'];
        $this->put('/kegiatan/'.$activity->id, $data)->assertSessionHasNoErrors();
        $this->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page->where('counts.pending', 0)->where('counts.completed', 1)->has('activities.data', 1));
        $this->put('/kegiatan/'.$activity->id, array_merge($data, ['status' => 'scheduled', 'activity_date' => '2026-10-15']))->assertSessionHasNoErrors();
        $this->get('/kegiatan/histori')->assertInertia(fn ($page) => $page->where('counts.upcoming', 1)->where('counts.completed', 0));
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_invalid_filters_are_rejected_and_guests_are_redirected(): void
    {
        $this->get('/kegiatan/histori')->assertRedirect('/login');
        $this->actingAs($this->pembina)->get('/kegiatan/histori?period=invalid&academic_year_id=9999&search='.str_repeat('a', 101).'&date_from=invalid&per_page=100')->assertSessionHasErrors(['period', 'academic_year_id', 'search', 'date_from']);
        $this->get('/kegiatan/histori?date_from=2026-10-14&date_to=2026-10-12')->assertSessionHasErrors('date_to');
        $this->get('/kegiatan/histori?per_page=100')->assertSessionHasErrors('per_page');
    }

    private function record(Activity $activity, string $status, ?Student $student = null): Attendance
    {
        return Attendance::create(['activity_id' => $activity->id, 'student_id' => ($student ?? $this->student)->id, 'status' => $status, 'recorded_by' => $this->pembina->id]);
    }

    public function test_student_completed_history_classifies_only_the_students_attendance(): void
    {
        $other = $this->member($this->account('siswa', 'another'));
        foreach (['hadir', 'izin', 'sakit', 'alpa', 'belum_diisi', null] as $status) {
            $activity = $this->activity(['title' => $status ?? 'no-record', 'status' => 'completed']);
            if ($status) {
                $this->record($activity, $status);
            }
            $this->record($activity, $status === 'hadir' ? 'alpa' : 'hadir', $other);
        }
        $this->record($this->activity(), 'hadir');
        $this->record($this->activity(['status' => 'cancelled']), 'alpa');
        $this->record($this->activity(['activity_date' => '2026-10-10']), 'alpa');
        $this->actingAs($this->student->user)->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page
            ->where('filters.period', 'completed')->where('filters.participation', 'all')->has('activities.data', 6)
            ->where('participationCounts', ['all' => 6, 'attended' => 1, 'missed' => 3, 'unrecorded' => 2])
            ->where('activities.data.0.my_participation', 'unrecorded')->where('activities.data.0.my_attendance', null)
            ->where('activities.data.1.my_participation', 'unrecorded')->where('activities.data.2.my_participation', 'missed')
            ->where('activities.data.3.my_participation', 'missed')->where('activities.data.4.my_participation', 'missed')
            ->where('activities.data.5.my_participation', 'attended')->missing('activities.data.0.attendances'));
        foreach (['attended' => 1, 'missed' => 3, 'unrecorded' => 2] as $participation => $total) {
            $this->get('/kegiatan/histori?participation='.$participation.'&student_id='.$other->id)->assertInertia(fn ($page) => $page
                ->has('activities.data', $total)->where('activities.total', $total)->where('counts.completed', 6)
                ->where('activities.data.0.my_participation', $participation)->where('participationCounts.all', 6));
        }
    }

    public function test_participation_filters_respect_access_date_search_and_pagination_and_attendance_corrections(): void
    {
        $first = null;
        for ($i = 0; $i < 12; $i++) {
            $activity = $this->activity(['status' => 'completed', 'activity_date' => '2026-10-10']);
            $record = $this->record($activity, 'alpa');
            $first ??= $record;
        }
        $this->record($this->activity(['status' => 'completed', 'activity_date' => '2026-09-01']), 'alpa');
        $this->record($this->activity(['status' => 'completed', 'title' => 'Writing', 'category' => 'Writing']), 'alpa');
        $hidden = $this->activity(['status' => 'completed', 'academic_year_id' => $this->otherYear->id]);
        $other = $this->member($this->account('siswa', 'outside'));
        $this->record($hidden, 'alpa', $other);
        $url = '/kegiatan/histori?participation=missed&search=debat&date_from=2026-10-01&academic_year_id='.$this->year->id;
        $this->actingAs($this->student->user)->get($url)->assertInertia(fn ($page) => $page
            ->has('activities.data', 10)->where('participationCounts.missed', 12)->where('activities.total', 12)
            ->where('activities.next_page_url', fn ($url) => str_contains($url, 'participation=missed') && str_contains($url, 'date_from=2026-10-01') && str_contains($url, 'search=debat')));
        $this->get($url.'&page=2')->assertInertia(fn ($page) => $page->has('activities.data', 2));
        $first->update(['status' => 'hadir']);
        $this->get($url)->assertInertia(fn ($page) => $page->where('participationCounts.missed', 11)->where('participationCounts.attended', 1)->where('activities.total', 11));
        $this->get(str_replace('participation=missed', 'participation=attended', $url))->assertInertia(fn ($page) => $page
            ->has('activities.data', 1)->where('activities.data.0.id', $first->activity_id)->where('activities.data.0.my_participation', 'attended'));
    }

    public function test_participation_is_only_applied_to_completed_student_history_and_unknown_filters_are_rejected(): void
    {
        $this->activity();
        $this->activity(['status' => 'completed']);
        $this->actingAs($this->student->user)->get('/kegiatan/histori?period=upcoming&participation=missed')->assertInertia(fn ($page) => $page
            ->where('filters.participation', 'all')->has('activities.data', 1)->where('activities.data.0.my_participation', null));
        $this->get('/kegiatan/histori?participation=invalid')->assertSessionHasErrors('participation');
        $this->actingAs($this->pembina)->get('/kegiatan/histori?period=completed&participation=missed')->assertInertia(fn ($page) => $page
            ->where('filters.participation', 'all')->where('participationCounts', null)->has('activities.data', 1)->where('activities.data.0.my_participation', null));
        $this->actingAs($this->account('siswa', 'unregistered'))->get('/kegiatan/histori?participation=unrecorded')->assertInertia(fn ($page) => $page
            ->where('filters.period', 'completed')->has('activities.data', 0)->where('participationCounts', ['all' => 0, 'attended' => 0, 'missed' => 0, 'unrecorded' => 0]));
    }

    public function test_student_history_shows_next_friday_without_attendance_when_upcoming_meetings_exist(): void
    {
        $friday = $this->activity(['title' => 'Debate', 'activity_date' => '2026-10-16', 'start_time' => '13:00', 'end_time' => null]);
        $completed = $this->activity(['status' => 'completed', 'activity_date' => '2026-10-09']);
        $this->record($completed, 'hadir');
        $this->actingAs($this->student->user)->get('/kegiatan/histori')->assertInertia(fn ($page) => $page
            ->where('filters.period', 'upcoming')->has('activities.data', 1)->where('activities.data.0.id', $friday->id)
            ->where('activities.data.0.phase', 'upcoming')->where('activities.data.0.my_attendance', null)
            ->where('activities.data.0.my_participation', null)->where('counts.upcoming', 1));
        $this->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page
            ->where('filters.period', 'completed')->has('activities.data', 1)->where('activities.data.0.id', $completed->id)->where('counts.upcoming', 1));
        $this->get('/kegiatan/histori?participation=attended')->assertInertia(fn ($page) => $page
            ->where('filters.period', 'completed')->where('filters.participation', 'attended')->where('activities.data.0.id', $completed->id));
        $this->get('/kegiatan/histori?search=Debate')->assertInertia(fn ($page) => $page->where('filters.period', 'upcoming')->where('activities.data.0.id', $friday->id));
    }

    public function test_default_history_falls_back_to_completed_and_considers_only_visible_filtered_upcoming_meetings(): void
    {
        $past = $this->activity(['title' => 'Past', 'status' => 'completed', 'activity_date' => '2026-10-09']);
        $this->activity(['academic_year_id' => $this->otherYear->id]);
        $this->actingAs($this->student->user)->get('/kegiatan/histori')->assertInertia(fn ($page) => $page
            ->where('filters.period', 'completed')->has('activities.data', 1)->where('activities.data.0.id', $past->id)->where('counts.upcoming', 0));
        $this->activity();
        $this->get('/kegiatan/histori?date_to=2026-10-09')->assertInertia(fn ($page) => $page->where('filters.period', 'completed')->has('activities.data', 1));
        $this->get('/kegiatan/histori?search=Past')->assertInertia(fn ($page) => $page->where('filters.period', 'completed')->has('activities.data', 1));
        $this->get('/kegiatan/histori?period=completed')->assertInertia(fn ($page) => $page->where('filters.period', 'completed')->has('activities.data', 1));
        $this->get('/kegiatan/histori?period=upcoming&search=Past')->assertInertia(fn ($page) => $page->where('filters.period', 'upcoming')->has('activities.data', 0));
    }
}
