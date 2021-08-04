<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBilletEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('email_template')->insert(
            array(
                'subject'       => 'Boleto',
                'copy_emails'   => 'contato@emaildetestes.com',
                'created_at'    => '2020-12-14 07:02:50',
                'updated_at'    => '2020-12-14 07:02:50',
                'key'           => 'billet_mail',
                'from'          => 'contato@emaildetestes.com',
                'content'       => '<div style="background-color: #ecf0f1;" align="center"><!-- Début en-tête --> <table id="email-penrose-conteneur" style="padding: 20px 0px;" border="0" width="660" cellspacing="0" cellpadding="0" align="center"> <tbody> <tr> <td>&nbsp;</td> </tr> </tbody> </table> <!-- Fin en-tête --> <table id="email-penrose-conteneur" style="border-right: 1px solid #e2e8ea; border-bottom: 1px solid #e2e8ea; border-left: 1px solid #e2e8ea; background-color: #ffffff;" border="0" width="660" cellspacing="0" cellpadding="0" align="center"><!-- Début bloc "mise en avant" --> <tbody> <tr> <td style="background-color: #ffffff;"> <table class="resp-full-table" border="0" width="660" cellspacing="0" cellpadding="0" align="center"> <tbody> <tr> <td class="resp-full-td" style="padding: 20px; text-align: center;" valign="top"><span style="font-size: 25px;"><a style="color: #545454; outline: none; text-decoration: none;" href="http://elou2.versaoemteste.com.br/admin/email_template/edit/{{ web_url() }}/provider/signin">Boleto</a></span></td> </tr> </tbody> </table> </td> </tr> <!-- Début article 1 --> <tr> <td style="border-bottom: 1px solid #e2e8ea;"> <table class="resp-full-table" style="padding: 20px;" border="0" width="660" cellspacing="0" cellpadding="0" align="center"> <tbody> <tr> <td width="100%"> <table class="resp-full-table" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;" border="0" width="100%" cellspacing="0" cellpadding="0" align="right"> <tbody> <tr> <td class="resp-full-td" style="text-align: justify;" valign="top" width="100%"> <p>Vencimento: {{ $vars["expiration"] }}</p> <p>Valor: {{ $vars["billet_value"] }}</p> <p>Boleto: {{ $vars["billet_url"] }}</p> </td> </tr> <tr> <td class="resp-full-td" style="text-align: center;" valign="top" width="100%">&nbsp;</td> </tr> <tr> <td class="resp-full-td" style="text-align: justify;" valign="top" width="100%"> <div style="padding: 10px; font-size: 12px;">&nbsp;</div> </td> </tr> </tbody> </table> </td> </tr> </tbody> </table> </td> </tr> <!-- Fin article 1 --></tbody> </table> <!-- Début footer --><!-- Fin footer --></div>'
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
