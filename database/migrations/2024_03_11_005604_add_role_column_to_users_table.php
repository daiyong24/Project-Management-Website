<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleColumnToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /* The enum method defines the role column as an ENUM type, 
        allowing only the values 'user', 'author', or 'admin'. 
        Any other value will result in an error. */
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user','author','admin'])->default('user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
