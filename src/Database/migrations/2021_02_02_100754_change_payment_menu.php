<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePaymentMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Permission::updateOrCreate(
            array('url' => '/corp/payment'),
            array(
                'name' => 'Payment', 
                'parent_id' => 6004,
                'is_menu' => 1,
                'url' => '/corp/libs/finance/payment',
                'order' => 203,
                'icon' => 'mdi mdi-currency-usd'
            )
        );
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
