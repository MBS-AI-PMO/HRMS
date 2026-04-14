<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropColumn('min_experience');
            $table->foreignId('job_experience_id')->after('gender')->default(1)
                  ->foreign('job_experience_id', 'job_posts_job_experience_id_foreign')->references('id')->on('job_experiences');
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropForeign('job_posts_job_experience_id_foreign');
            $table->dropColumn('job_experience_id');
            $table->string('min_experience', 191)->after('gender');
        });
    }
};
