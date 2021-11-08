<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPixKeyInTransaction extends Migration
{

    public function up()
    {
        Schema::table('transaction', function(Blueprint $table) {
            $table->string('pix_key')->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction',function(Blueprint $table){
			$table->dropColumn('pix_key');
		});
    }
}
