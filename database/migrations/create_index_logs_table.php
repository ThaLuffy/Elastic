<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndexLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('index_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('type');
            $table->string('index');
            $table->string('model');
            $table->string('document_id');
            $table->string('job_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('indexing_logs');
    }
}
