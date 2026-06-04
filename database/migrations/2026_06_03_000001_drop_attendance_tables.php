<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_uploads');
    }

    public function down(): void
    {
        Schema::create('attendance_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status')->default('Uploaded');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_upload_id')->constrained('attendance_uploads')->cascadeOnDelete();
            $table->string('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('department')->nullable();
            $table->string('job_type')->nullable();
            $table->string('main_gate')->nullable();
            $table->date('attendance_date')->nullable();
            $table->time('morning_in')->nullable();
            $table->time('morning_out')->nullable();
            $table->time('afternoon_in')->nullable();
            $table->time('afternoon_out')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->json('missing_time_logs')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_tardy')->default(false);
            $table->boolean('is_holiday_present')->default(false);
            $table->timestamps();
        });
    }
};
