<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->foreignId('appointed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at', 'starts_on', 'ends_on']);
        });

        // Existing appointments remain valid through this calendar semester.
        $today = now('Asia/Jakarta');
        $end = $today->month <= 6 ? $today->copy()->startOfYear()->addMonths(6)->subDay() : $today->copy()->endOfYear();
        DB::table('users')->where('role', 'panitia')->orderBy('id')->chunkById(100, function ($users) use ($today, $end) {
            foreach ($users as $user) {
                DB::table('committee_roles')->insert([
                    'user_id' => $user->id, 'starts_on' => $today->toDateString(), 'ends_on' => $end->toDateString(),
                    'note' => 'Penugasan lama dimigrasikan sampai akhir semester kalender. Pembina dapat menyesuaikan masa tugas.',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_roles');
    }
};
