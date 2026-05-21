<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('PDS_table')) {
            Schema::create('PDS_table', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
                $table->string('filename')->nullable();
                $table->string('filepath')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->string('scan_status')->default('pending');
                $table->timestamp('scanned_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('PDS_table');
    }
};
