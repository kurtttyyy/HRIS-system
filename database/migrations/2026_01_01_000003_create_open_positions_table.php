<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('open_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('employment');
            $table->string('work_mode');
            $table->text('job_description');
            $table->text('responsibilities');
            $table->text('requirements');
            // $table->string('min_salary');
            // $table->string('max_salary');
            $table->string('experience_level');
            $table->string('location');
            $table->string('skills');
            $table->string('benifits');
            $table->string('job_type');
            $table->timestamp('one')->nullable();
            $table->timestamp('two')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_positions');
    }
};
