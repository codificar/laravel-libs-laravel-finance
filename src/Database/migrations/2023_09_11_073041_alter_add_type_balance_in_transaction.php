<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddTypeBalanceInTransaction extends Migration
{

    public function up()
    {
        DB::statement("ALTER TABLE `transaction` CHANGE `type` `type` ENUM('base_tax','cancel_tax','request_price','check_limit_transaction','request_single_transaction','subscription_transaction','balance_add_transaction') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `transaction` CHANGE `type` `type` ENUM('base_tax','cancel_tax','request_price','check_limit_transaction','request_single_transaction','subscription_transaction') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT NULL;");
    }
}
