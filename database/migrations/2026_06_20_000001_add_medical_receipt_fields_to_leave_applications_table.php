<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('medical_receipt_path')->nullable()->after('commutation');
            $table->string('medical_receipt_name')->nullable()->after('medical_receipt_path');
            $table->string('medical_receipt_mime')->nullable()->after('medical_receipt_name');
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn([
                'medical_receipt_path',
                'medical_receipt_name',
                'medical_receipt_mime',
            ]);
        });
    }
};
