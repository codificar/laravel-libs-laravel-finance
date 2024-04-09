<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Transaction;

//to run test: sail artisan make:test postbackPixPagarmeTest
class PostbackPixPagarmeTest extends TestCase
{
    /**
     * Testa a função postbackPixPagarme.
     * Defina a request enviada pelo webhook da pagarme em $webhookData
     * @param RequestPixPagarme
     * 
     */
    public function testPostbackPixPagarme()
    {
        $webhookData = json_decode('{
            "id": "hook_p7WB08VuOc9rJYPG",
            "account": {
              "id": "acc_r5PwOJXsgfEog8Lx",
              "name": "MOVICARE INTERMEDIACAO DE SERVICOS TECNOLOGICOS LTDA (Conta 2)"
            },
            "type": "order.paid",
            "created_at": "2023-11-06T21:34:24.1466495Z",
            "data": {
              "id": "or_agPdRd1UVUwXYQzj",
              "code": "YX74IJI9XN",
              "amount": 1000,
              "currency": "BRL",
              "closed": true,
              "items": [
                {
                  "id": "oi_JMxB40ysYsv9RrLq",
                  "amount": 1000,
                  "code": "169930612962248",
                  "created_at": "2023-11-06T21:28:51.2566667Z",
                  "description": "Serviço prestado",
                  "quantity": 1,
                  "status": "active",
                  "updated_at": "2023-11-06T21:28:51.2566667Z"
                }
              ],
              "customer": {
                "id": "cus_W4o1q8QhGhw2mYQz",
                "name": "Lucas Amorim",
                "email": "lucas.rocha@codificar.com.br",
                "document": "93557043649",
                "type": "individual",
                "delinquent": false,
                "created_at": "2023-08-07T20:46:57.047Z",
                "updated_at": "2023-08-07T20:46:57.047Z",
                "phones": {
                  "home_phone": {
                    "country_code": "55",
                    "number": "999063004",
                    "area_code": "31"
                  }
                },
                "metadata": {}
              },
              "status": "paid",
              "created_at": "2023-11-06T21:28:51.257Z",
              "updated_at": "2023-11-06T21:34:23.9152645Z",
              "closed_at": "2023-11-06T21:28:51.257Z",
              "charges": [
                {
                  "id": "ch_zb4AXr3H0HJ6raJL",
                  "code": "YX74IJI9XN",
                  "gateway_id": "1974396016",
                  "amount": 1000,
                  "paid_amount": 1000,
                  "status": "paid",
                  "currency": "BRL",
                  "payment_method": "pix",
                  "paid_at": "2023-11-06T21:34:19Z",
                  "created_at": "2023-11-06T21:28:51.35Z",
                  "updated_at": "2023-11-06T21:34:23.8990054Z",
                  "pending_cancellation": false,
                  "customer": {
                    "id": "cus_W4o1q8QhGhw2mYQz",
                    "name": "Lucas Amorim",
                    "email": "lucas.rocha@codificar.com.br",
                    "document": "93557043649",
                    "type": "individual",
                    "delinquent": false,
                    "created_at": "2023-08-07T20:46:57.047Z",
                    "updated_at": "2023-08-07T20:46:57.047Z",
                    "phones": {
                      "home_phone": {
                        "country_code": "55",
                        "number": "999063004",
                        "area_code": "31"
                      }
                    },
                    "metadata": {}
                  },
                  "last_transaction": {
                    "transaction_type": "pix",
                    "pix_provider_tid": "1974396016",
                    "qr_code": "00020101021226820014br.gov.bcb.pix2560pix.stone.com.br/pix/v2/82b05bc7-f4ba-4bdd-8793-e78769334b58520400005303986540510.005802BR5925Movicare Intermediacao de6014RIO DE JANEIRO62290525paclonezvev2kkl1floescm2q6304ADDB",
                    "qr_code_url": "https://api.pagar.me/core/v5/transactions/tran_MLNz2w5C0C1Rxe4E/qrcode?payment_method=pix",
                    "end_to_end_id": "E00416968202311062134UJ5IIM2Q8WN",
                    "payer": {
                      "name": "LUCAS ROCHA AMORIM",
                      "document": "***535696**",
                      "document_type": "cpf",
                      "bank_account": {
                        "bank_name": "Banco Inter S.A.",
                        "ispb": "00416968"
                      }
                    },
                    "expires_at": "2023-11-07T21:28:51Z",
                    "id": "tran_DrxO23Ahvh8dE0bX",
                    "gateway_id": "1974396016",
                    "amount": 1000,
                    "status": "paid",
                    "success": true,
                    "created_at": "2023-11-06T21:34:23.8990054Z",
                    "updated_at": "2023-11-06T21:34:23.8990054Z",
                    "gateway_response": {},
                    "antifraud_response": {},
                    "metadata": {},
                    "splits": [
                      {
                        "id": "sr_clonezvcf24wt019to41lut5y",
                        "type": "flat",
                        "amount": 150,
                        "recipient": {
                          "id": "rp_79nR0nkHNU385PdE",
                          "name": "MOVICARE INTERMEDIACAO DE SERVICOS TECNOLOGICOS LTDA (Conta 2)",
                          "document": "34637606000154",
                          "type": "company",
                          "payment_mode": "bank_transfer",
                          "status": "active",
                          "created_at": "2023-01-03T13:52:03.553Z",
                          "updated_at": "2023-01-03T13:52:03.553Z"
                        },
                        "options": {
                          "liable": true,
                          "charge_processing_fee": true,
                          "charge_remainder_fee": true
                        }
                      },
                      {
                        "id": "sr_clonezvcf24wu019ti9fu5zyo",
                        "type": "flat",
                        "amount": 850,
                        "recipient": {
                          "id": "re_clo3fgiq14n7r019tlrx11pxs",
                          "name": "Lucas Amorim",
                          "email": "lucas.rocha@codificar.com.br",
                          "document": "15753569617",
                          "description": "teste",
                          "type": "individual",
                          "payment_mode": "bank_transfer",
                          "status": "active",
                          "created_at": "2023-10-23T21:46:25.543Z",
                          "updated_at": "2023-10-23T21:46:28.43Z"
                        },
                        "options": {
                          "liable": false,
                          "charge_processing_fee": false,
                          "charge_remainder_fee": false
                        }
                      }
                    ]
                  },
                  "metadata": {}
                }
              ],
              "metadata": {}
            }
          }', true);

          if ($webhookData){
            $transaction = new Transaction;
            $transaction->type = Transaction::REQUEST_PRICE;
            $transaction->status = Transaction::WAITING_PAYMENT;
            $transaction->gross_value = number_format($webhookData['data']['amount'] / 100, 2);
            $transaction->net_value = 0.00;
            $transaction->provider_value = number_format($webhookData['data']['charges'][0]['last_transaction']['splits'][1]['amount'] / 100, 2);
            $transaction->gateway_tax_value = 0.00;
            $transaction->gateway_transaction_id = $webhookData['data']['charges'][0]['id'];
            $transaction->split_status = Transaction::SPLIT_WAITING_FUNDS;
            $transaction->pix_base64 = $webhookData['data']['charges'][0]['last_transaction']['qr_code'];
            $transaction->pix_copy_paste = $webhookData['data']['charges'][0]['last_transaction']['qr_code'];
            $transaction->pix_expiration_date_time = Carbon::parse($webhookData['data']['charges'][0]['last_transaction']['expires_at'])->format('Y-m-d H:i:s');
            
            $transaction->save();
          }

          $postbackUrl = "http://localhost/libs/finance/postback/pix/{$transaction->gateway_transaction_id}";
          $response = $this->postJson($postbackUrl, $webhookData);
        

        $transaction = Transaction::where('id', $transaction->id)->first();
        if ($transaction->status == 'paid' && $transaction->split_status == Transaction::SPLIT_PAID) {
            $this->assertTrue(true);
            $transaction->delete();
        } else {
            $this->fail('A transação não está no estado desejado.');
        }

    }
}
