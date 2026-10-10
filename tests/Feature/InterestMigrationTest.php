<?php

namespace Tests\Feature;

use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InterestMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_rename_preserves_catalogue_choices_and_foreign_key_links(): void
    {
        $migration = require database_path('migrations/2026_10_10_100000_rename_interest_categories_to_interests.php');
        $migration->down();
        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id, 'student_number' => $user->username, 'full_name' => $user->name, 'joined_year' => 2026]);
        $id = DB::table('interest_categories')->where('name', 'Percakapan')->value('id');
        DB::table('student_interests')->insert(['student_id' => $student->id, 'interest_category_id' => $id, 'is_primary' => true, 'learning_goal' => 'Retained goal', 'created_at' => now(), 'updated_at' => now()]);
        $catalogue = DB::table('interest_categories')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $choices = DB::table('student_interests')->get()->map(fn ($row) => (array) $row)->all();
        $migration->up();
        $this->assertSame($catalogue, DB::table('interests')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($choices, DB::table('student_interests')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame('Percakapan', $student->interests()->firstOrFail()->category->name);
        $this->assertSame($id, InterestCategory::where('name', 'Percakapan')->firstOrFail()->id);
        $this->assertSame($id, Interest::where('name', 'Percakapan')->firstOrFail()->id);
        $this->assertTrue(collect(DB::select("PRAGMA foreign_key_list('student_interests')"))->contains(fn ($key) => $key->table === 'interests'));
        $migration->down();
        $this->assertSame($choices, DB::table('student_interests')->get()->map(fn ($row) => (array) $row)->all());
        $migration->up();
    }
}
