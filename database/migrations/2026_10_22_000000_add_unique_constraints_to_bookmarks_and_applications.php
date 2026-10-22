<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateBookmarksExist = DB::table('job_user_bookmarks')
            ->select('user_id', 'job_id')
            ->groupBy('user_id', 'job_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicateApplicationsExist = DB::table('applicants')
            ->select('user_id', 'job_id')
            ->groupBy('user_id', 'job_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateBookmarksExist || $duplicateApplicationsExist) {
            throw new RuntimeException(
                'Duplicate bookmarks or applications exist. Resolve duplicate user/job pairs before running this migration.'
            );
        }

        Schema::table('job_user_bookmarks', function (Blueprint $table): void {
            $table->unique(['user_id', 'job_id'], 'job_user_bookmarks_user_job_unique');
        });

        Schema::table('applicants', function (Blueprint $table): void {
            $table->unique(['user_id', 'job_id'], 'applicants_user_job_unique');
        });
    }

    public function down(): void
    {
        Schema::table('job_user_bookmarks', function (Blueprint $table): void {
            $table->dropUnique('job_user_bookmarks_user_job_unique');
        });

        Schema::table('applicants', function (Blueprint $table): void {
            $table->dropUnique('applicants_user_job_unique');
        });
    }
};
