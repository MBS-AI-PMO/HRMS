<?php

use App\Models\company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('registration_slug', 191)->nullable()->unique()->after('company_name');
        });

        company::query()->select('id', 'company_name', 'registration_slug')->each(function (company $row) {
            if ($row->registration_slug) {
                return;
            }
            $row->registration_slug = company::makeUniqueRegistrationSlug($row->company_name, (int) $row->id);
            $row->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['registration_slug']);
            $table->dropColumn('registration_slug');
        });
    }
};
