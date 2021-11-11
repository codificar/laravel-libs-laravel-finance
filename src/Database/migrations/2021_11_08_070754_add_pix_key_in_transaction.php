<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPixKeyInTransaction extends Migration
{

    public function up()
    {
        Schema::table('transaction', function(Blueprint $table) {
            $table->text('pix_base64')->nullable();
            $table->text('pix_copy_paste')->nullable();
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
			$table->dropColumn('pix_base64');
            $table->dropColumn('pix_copy_paste');
		});
    }
}
