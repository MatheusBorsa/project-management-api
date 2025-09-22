<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ArtStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('art', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id');
            $table->string('title')->nullable();
            $table->string('art_path');
            $table->string('status')->default(ArtStatus::PENDING->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('art');
    }
};
