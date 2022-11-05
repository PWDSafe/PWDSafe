<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEncryptedcredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('encryptedcredentials', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('credentialid');
            $table->integer('userid');
            $table->text('data', 65535);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('encryptedcredentials');
    }
}
