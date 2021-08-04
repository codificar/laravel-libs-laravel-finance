<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteFinanceBeforeYear2000 extends Migration
{

    /**
     * delete all finance with compensation_date before year 2000, because some rows is with bug (ex: year 0021 and not 2021)
     */
    public function up()
    {
        DB::statement("DELETE FROM finance WHERE compensation_date < '2000-01-01 00:00:00';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
