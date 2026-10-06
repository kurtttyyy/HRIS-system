<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->text('missing_documents')->nullable();
            $table->text('document_notice')->nullable();
            $table->timestamp('document_notice_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', fn (Blueprint $table) => $table->dropColumn(['missing_documents', 'document_notice', 'document_notice_at']));
    }
};
