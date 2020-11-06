<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class ChangeMenuUrlToLibraryUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("UPDATE `permission` SET `url`='/admin/libs/finance/provider_extract' WHERE `url` = '/admin/provider_extract' ;");
        DB::statement("UPDATE `permission` SET `url`='/corp/libs/finance/financial-report' WHERE `url` = '/corp/financial-report' ;");

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