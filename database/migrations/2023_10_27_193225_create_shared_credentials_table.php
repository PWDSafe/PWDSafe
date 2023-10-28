<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('shared_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('site', 255);
            $table->string('username', 255);
            $table->text('notes');
            $table->text('secret');
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('burn_after_read')->default(true);
            $table->dateTime('expire_at');
            $table->timestamps();
        });
    }
};
