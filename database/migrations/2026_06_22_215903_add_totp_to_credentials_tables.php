<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->boolean('has_totp')->default(false)->after('notes');
        });

        Schema::table('encryptedcredentials', function (Blueprint $table) {
            $table->text('totp_secret')->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropColumn('has_totp');
        });

        Schema::table('encryptedcredentials', function (Blueprint $table) {
            $table->dropColumn('totp_secret');
        });
    }
};
