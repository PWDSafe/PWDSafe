<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_credentials', function (Blueprint $table) {
            $table->string('username', 255)->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }
};
