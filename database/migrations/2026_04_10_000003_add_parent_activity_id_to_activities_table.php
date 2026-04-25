<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentActivityIdToActivitiesTable extends Migration
{
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('parent_activity_id')
                ->nullable()
                ->after('user_id')
                ->constrained('activities')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['parent_activity_id']);
            $table->dropColumn('parent_activity_id');
        });
    }
}
