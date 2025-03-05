<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'approved', 'ongoing', 'finished'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('type', ['bug', 'feature', 'update'])->default('bug');
            $table->foreignId('reporter_id')->constrained('pharm_users');
            $table->foreignId('assignee_id')->nullable()->constrained('pharm_users');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pharm_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharm_ticket_id')->constrained();
            $table->foreignId('user_id')->constrained('pharm_users');
            $table->text('comment');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('pharm_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharm_ticket_id')->constrained();
            $table->foreignId('pharm_comment_id')->nullable()->constrained('pharm_ticket_comments');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('pharm_ticket_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharm_ticket_id')->constrained();
            $table->foreignId('user_id')->constrained('pharm_users');
            $table->string('activity'); // e.g., 'status_changed', 'comment_added', 'ticket_created'
            $table->json('properties')->nullable();
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
        Schema::dropIfExists('pharm_ticket_activities');
        Schema::dropIfExists('pharm_ticket_attachments');
        Schema::dropIfExists('pharm_ticket_comments');
        Schema::dropIfExists('pharm_tickets');
    }
}
