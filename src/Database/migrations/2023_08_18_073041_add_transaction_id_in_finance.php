<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionIdInFinance extends Migration
{

    public function up()
    {
        
        if (!Schema::hasColumn('finance', 'transaction_id')) {
            Schema::table('finance', function (Blueprint $table) {
                $table->unsignedInteger('transaction_id')
                    ->default(null)
                    ->nullable();
                $table->foreign('transaction_id')
                    ->references('id')
                    ->on('transaction');
                $table->unique(['transaction_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance',function(Blueprint $table){
			$table->dropConstrainedForeignId('transaction_id');
            $table->dropUnique(['transaction_id']);
		});
    }
}
