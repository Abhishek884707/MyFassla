<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone');
            $table->string('contect_person')->nullable();
            $table->string('mark_your_loc')->nullable();
            $table->string('gst_no')->nullable();
            // $table->string('addhar_no')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('image')->nullable();
            $table->string('startup_reg_no')->nullable();
            $table->json('edit_request')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('code')->nullable();
            $table->boolean('active')->default(false);
            $table->integer('type_of_seller')->nullable();
            $table->integer('role_id');
            // $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
