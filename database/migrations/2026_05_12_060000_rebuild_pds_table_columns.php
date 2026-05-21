<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('PDS_table')) {
            return;
        }

        Schema::table('PDS_table', function (Blueprint $table) {
            $oldColumns = [
                'filename',
                'filepath',
                'mime_type',
                'size',
                'scan_status',
                'scanned_at',
                'created_at',
                'updated_at',
            ];

            foreach ($oldColumns as $column) {
                if (Schema::hasColumn('PDS_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('PDS_table', function (Blueprint $table) {
            if (! Schema::hasColumn('PDS_table', 'surname')) {
                $table->string('surname')->nullable()->after('applicant_id');
            }

            if (! Schema::hasColumn('PDS_table', 'first_name')) {
                $table->string('first_name')->nullable()->after('surname');
            }

            if (! Schema::hasColumn('PDS_table', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('PDS_table', 'name_extension')) {
                $table->string('name_extension')->nullable()->after('middle_name');
            }

            if (! Schema::hasColumn('PDS_table', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('name_extension');
            }

            if (! Schema::hasColumn('PDS_table', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('PDS_table', 'sex')) {
                $table->string('sex')->nullable()->after('place_of_birth');
            }

            if (! Schema::hasColumn('PDS_table', 'civil_status')) {
                $table->string('civil_status')->nullable()->after('sex');
            }

            if (! Schema::hasColumn('PDS_table', 'gsis_id_no')) {
                $table->string('gsis_id_no')->nullable()->after('civil_status');
            }

            if (! Schema::hasColumn('PDS_table', 'gsis_no')) {
                $table->string('gsis_no')->nullable()->after('gsis_id_no');
            }

            if (! Schema::hasColumn('PDS_table', 'pag_ibig_id_no')) {
                $table->string('pag_ibig_id_no')->nullable()->after('gsis_no');
            }

            if (! Schema::hasColumn('PDS_table', 'philhealth_no')) {
                $table->string('philhealth_no')->nullable()->after('pag_ibig_id_no');
            }

            if (! Schema::hasColumn('PDS_table', 'sss_no')) {
                $table->string('sss_no')->nullable()->after('philhealth_no');
            }

            if (! Schema::hasColumn('PDS_table', 'tin_no')) {
                $table->string('tin_no')->nullable()->after('sss_no');
            }

            if (! Schema::hasColumn('PDS_table', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('tin_no');
            }

            if (! Schema::hasColumn('PDS_table', 'zip_code')) {
                $table->string('zip_code')->nullable()->after('permanent_address');
            }

            if (! Schema::hasColumn('PDS_table', 'telephone_no')) {
                $table->string('telephone_no')->nullable()->after('zip_code');
            }

            if (! Schema::hasColumn('PDS_table', 'mobile_no')) {
                $table->string('mobile_no')->nullable()->after('telephone_no');
            }

            if (! Schema::hasColumn('PDS_table', 'email_address')) {
                $table->string('email_address')->nullable()->after('mobile_no');
            }

            if (! Schema::hasColumn('PDS_table', 'elementary')) {
                $table->text('elementary')->nullable()->after('email_address');
            }

            if (! Schema::hasColumn('PDS_table', 'secondary')) {
                $table->text('secondary')->nullable()->after('elementary');
            }

            if (! Schema::hasColumn('PDS_table', 'vocational_trade_course')) {
                $table->text('vocational_trade_course')->nullable()->after('secondary');
            }

            if (! Schema::hasColumn('PDS_table', 'graduate_studies')) {
                $table->text('graduate_studies')->nullable()->after('vocational_trade_course');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('PDS_table')) {
            return;
        }

        Schema::table('PDS_table', function (Blueprint $table) {
            $newColumns = [
                'surname',
                'first_name',
                'middle_name',
                'name_extension',
                'date_of_birth',
                'place_of_birth',
                'sex',
                'civil_status',
                'gsis_id_no',
                'gsis_no',
                'pag_ibig_id_no',
                'philhealth_no',
                'sss_no',
                'tin_no',
                'permanent_address',
                'zip_code',
                'telephone_no',
                'mobile_no',
                'email_address',
                'elementary',
                'secondary',
                'vocational_trade_course',
                'graduate_studies',
            ];

            foreach ($newColumns as $column) {
                if (Schema::hasColumn('PDS_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('PDS_table', function (Blueprint $table) {
            if (! Schema::hasColumn('PDS_table', 'filename')) {
                $table->string('filename')->nullable()->after('applicant_id');
            }

            if (! Schema::hasColumn('PDS_table', 'filepath')) {
                $table->string('filepath')->nullable()->after('filename');
            }

            if (! Schema::hasColumn('PDS_table', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('filepath');
            }

            if (! Schema::hasColumn('PDS_table', 'size')) {
                $table->unsignedBigInteger('size')->nullable()->after('mime_type');
            }

            if (! Schema::hasColumn('PDS_table', 'scan_status')) {
                $table->string('scan_status')->default('pending')->after('size');
            }

            if (! Schema::hasColumn('PDS_table', 'scanned_at')) {
                $table->timestamp('scanned_at')->nullable()->after('scan_status');
            }

            if (! Schema::hasColumn('PDS_table', 'created_at')) {
                $table->timestamps();
            }
        });
    }
};
