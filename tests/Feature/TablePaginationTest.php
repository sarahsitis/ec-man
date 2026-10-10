<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TablePaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DemoDataSeeder::class);
        $this->actingAs(User::where('username', 'demo_pembina')->firstOrFail());
    }

    public function test_students_are_loaded_in_pages_with_only_the_allowed_sizes(): void
    {
        foreach ([10, 25, 50] as $size) {
            $this->get('/students?per_page='.$size)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('students.data', $size)->where('students.per_page', $size)->where('students.total', 50));
        }
        $firstIds = [];
        $this->get('/students')->assertInertia(fn (Assert $page) => $page->where('students.per_page', 10)
            ->where('students.data', function ($rows) use (&$firstIds) { $firstIds = collect($rows)->pluck('id')->all(); return count($firstIds) === 10; }));
        $this->get('/students?page=2')->assertInertia(fn (Assert $page) => $page
            ->where('students.from', 11)->where('students.to', 20)
            ->where('students.data', fn ($rows) => count(array_intersect($firstIds, collect($rows)->pluck('id')->all())) === 0));
    }

    public function test_search_filters_and_size_are_kept_in_page_links_and_empty_results(): void
    {
        $this->get('/students?status=active&search=Demo&per_page=10')->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Demo')->where('filters.status', 'active')
            ->where('students.next_page_url', fn ($url) => str_contains($url, 'per_page=10') && str_contains($url, 'status=active') && str_contains($url, 'search=Demo')));
        $this->get('/students?search=does-not-exist&per_page=25')->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 0)->where('students.total', 0)->where('students.from', null)->where('students.to', null)->where('students.per_page', 25));
    }

    public function test_common_server_lists_accept_sizes_and_reject_unbounded_sizes(): void
    {
        foreach (['/students' => 'students', '/keanggotaan' => 'memberships', '/kegiatan' => 'activities',
            '/pre-test/hasil' => 'students', '/penilaian' => 'assignments', '/pemeriksaan-rekomendasi' => 'assignments', '/tahun-ajaran' => 'years'] as $url => $prop) {
            $this->get($url.'?per_page=25')->assertOk()->assertInertia(fn (Assert $page) => $page->where($prop.'.per_page', 25));
            $this->getJson($url.'?per_page=100000')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        }
        foreach (['0', '-1', '20', 'text', '10.5', ''] as $size) {
            $this->getJson('/students?per_page='.$size)->assertUnprocessable()->assertJsonValidationErrors('per_page');
        }
    }

    public function test_pagination_does_not_expand_student_access(): void
    {
        $student = Student::whereHas('user', fn ($q) => $q->where('username', 'demo050'))->firstOrFail();
        $this->actingAs($student->user)->get('/students?per_page=50')->assertForbidden();
        $this->get('/perkembangan-saya?per_page=25')->assertOk()->assertInertia(fn (Assert $page) => $page->where('grades.per_page', 25));
    }
}
