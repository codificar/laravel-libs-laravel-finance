<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBalanceSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function(Blueprint $table)
		{
            DB::statement("INSERT INTO `settings`(`key`, `value`, `category`, `tool_tip`, `page`, `sub_category`, `contact_email`) VALUES ('add_billet_balance_user', 1, 6, 'If 0, billet will disable for user. If 1, enable billet', 0, 0, '');");
            DB::statement("INSERT INTO `settings`(`key`, `value`, `category`, `tool_tip`, `page`, `sub_category`, `contact_email`) VALUES ('add_card_balance_user', 1, 6, 'If 0, add balance with credit card will disable for user. If 1 enable ', 0, 0, '');");
            DB::statement("INSERT INTO `settings`(`key`, `value`, `category`, `tool_tip`, `page`, `sub_category`, `contact_email`) VALUES ('add_billet_balance_provider', 1, 6, 'If 0, billet will disable for provider. If 1, enable billet', 0, 0, '');");
            DB::statement("INSERT INTO `settings`(`key`, `value`, `category`, `tool_tip`, `page`, `sub_category`, `contact_email`) VALUES ('add_card_balance_provider', 1, 6, 'If 0, add balance with credit card will disable for provider. If 1 enable', 0, 0, '');");
            DB::statement("INSERT INTO `settings`(`key`, `value`, `category`, `tool_tip`, `page`, `sub_category`, `contact_email`) VALUES ('add_balance_min', 10, 6, 'Minimun valueto add a balance', 0, 0, '');");
            DB::statement("INSERT INTO `settings`(`key`, `value`, `category`, `tool_tip`, `page`, `sub_category`, `contact_email`) VALUES ('add_balance_billet_tax', 0, 6, 'Tax to create a billet', 0, 0, '');");
        });    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
