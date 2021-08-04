<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLedgerIdTransaction extends Migration
{

    public function up()
    {
        Schema::table('transaction', function(Blueprint $table) {
            $table->integer('ledger_id')->unsigned()->nullable();
			$table->foreign('ledger_id')->references('id')->on('ledger')->onDelete('cascade');
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
			$table->dropColumn('ledger_id');
		});
    }
}
