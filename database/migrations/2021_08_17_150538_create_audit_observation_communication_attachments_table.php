<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditObservationCommunicationAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_observation_communication_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('communication_id');
            $table->string('file_name');
            $table->string('file_location');
            $table->string('file_url')->nullable();
            $table->string('file_type');
            $table->string('tag')->nullable();
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
        Schema::dropIfExists('audit_observation_communication_attachments');
    }
}
