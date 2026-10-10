<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::rename('interest_categories', 'interests'); }
    public function down(): void { Schema::rename('interests', 'interest_categories'); }
};
