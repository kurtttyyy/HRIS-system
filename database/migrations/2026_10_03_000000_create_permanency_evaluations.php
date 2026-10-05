<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permanency_evaluation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('notice_days')->default(7);
            $table->json('criteria');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('permanency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_user_id')->index();
            $table->unsignedBigInteger('head_user_id')->index();
            $table->unsignedBigInteger('template_id');
            $table->date('due_date');
            $table->json('ratings');
            $table->decimal('overall_rating', 3, 2);
            $table->string('recommendation');
            $table->text('comments');
            $table->timestamps();
            $table->unique(['employee_user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permanency_evaluations');
        Schema::dropIfExists('permanency_evaluation_templates');
    }
};
