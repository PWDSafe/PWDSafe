<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hex-encoded 32-byte salt for PBKDF2 key derivation.
            // Null means the user still uses the legacy AES-256-CBC privkey format.
            $table->string('privkey_salt', 64)->nullable()->after('privkey');
            $table->boolean('uses_login_hash')->default(false)->after('privkey_salt');
            $table->boolean('vault_configured')->default(false)->after('uses_login_hash');
            $table->boolean('separate_vault_password')->default(false)->after('vault_configured');
            $table->string('login_salt', 64)->nullable()->after('separate_vault_password');
            $table->boolean('is_admin')->default(false)->after('login_salt');
        });

        // Seed existing rows: local users already have a configured vault, LDAP users do not.
        DB::table('users')->where('uses_login_hash', true)->update(['vault_configured' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'privkey_salt',
                'uses_login_hash',
                'vault_configured',
                'separate_vault_password',
                'login_salt',
                'is_admin',
            ]);
        });
    }
};
