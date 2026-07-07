<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'client_id')) {
            DB::table('employees')
                ->whereNotNull('client_id')
                ->update(['company_id' => null]);
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if ($this->foreignKeyExists('employees', 'employees_company_id_foreign')) {
                    $table->dropForeign('employees_company_id_foreign');
                }
            });

            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('company_id', 'employees_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'parent_company_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_company_id')->nullable()->after('company_name');
                $table->foreign('parent_company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'parent_company_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropForeign(['parent_company_id']);
                $table->dropColumn('parent_company_id');
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if ($this->foreignKeyExists('employees', 'employees_company_id_foreign')) {
                    $table->dropForeign('employees_company_id_foreign');
                }
            });

            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('company_id', 'employees_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');
            });
        }
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$database, $table, $foreignKey, 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
