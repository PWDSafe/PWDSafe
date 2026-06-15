<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shared_credentials', function (Blueprint $table) {
            $table->renameColumn('site', 'name');
        });

        Schema::table('shared_credentials', function (Blueprint $table) {
            $table->string('url')->nullable()->after('name');
        });

        DB::table('shared_credentials')->update(['url' => DB::raw('name')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shared_credentials', function (Blueprint $table) {
            $table->dropColumn('url');
        });

        Schema::table('shared_credentials', function (Blueprint $table) {
            $table->renameColumn('name', 'site');
        });
    }
};
