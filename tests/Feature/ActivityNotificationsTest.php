<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ActivityScheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ActivityNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $pembina;

    private User $panitia;

    private Student $first;

    private Student $second;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-11 12:00:00', 'Asia/Jakarta'));
        $this->pembina = $this->account('pembina', 'pembina');
        $this->year = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true]);
        $this->panitia = $this->account('panitia', 'panitia');
        $committee = $this->member($this->panitia);
        $this->enroll($committee);
        $this->panitia->committeeRoles()->create(['starts_on' => '2026-10-01', 'ends_on' => '2026-12-31']);
        $this->first = $this->member($this->account('first', 'siswa'));
        $this->second = $this->member($this->account('second', 'siswa'));
        $this->enroll($this->first);
        $this->enroll($this->second);
    }

    private function account(string $name, string $role): User
    {
        return User::create(['name' => $name, 'username' => $name, 'role' => $role, 'password' => 'test-password']);
    }

    private function member(User $user, string $status = 'active'): Student
    {
        return Student::create(['user_id' => $user->id, 'student_number' => '100'.$user->id, 'full_name' => $user->name, 'class_name' => 'XI RPL 1', 'status' => $status, 'joined_year' => 2026]);
    }

    private function enroll(Student $student, string $status = 'active', ?AcademicYear $year = null): void
    {
        Membership::create(['student_id' => $student->id, 'academic_year_id' => ($year ?? $this->year)->id, 'class_name' => $student->class_name, 'status' => $status]);
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['academic_year_id' => $this->year->id, 'title' => 'Latihan percakapan', 'category' => 'Percakapan', 'activity_date' => '2026-10-14', 'start_time' => '15:00', 'end_time' => '16:00', 'location' => 'Ruang English Club', 'status' => 'scheduled'], $overrides);
    }

    private function create(?User $actor = null, array $overrides = []): Activity
    {
        $this->actingAs($actor ?? $this->pembina)->post('/kegiatan', $this->data($overrides))->assertSessionHasNoErrors();

        return Activity::latest('id')->firstOrFail();
    }

    public function test_pembina_notifies_only_active_students_enrolled_in_the_activity_semester(): void
    {
        $outsider = $this->member($this->account('outside', 'siswa'));
        $inactiveMembership = $this->member($this->account('inactive-member', 'siswa'));
        $this->enroll($inactiveMembership, 'inactive');
        foreach (['inactive', 'left', 'alumni'] as $status) {
            $this->enroll($this->member($this->account($status, 'siswa'), $status));
        }
        $next = AcademicYear::create(['name' => '2026/2027', 'semester' => 'genap', 'is_active' => false]);
        $this->enroll($this->member($this->account('other-semester', 'siswa')), 'active', $next);
        $this->enroll($this->member($this->pembina));
        $activity = $this->create(null, ['created_by' => $outsider->user_id, 'recipient_ids' => [$outsider->user_id]]);
        $this->assertSame($this->pembina->id, $activity->created_by);
        $this->assertDatabaseCount('notifications', 3);
        $this->assertEqualsCanonicalizing([$this->panitia->id, $this->first->user_id, $this->second->user_id], DatabaseNotification::pluck('notifiable_id')->all());
        $notification = $this->first->user->notifications()->firstOrFail();
        $this->assertSame(ActivityScheduled::class, $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertSame($activity->id, $notification->data['activity_id']);
        $this->assertSame($this->pembina->name, $notification->data['creator_name']);
        $this->assertSame('2026-10-14', $notification->data['activity_date']);
        $this->assertSame('15:00', $notification->data['start_time']);
        $this->assertSame('Ruang English Club', $notification->data['location']);
    }

    public function test_active_panitia_can_create_a_meeting_and_send_notifications_but_cannot_edit_or_delete_it(): void
    {
        $this->actingAs($this->panitia)->get('/kegiatan/buat')->assertOk()->assertInertia(fn ($page) => $page->component('Activities/Form'));
        $activity = $this->create($this->panitia);
        $this->assertSame($this->panitia->id, $activity->created_by);
        $this->assertDatabaseCount('notifications', 3);
        $this->assertSame($this->panitia->name, $this->first->user->notifications()->firstOrFail()->data['creator_name']);
        $this->get('/kegiatan/'.$activity->id.'/edit')->assertForbidden();
        $this->put('/kegiatan/'.$activity->id, $this->data())->assertForbidden();
        $this->delete('/kegiatan/'.$activity->id)->assertForbidden();
    }

    public function test_students_and_inactive_or_ineligible_panitia_cannot_create_meetings(): void
    {
        $actors = [$this->first->user];
        $this->panitia->committeeRoles()->update(['ends_on' => '2026-10-10']);
        $actors[] = $this->panitia;
        $ineligible = $this->account('ineligible', 'panitia');
        $this->member($ineligible)->update(['class_name' => 'X RPL 1']);
        $ineligible->committeeRoles()->create(['starts_on' => '2026-10-01', 'ends_on' => '2026-12-31']);
        $actors[] = $ineligible;
        foreach ($actors as $actor) {
            $this->actingAs($actor)->get('/kegiatan/buat')->assertForbidden();
            $this->post('/kegiatan', $this->data())->assertForbidden();
        }
        $this->assertDatabaseCount('activities', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_invalid_and_non_scheduled_meetings_do_not_send_notifications(): void
    {
        $this->actingAs($this->pembina)->post('/kegiatan', $this->data(['activity_date' => 'invalid', 'end_time' => '14:00']))->assertSessionHasErrors(['activity_date', 'end_time']);
        $this->assertDatabaseCount('activities', 0);
        foreach (['completed', 'cancelled'] as $status) {
            $this->create(null, ['status' => $status]);
        }
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_delivery_covers_large_membership_lists_without_duplicates(): void
    {
        $password = $this->first->user->password;
        DB::table('users')->insert(array_map(fn ($i) => ['name' => 'Bulk '.$i, 'username' => 'bulk-'.$i, 'role' => 'siswa', 'password' => $password], range(1, 205)));
        $users = User::where('username', 'like', 'bulk-%')->get();
        DB::table('students')->insert($users->map(fn ($user) => ['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'class_name' => 'XI RPL 1', 'status' => 'active', 'joined_year' => 2026])->all());
        DB::table('memberships')->insert(Student::whereIn('user_id', $users->pluck('id'))->get()->map(fn ($student) => ['student_id' => $student->id, 'academic_year_id' => $this->year->id, 'class_name' => $student->class_name, 'status' => 'active'])->all());
        $this->create();
        $this->assertDatabaseCount('notifications', 208);
        $this->assertSame(208, DatabaseNotification::distinct()->count('notifiable_id'));
    }

    public function test_notification_views_and_read_actions_are_scoped_to_the_signed_in_user(): void
    {
        $this->create();
        $mine = $this->first->user->notifications()->firstOrFail();
        $other = $this->second->user->notifications()->firstOrFail();
        $this->actingAs($this->first->user)->get('/notifikasi')->assertOk()->assertInertia(fn ($page) => $page->component('Notifications/Index')->has('notifications.data', 1)->where('notifications.data.0.id', $mine->id)->where('notificationSummary.unreadCount', 1));
        $this->get('/notifikasi/ringkasan')->assertOk()->assertExactJson(['unreadCount' => 1])->assertHeader('Cache-Control', 'no-store, private');
        foreach (['baca', 'buka'] as $action) {
            $this->post('/notifikasi/'.$other->id.'/'.$action)->assertNotFound();
        }
        $this->assertNull($other->fresh()->read_at);
        $this->post('/notifikasi/'.$mine->id.'/baca')->assertStatus(303);
        $readAt = $mine->fresh()->read_at;
        $this->travel(5)->minutes();
        $this->post('/notifikasi/'.$mine->id.'/baca')->assertStatus(303);
        $this->assertTrue($readAt->equalTo($mine->fresh()->read_at));
        $this->get('/notifikasi/ringkasan')->assertExactJson(['unreadCount' => 0]);
    }

    public function test_read_all_and_unread_filters_do_not_change_other_accounts(): void
    {
        $this->create();
        $this->create(null, ['title' => 'Latihan debat']);
        $mine = $this->first->user->notifications()->firstOrFail();
        $this->actingAs($this->first->user)->post('/notifikasi/'.$mine->id.'/baca')->assertStatus(303);
        $this->get('/notifikasi?status=unread')->assertInertia(fn ($page) => $page->has('notifications.data', 1)->where('notifications.data.0.read_at', null)->where('filters.status', 'unread'));
        $this->post('/notifikasi/baca-semua')->assertStatus(303);
        $this->get('/notifikasi?status=unread')->assertInertia(fn ($page) => $page->has('notifications.data', 0)->where('notificationSummary.unreadCount', 0));
        $this->assertSame(2, $this->second->user->unreadNotifications()->count());
        $this->post('/notifikasi/baca-semua')->assertStatus(303);
        $this->assertSame(2, $this->second->user->unreadNotifications()->count());
    }

    public function test_opening_a_notification_marks_it_read_and_opens_the_accessible_activity(): void
    {
        $activity = $this->create();
        $item = $this->first->user->notifications()->firstOrFail();
        $this->actingAs($this->first->user)->post('/notifikasi/'.$item->id.'/buka')->assertStatus(303)->assertRedirect('/kegiatan/'.$activity->id);
        $this->assertNotNull($item->fresh()->read_at);
        $this->get('/kegiatan/'.$activity->id)->assertOk();
    }

    public function test_latest_schedule_and_status_are_displayed_without_resending_notifications(): void
    {
        $activity = $this->create();
        $this->put('/kegiatan/'.$activity->id, $this->data(['title' => 'Jadwal baru', 'activity_date' => '2026-10-15', 'start_time' => '16:00', 'end_time' => '17:00', 'location' => 'Aula', 'status' => 'cancelled']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notifications', 3);
        $this->actingAs($this->first->user)->get('/notifikasi')->assertInertia(fn ($page) => $page->where('notifications.data.0.data.title', 'Jadwal baru')->where('notifications.data.0.data.activity_date', '2026-10-15')->where('notifications.data.0.data.start_time', '16:00')->where('notifications.data.0.data.location', 'Aula')->where('notifications.data.0.data.status', 'cancelled')->where('notifications.data.0.activity_available', true));
    }

    public function test_deleted_or_inaccessible_activities_preserve_notifications_without_broken_links(): void
    {
        $activity = $this->create();
        $item = $this->first->user->notifications()->firstOrFail();
        $this->first->memberships()->delete();
        $this->actingAs($this->first->user)->get('/notifikasi')->assertInertia(fn ($page) => $page->where('notifications.data.0.activity_available', false));
        $this->post('/notifikasi/'.$item->id.'/buka')->assertRedirect('/notifikasi');
        $this->actingAs($this->pembina)->delete('/kegiatan/'.$activity->id)->assertSessionHasNoErrors();
        $other = $this->second->user->notifications()->firstOrFail();
        $this->actingAs($this->second->user)->get('/notifikasi')->assertInertia(fn ($page) => $page->where('notifications.data.0.activity_available', false)->where('notifications.data.0.data.title', 'Latihan percakapan'));
        $this->post('/notifikasi/'.$other->id.'/buka')->assertRedirect('/notifikasi');
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_pagination_keeps_notification_order_stable_and_validates_filters(): void
    {
        $activity = $this->create();
        for ($i = 0; $i < 24; $i++) {
            $this->first->user->notify(new ActivityScheduled($activity, $this->pembina));
        }
        $this->actingAs($this->first->user)->get('/notifikasi?per_page=10')->assertInertia(fn ($page) => $page->has('notifications.data', 10)->where('notifications.total', 25)->where('notifications.last_page', 3));
        $firstPage = $this->get('/notifikasi?per_page=10')->viewData('page')['props']['notifications']['data'];
        $secondPage = $this->get('/notifikasi?per_page=10&page=2')->viewData('page')['props']['notifications']['data'];
        $this->assertSame([], array_intersect(array_column($firstPage, 'id'), array_column($secondPage, 'id')));
        $this->get('/notifikasi?status=invalid')->assertSessionHasErrors('status');
    }

    public function test_delivery_failure_rolls_back_the_meeting_and_all_notifications(): void
    {
        $attempts = 0;
        DatabaseNotification::creating(function () use (&$attempts) {
            if (++$attempts === 2) {
                throw new RuntimeException('Simulated notification failure');
            }
        });
        try {
            $this->withoutExceptionHandling()->actingAs($this->pembina)->post('/kegiatan', $this->data());
            $this->fail('Expected a notification failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated notification failure', $e->getMessage());
        } finally {
            DatabaseNotification::flushEventListeners();
        }
        $this->assertSame(2, $attempts);
        $this->assertDatabaseCount('activities', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_guests_cannot_read_or_modify_notifications(): void
    {
        $this->create();
        $item = $this->first->user->notifications()->firstOrFail();
        auth()->logout();
        $this->get('/notifikasi')->assertRedirect('/login');
        $this->get('/notifikasi/ringkasan')->assertRedirect('/login');
        $this->post('/notifikasi/baca-semua')->assertRedirect('/login');
        $this->post('/notifikasi/'.$item->id.'/baca')->assertRedirect('/login');
        $this->post('/notifikasi/'.$item->id.'/buka')->assertRedirect('/login');
        $this->assertNull($item->fresh()->read_at);
    }
}
