<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemTicketCommentRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('worker')->create('system_ticket_comment_replies', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('system_ticket_comment_id');
            $table->text('comment');
            $table->text('attachments')->nullable();
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
        Schema::connection('worker')->dropIfExists('system_ticket_comment_replies');
    }
}
