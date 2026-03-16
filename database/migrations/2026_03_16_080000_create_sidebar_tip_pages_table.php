<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_tip_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_component')->unique();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('rotation_interval_seconds')->default(60);
            $table->timestamps();
        });

        Schema::create('sidebar_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidebar_tip_page_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_tips');
        Schema::dropIfExists('sidebar_tip_pages');
    }
};
