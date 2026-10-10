<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityScheme;
use App\Models\CommitteeRole;
use App\Models\Membership;
use App\Models\PreTestResult;
use App\Models\Student;
use App\Models\User;
use App\Services\ClassCatalog;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00', 'Asia/Jakarta'));
    }

    public function test_demo_members_can_login_access_their_schedules_and_populate_reports(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->assertDatabaseCount('students', 50);
        $this->assertDatabaseCount('users', 51);
        $this->assertDatabaseCount('committee_roles', 15);
        $this->assertDatabaseCount('memberships', 50);
        $this->assertDatabaseCount('pre_test_results', 35);
        $this->assertDatabaseCount('student_interests', 81);
        $this->assertDatabaseCount('activities', 9);
        $this->assertDatabaseCount('activity_schemes', 6);
        $this->assertDatabaseCount('scores', 0);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame(15, User::where('role', 'panitia')->count());
        $this->assertSame(35, User::where('role', 'siswa')->count());
        $this->assertSame(15, User::activeCommittee()->count());
        $this->assertTrue(Student::get()->every(fn ($student) => $student->status === 'active' && in_array($student->class_name, ClassCatalog::all(), true)));
        $this->assertTrue(User::where('role', 'panitia')->get()->every(fn ($user) => $user->isPanitia()));
        $this->assertSame(3, PreTestResult::distinct()->count('level'));
        $this->assertSame(3, PreTestResult::min('score'));
        $this->assertSame(12, PreTestResult::max('score'));

        $this->post('/login', ['username' => 'demo001', 'password' => DemoDataSeeder::PASSWORD])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs(User::where('username', 'demo001')->firstOrFail());
        $activity = Activity::orderBy('activity_date')->firstOrFail();
        $this->get('/kegiatan/'.$activity->id)->assertInertia(fn ($page) => $page->has('students', 50)->has('attendances', 0));

        $pending = User::where('username', 'demo050')->firstOrFail();
        $this->actingAs($pending)->get('/pre-test')->assertInertia(fn ($page) => $page->where('result', null)->has('questions', 12));
        $this->get('/kegiatan')->assertInertia(fn ($page) => $page->has('activities.data', 9));
        $this->get('/kegiatan/'.$activity->id)->assertInertia(fn ($page) => $page->has('students', 0)->where('myAttendance', null));

        $completed = User::where('username', 'demo020')->firstOrFail();
        $this->actingAs($completed)->get('/perkembangan-saya')->assertInertia(fn ($page) => $page
            ->has('report.interests', 2)->where('report.interests.0.is_primary', true)->where('report.total_scores', 0)
            ->where('report.skills.0.self_assessment', 1)->where('report.baseline_submitted_at', fn ($value) => $value !== null));
        $this->get('/pre-test')->assertInertia(fn ($page) => $page->has('questions', 0)->missing('result.answers'));

        $this->actingAs(User::where('role', 'pembina')->firstOrFail())->get('/pre-test/hasil')->assertInertia(fn ($page) => $page
            ->where('counts.total', 50)->where('counts.completed', 35)->where('counts.pending', 15));
        $this->get('/laporan/minat')->assertInertia(fn ($page) => $page
            ->where('counts', ['total' => 50, 'respondents' => 35, 'pending' => 15, 'selections' => 81])
            ->has('distribution', 6)->where('distribution', fn ($rows) => collect($rows)->sum('primary_count') === 35));
    }

    public function test_rerun_preserves_existing_records_manual_edits_passwords_and_revocations(): void
    {
        $existing = User::factory()->create(['role' => 'pembina']);
        $activeYear = AcademicYear::create(['name' => '2025/2026', 'semester' => 'genap', 'is_active' => true]);
        $realStudent = Student::create([
            'user_id' => User::factory()->create()->id, 'student_number' => 'real001', 'full_name' => 'Siswa sebelumnya',
            'class_name' => 'X MPLB 1', 'status' => 'active', 'joined_year' => 2026,
        ]);
        $this->seed(DemoDataSeeder::class);
        $this->assertSame(51, Student::count());
        $this->assertTrue($activeYear->fresh()->is_active);
        $demoYear = AcademicYear::where('name', '2026/2027')->firstOrFail();
        $this->assertFalse($demoYear->is_active);
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
        $this->assertSame(50, Membership::where('academic_year_id', $demoYear->id)->count());
        $this->assertSame($existing->id, Activity::firstOrFail()->created_by);
        $this->assertSame('Siswa sebelumnya', $realStudent->fresh()->full_name);
        $this->assertNull($realStudent->fresh()->preTestResult);

        $editedUser = User::where('username', 'demo001')->firstOrFail();
        $editedUser->update(['password' => 'changed-password']);
        $editedUser->student->update(['full_name' => 'Nama telah diubah', 'status' => 'inactive']);
        $editedUser->committeeRoles()->update(['revoked_at' => now(), 'revoked_by' => $existing->id, 'revoke_reason' => 'Perubahan manual']);
        Activity::firstOrFail()->update(['status' => 'cancelled', 'location' => 'Ruang baru']);
        $before = $this->snapshot();
        $this->seed(DemoDataSeeder::class);
        $this->assertSame($before, $this->snapshot());
        $this->assertTrue(Hash::check('changed-password', $editedUser->fresh()->password));
        $this->assertFalse($editedUser->fresh()->isPanitia());
    }

    public static function months(): array
    {
        return [
            'October' => ['2026-10-10', 9, '2026-10-01', '2026-10-29', '2026/2027', 'ganjil'],
            'December rollover' => ['2026-12-10', 10, '2026-12-01', '2026-12-31', '2026/2027', 'ganjil'],
            'January' => ['2027-01-10', 8, '2027-01-05', '2027-01-28', '2026/2027', 'genap'],
            'Leap February' => ['2028-02-10', 9, '2028-02-01', '2028-02-29', '2027/2028', 'genap'],
        ];
    }

    #[DataProvider('months')]
    public function test_monthly_schedule_respects_weekdays_month_boundaries_and_school_semesters(
        string $today, int $count, string $first, string $last, string $yearName, string $semester
    ): void {
        $this->travelTo(Carbon::parse($today.' 12:00:00', 'Asia/Jakarta'));
        $this->seed(DemoDataSeeder::class);
        $activities = Activity::orderBy('activity_date')->get();
        $this->assertCount($count, $activities);
        $this->assertSame($first, $activities->first()->activity_date->toDateString());
        $this->assertSame($last, $activities->last()->activity_date->toDateString());
        $year = AcademicYear::firstOrFail();
        $this->assertSame($yearName, $year->name);
        $this->assertSame($semester, $year->semester);
        $this->assertTrue($year->is_active);
        foreach ($activities as $activity) {
            $this->assertContains($activity->activity_date->dayOfWeek, [Carbon::TUESDAY, Carbon::THURSDAY]);
            $this->assertSame(substr($today, 0, 7), $activity->activity_date->format('Y-m'));
            $this->assertSame('14:30', substr($activity->start_time, 0, 5));
            $this->assertSame('16:00', substr($activity->end_time, 0, 5));
            $this->assertSame($year->id, $activity->academic_year_id);
            $this->assertSame(90, $activity->scheme->duration_minutes);
            $this->assertSame($activity->scheme->agenda, $activity->agenda);
        }
        $month = now('Asia/Jakarta')->startOfMonth();
        foreach (CommitteeRole::get() as $role) {
            $this->assertSame($month->toDateString(), $role->starts_on->toDateString());
            $this->assertSame($month->copy()->endOfMonth()->toDateString(), $role->ends_on->toDateString());
        }
    }

    public function test_next_month_reuses_members_without_overwriting_pretests_or_old_schedules(): void
    {
        $this->seed(DemoDataSeeder::class);
        $pretests = DB::table('pre_test_results')->orderBy('id')->get()->toJson();
        $october = DB::table('activities')->orderBy('id')->get()->toJson();
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', 'Asia/Jakarta'));
        $this->seed(DemoDataSeeder::class);
        $this->assertDatabaseCount('students', 50);
        $this->assertDatabaseCount('users', 51);
        $this->assertDatabaseCount('memberships', 50);
        $this->assertDatabaseCount('pre_test_results', 35);
        $this->assertDatabaseCount('committee_roles', 30);
        $this->assertSame(15, User::activeCommittee()->count());
        $this->assertDatabaseCount('activities', 17);
        $this->assertSame($pretests, DB::table('pre_test_results')->orderBy('id')->get()->toJson());
        $this->assertSame($october, DB::table('activities')->where('activity_date', '<', '2026-11-01')->orderBy('id')->get()->toJson());
    }

    public function test_identifier_collision_rolls_back_the_whole_batch_without_partial_records(): void
    {
        $realUser = User::factory()->create();
        Student::create(['user_id' => $realUser->id, 'student_number' => 'demo025', 'full_name' => 'Siswa sebelumnya', 'joined_year' => 2026]);
        $before = $this->snapshot();
        try {
            $this->seed(DemoDataSeeder::class);
            $this->fail('Expected a student number collision.');
        } catch (QueryException $exception) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['users', 'students', 'academic_years', 'committee_roles', 'memberships', 'interests', 'student_interests', 'pre_test_results', 'activity_schemes', 'activities'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        return $snapshot;
    }
}
