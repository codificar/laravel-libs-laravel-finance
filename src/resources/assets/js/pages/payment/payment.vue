<script>
import axios from "axios";
export default {
  props: [
    "Enviroment",
    "user_balance",
    "user_cards",
    "save_payment_route",
    "request_payment_route",
    "add_new_billet_route",
    "add_new_pix_route",
    "pix_screen_route",
    "financial_report_route",
    "delete_user_card",
    "PrepaidSettings",
    "CurrencySymbol",
    "IframeAddCard"
  ],
  /**
   *
   * Cria todos os arrays de dados, configurações e rótulos para os gráficos grandes e pequenos
   * @tinyCharts
   *       countChart, totalChart, avgChart
   * @bigChart
   *       typesChart, quantitiesChart, valuesChart
   */
  data() {
    return {
      yearDigits: new Date()
        .getFullYear()
        .toString()
        .substring(0, 2),
      yearMaks: `##/${new Date()
        .getFullYear()
        .toString()
        .substring(0, 2)}##`,
      cards_list: "",
      value: "",
      valueBillet: "",
      card: "",
      card_number: "",
      card_holder: "",
      card_cvv: "",
      card_exp: "",
      pay_card: 0,
      prepaid_settings: {},
    };
  },
  computed: {
    card_expiration_month: function() {
      var exp_date = this.card_exp.replace(/\s/g, "").split("/");
      if (exp_date[0]) return exp_date[0];
      else return "";
    },
    card_expiration_year: function() {
      var exp_date = this.card_exp.replace(/\s/g, "").split("/20");
      if (exp_date[1]) {
        return this.yearDigits + exp_date[1];
      } else return "";
    },
  },
  components: {},
  methods: {
    /**
     * @author Gabriel Machado
     *
     * adaptado por @author Hugo Couto
     *
     * Cria um array com todos os dados sincronizados com os arrays de rótulos em dateIntervals
     *
     * @return array com todos os dados organizados para mostrar nos gráficos
     * @param any dateIntervals, intervalos em que serão mostrados os dados do gráfico, dia inicial, mes inicial e ano inicial
     * @param any values, os valores vindo do servidor para organizar no array
     * @param number número de elementos do array
     * @param string nome do índice do array recebido que vai ser adicionado ao array de retorno
     */
    addCard() {
      $("#modal-add-credit-card").modal("show");
      $("#modal-card-selected").modal("hide");
    },

    selectCardPayment() {
      $("#modal-card-selected").modal("show");
    },

    submitNewCard() {
      if (
        !this.card_number ||
        !this.card_holder ||
        !this.card_cvv ||
        !this.card_exp
      )
        return;
      // if(this.card_number.endsWith(" ")) this.card_number = this.card_number.slice(0, -1);
      new Promise((resolve, reject) => {
        axios
          .post(this.save_payment_route, {
            card_number: this.card_number,
            card_holder: this.card_holder,
            card_cvv: this.card_cvv,
            card_cvc: this.card_cvv,
            card_expiration_month: this.card_expiration_month,
            card_expiration_year: this.card_expiration_year,
          })
          .then((response) => {
            if (response.data.success) {
              this.cards_list.push(response.data.data[0]);
              this.$swal({
                title: this.trans("finance.card_added"),
                type: "success",
              });
              this.card_number = "";
              this.card_holder = "";
              this.card_cvv = "";
              this.card_exp = "";
              $("#modal-add-credit-card").modal("hide");
              $("#modal-card-selected").modal("show");
            } else {
              this.showErrorMsg(response.data);
            }
          })
          .catch((error) => {
            console.log(error);
            reject(error);
            this.showErrorMsg(error);
            return false;
          });
      });
    },

    showErrorMsg(errData = null) {
      let titleError;
      if(errData && errData.type)
        titleError = errData.type;
      else if(errData && errData.error) 
        titleError = errData.error;
      else if(errData)
        titleError = errData;
      else 
        titleError = this.trans("finance.card_declined");

      this.$swal({
        title: titleError,
        type: "error",
      });
    },
  
    changeValue() {
      this.$swal({
        title: this.trans("finance.change_value"),
        text: this.trans("finance.insert_value"),
        type: "question",
        input: "number",
        inputValue: this.value ? this.value : "",
        showCancelButton: true,
        inputValidator: (value) => {
          return (
            parseFloat(value) < 0 &&
              parseFloat(this.user_balance) > 0 &&
              this.trans("finance.value_cant_be_lower") + " 0,00" 
          );
        },
      }).then((result) => {
        if (result.value) {
          this.value = result.value;
        }
      });
    },
    requestChargepix() {
      var minValue = 1.50; //pix min value is R$ 1.50 in juno
      if (this.value == 0 || this.value < minValue) {
        this.$swal({
          title: this.trans("finance.value_cant_be_lower") + " " + minValue.toFixed(2),
          type: "error",
        });
        return;
      }
      var totalPix = 0;
      var textMsg = "";
      totalPix = parseFloat(this.value);
      textMsg = this.trans("finance.confirm_create_pix") + ": " + this.CurrencySymbol + totalPix.toFixed(2);

      this.$swal({
        title: this.trans("finance.confirm_payment"),
        text: textMsg,
        type: "question",
        showCancelButton: true,
        confirmButtonText: this.trans("finance.confirm"),
        cancelButtonText: this.trans("finance.cancel"),
      }).then((result) => {
        if (result.value) {
          new Promise((resolve, reject) => {
            axios
              .post(this.add_new_pix_route, {
                value: parseFloat(this.value),
              })
              .then((response) => {
                if (response.data.success) {
                  window.location.href = this.pix_screen_route + "?id=" + response.data.gateway_transaction_id;
                } else {
                  this.showErrorMsg(response.data);
                }
              })
              .catch((error) => {
                console.log(error);
                reject(error);
                this.showErrorMsg("Erro ao gerar Pix");
                return false;
              });
          });
        }
      });
    },
    requestChargeBillet() {
      var minValue = this.prepaid_settings.prepaid_min_billet_value ? parseFloat(this.prepaid_settings.prepaid_min_billet_value) : 0;
      if (this.value == 0 || this.value < minValue) {
        this.$swal({
          title: this.trans("finance.value_cant_be_lower") + " " + minValue.toFixed(2),
          type: "error",
        });
        return;
      }
      var totalBillet = 0;
      var textMsg = "";
      if(this.prepaid_settings.prepaid_tax_billet && parseFloat(this.prepaid_settings.prepaid_tax_billet) > 0) {
        totalBillet = parseFloat(this.value) + parseFloat(this.prepaid_settings.prepaid_tax_billet);
        textMsg = this.trans("finance.tax_value") + ": " + this.CurrencySymbol + this.prepaid_settings.prepaid_tax_billet + ". " + this.trans("finance.total") + ": " + this.CurrencySymbol + totalBillet.toFixed(2);
      } else {
        totalBillet = parseFloat(this.value);
        textMsg = this.trans("finance.confirm_create_billet_msg") + ": " + this.CurrencySymbol + totalBillet.toFixed(2);

      }

      this.$swal({
        title: this.trans("finance.confirm_create_billet"),
        text: textMsg,
        type: "question",
        showCancelButton: true,
        confirmButtonText: this.trans("finance.confirm"),
        cancelButtonText: this.trans("finance.cancel"),
      }).then((result) => {
        if (result.value) {
          new Promise((resolve, reject) => {
            axios
              .post(this.add_new_billet_route, {
                value: parseFloat(this.value),
              })
              .then((response) => {
                console.log("rewsp: ", response)
                if (response.data.success) {
                  this.$swal({
                    title: this.trans("finance.billet_success"),
                    html:
                      '<label class="alert alert-warning alert-dismissable text-left">' +
                        this.trans("finance.billet_success_msg") + " " + '<a href="' + response.data.billet_url + '" target="_blank">clique aqui</a>' +
                      '</label>',
                    type: "success",
                  }).then((result) => {
                    window.location.reload();
                  });
                } else {
                  this.showErrorMsg(response.data);
                }
              })
              .catch((error) => {
                console.log(error);
                reject(error);
                this.showErrorMsg("Erro ao gerar boleto");
                return false;
              });
          });
        }
      });
    },
    requestCharge() {
      let card = this.cards_list.find((card) => card.id === this.pay_card);
      if (!card) {
        this.$swal({
          title: this.trans("finance.choose_card"),
          type: "error",
        });
        return;
      }
      if (this.value <= 0) {
        this.$swal({
          title: this.trans("finance.value_cant_be_lower") + " 0,00",
          type: "error",
        });
        return;
      }
      this.$swal({
        title: this.trans("finance.confirm_payment"),
        text:
          this.trans("finance.ride_card_payment") +
          ": " +
          card.card_type +
          " " +
          card.last_four,
        type: "question",
        showCancelButton: true,
        confirmButtonText: this.trans("finance.make_payment"),
        cancelButtonText: this.trans("finance.cancel"),
      }).then((result) => {
        if (result.value) {
          this.sendRequest();
        }
      });
    },
    sendRequest() {
      new Promise((resolve, reject) => {
        axios
          .post(this.request_payment_route, {
            card_id: this.pay_card,
            value: this.value,
          })
          .then((response) => {
            if (response.data.success) {
              this.$swal({
                title: this.trans(
                  "finance.payment_creditcard_success"
                ),
                type: "success",
              }).then((result) => {
                window.location.reload();
              });
            } else {
              this.showErrorMsg(response.data);
            }
          })
          .catch((error) => {
            console.log(error);
            reject(error);
            this.showErrorMsg();
            return false;
          });
      });
    },
    formatNumber(num) {
      let numF = parseFloat(num).toFixed(2);
      numF = numF.toString().replace('.',',');
      return this.CurrencySymbol + numF;
    },
    alertDeleteCard(card_id, last_four) {
      this.$swal({
        title: "Remover cartão",
        text: "Tem certeza que deseja remover o cartão: **** **** " + last_four,
        type: "question",
        showCancelButton: true,
        cancelButtonText: this.trans("finance.cancel"),
        confirmButtonText: "Sim",
      }).then((result) => {
        if (result.value) {
          console.log(this.delete_user_card);
          new Promise((resolve, reject) => {
            axios
              .post(this.delete_user_card, {
                card_id: card_id,
              })
              .then((response) => {
                if (response.data.success) {
                  this.$swal({
                    title: this.trans("finance.card_removed"),
                    type: "success",
                  }).then((result) => {
                    window.location.reload();
                  });
                } else {
                  this.$swal({
                    title: "error",
                    type: "error",
                  });
                }
              })
              .catch((error) => {
                console.log(error);
                reject(error);
                return false;
              });
          });
        }
      });
    },
    iframeCardAdded() {
      if($('#modal-add-credit-card').is(':visible')) {
        location.reload();
      }
    }
  },
  mounted() {
    console.log("this.yearNow", this.yearMaks);
    window.addEventListener("load", function(event) {
      var card = new Card({
        form: "#add-credit-card",
        container: ".card-wrapper",
      });
    });
  },
  created() {
    this.cards_list = JSON.parse(this.user_cards);

    this.prepaid_settings = JSON.parse(this.PrepaidSettings);
    
    if (parseFloat(this.user_balance) < 0)
      this.value = -1 * parseFloat(this.user_balance);
    else this.value = 0;

    this.valueBillet = 0;
    //$("#modal-add-credit-card").modal("show");
  },
};
</script>
<template>
  <div>

    <!-- Pagamento com boleto bancario e cartao -->
    <div class="card card-outline-info">
      <div class="card-header">
        <div class="row">
          <div class="col-sm-7">
            <h3 class="card-title text-white m-b-0">
              {{ trans("finance.add_balance") }}
            </h3>
          </div>
        </div>
      </div>
      <div class="card-block">
        <div class="container row col-md-12 d-flex justify-content-center">
          <div class="row">
            <div class="col-sm-12">
              <h2 style="text-align: center;" class="card-title text-black">
                {{trans("finance.value_to_pay") + ": " + formatNumber(value) }}
              </h2>
            </div>
            <div class="col-sm-10">
              <button
                  v-on:click="changeValue"
                  class="btn btn-link pull-right"
                  style="margin-top: -15px"
                  type="button"
                >
                  <i class="mdi mdi-cash-multiple"></i>
                  {{ trans("finance.change_value") }}
              </button>
            </div>  
          </div>
          
        </div>
        <br />
      </div>

      <div v-if="
        (Enviroment == 'user' && parseInt(prepaid_settings.prepaid_billet_user)) ||
        (Enviroment == 'provider' && parseInt(prepaid_settings.prepaid_billet_provider)) ||
        (Enviroment == 'corp' && parseInt(prepaid_settings.prepaid_billet_corp))
      ">
        <div class="row">
          <div class="col-sm-4"></div> <!-- offset -->
          <div class="col-sm-4">
            <a
              v-on:click="requestChargeBillet"
              class="row custom_btn default btn-lg"
              type="button"
            >
              {{ trans("finance.new_billet") }}
            </a>
          </div>
          <i class="mdi mdi-barcode" style="margin-left: -50px; font-size: 36px;"></i>
        </div>
        <br />
      </div>

      <div v-if="
        (Enviroment == 'user' && parseInt(prepaid_settings.prepaid_pix_user)) ||
        (Enviroment == 'provider' && parseInt(prepaid_settings.prepaid_pix_provider)) ||
        (Enviroment == 'corp' && parseInt(prepaid_settings.prepaid_pix_corp))
      ">
        <div class="row">
          <div class="col-sm-4"></div> <!-- offset -->
          <div class="col-sm-4">
            <a
              v-on:click="requestChargepix"
              class="row custom_btn default btn-lg"
              type="button"
            >
              {{ trans("finance.add_pix_balance") }}
            </a>
          </div>
            <svg style="margin-left: -50px; z-index: 2; margin-top: 7px;" id="svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="40" height="40" viewBox="0, 0, 400,400" version="1.1"><g id="svgg"><path id="path0" d="M190.820 24.773 C 182.377 26.403,176.861 28.633,169.889 33.235 C 166.480 35.485,95.313 105.622,95.313 106.731 C 95.313 106.896,98.893 107.031,103.269 107.031 C 114.544 107.031,120.806 108.313,128.516 112.198 C 135.797 115.867,136.097 116.139,166.992 146.909 C 198.866 178.654,197.574 177.528,202.148 177.528 C 206.724 177.528,205.463 178.625,236.914 147.286 C 261.986 122.303,266.308 118.159,269.499 116.051 C 278.896 109.839,286.315 107.536,298.184 107.146 L 305.157 106.917 270.450 72.189 C 230.811 32.528,230.075 31.879,220.285 27.961 C 211.942 24.622,198.909 23.211,190.820 24.773 M57.862 143.945 C 34.877 167.006,33.145 168.954,29.862 175.432 C 21.798 191.346,22.373 212.182,31.286 227.013 C 34.799 232.859,37.189 235.470,58.720 256.980 L 79.979 278.218 93.993 278.152 C 109.711 278.077,112.245 277.847,117.681 276.004 C 125.649 273.302,124.685 274.138,157.617 241.365 C 187.273 211.853,187.308 211.820,190.625 210.200 C 198.313 206.448,206.616 206.521,214.180 210.408 C 216.771 211.740,219.264 214.115,245.898 240.634 C 284.697 279.264,281.541 277.287,305.078 277.716 C 312.490 277.851,318.821 278.089,319.145 278.245 C 320.182 278.742,363.686 234.765,366.762 230.111 C 379.052 211.518,379.052 188.482,366.762 169.889 C 363.686 165.235,320.182 121.258,319.145 121.755 C 318.821 121.911,312.490 122.149,305.078 122.284 C 281.532 122.713,284.731 120.705,245.703 159.556 C 214.379 190.738,215.325 189.901,209.375 191.712 C 202.532 193.796,194.982 192.789,188.877 188.978 C 187.738 188.267,174.530 175.436,157.422 158.421 C 118.551 119.762,122.420 121.985,93.641 121.778 L 80.055 121.680 57.862 143.945 M198.633 222.911 C 196.811 223.463,194.702 225.473,167.188 252.885 C 136.084 283.873,135.797 284.133,128.516 287.802 C 120.840 291.670,114.495 292.966,103.223 292.968 C 98.872 292.968,95.313 293.104,95.313 293.269 C 95.313 294.378,166.480 364.515,169.889 366.765 C 189.506 379.715,214.557 378.874,233.008 364.646 C 234.512 363.487,251.361 346.911,270.450 327.811 L 305.157 293.083 298.184 292.854 C 286.313 292.464,278.891 290.160,269.499 283.948 C 266.308 281.838,261.965 277.674,236.719 252.511 C 203.134 219.037,205.459 220.845,198.633 222.911 " stroke="none" fill="#04bcac" fill-rule="evenodd"></path><path id="path1" d="" stroke="none" fill="#08bcac" fill-rule="evenodd"></path></g></svg>
        </div>
        <br />
      </div>

      <div v-if="
        (Enviroment == 'user' && parseInt(prepaid_settings.prepaid_card_user)) ||
        (Enviroment == 'provider' && parseInt(prepaid_settings.prepaid_card_provider)) ||
        (Enviroment == 'corp' && parseInt(prepaid_settings.prepaid_card_corp))
      ">
        <div class="row">
          <div class="col-sm-4"></div> <!-- offset -->
          <div class="col-sm-4">
            <a
              v-on:click="selectCardPayment"
              class="row custom_btn default btn-lg"
              type="button"
            >
              {{ trans("finance.payment_card") }}
            </a>
          </div>
          <i class="mdi mdi-credit-card" style="margin-left: -50px; font-size: 36px;"></i>
        </div>
      </div>
      <br />
    </div>
    
    <!-- Modal to pay with card -->
    <div id="modal-card-selected" class="modal fade" role="dialog">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">
              &times;
            </button>
            <h4 class="modal-title">{{ trans("finance.new_card") }}</h4>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-sm-12">
                  <!-- Pagamento com cartao de credito -->
                  <div class="card card-outline-info">

                    <div class="card-block">

                      <div class="row justify-content-center">
                        
                        <div
                          data-toggle="buttons"
                          class="btn-group-vertical col-sm-7"
                          aria-label="Toolbar with button groups"
                        >
                          <label
                            v-for="card in cards_list"
                            :key="card.id"
                            v-on:click="pay_card = card.id ? card.id : card.card_id"
                            class="btn btn-outline-info btn-lg"
                          >
                            <td>
                              <input
                                type="radio"
                                name="pay_card"
                                id="pay_card"
                                :value="card.id"
                              />
                            </td>
                            <td class="col-3" style="text-transform:uppercase;">
                              <div class="float-left">{{ card.card_type }}</div>
                            </td>
                            <td class="col-3 justify-content-end">
                              **** **** {{ card.last_four }}
                            </td>
                            <td>
                              <button
                                @click="alertDeleteCard(card.id, card.last_four)"
                                class="btn-sm btn-danger"
                                type="button"
                              >
                                X
                              </button>
                            </td>
                          </label>
                        </div>
                      </div>

                        <div v-bind:class="cards_list.length > 0 ? 'row d-flex justify-content-end pr-3' : 'row d-flex justify-content-center pr-3'">
                          <a
                            v-on:click="addCard"
                            class="custom_btn default"
                            type="button"
                          >
                            <i class="mdi mdi-plus"></i>
                            {{ trans("finance.new_card") }}
                          </a>
                        </div>
                    </div>
                    <div v-if="cards_list.length > 0" class="row d-flex justify-content-center pr-3 pb-3">
                      <button
                        v-on:click="requestCharge"
                        class="btn btn-success btn-lg"
                        type="button"
                      >
                        <i class="mdi mdi-credit-card-multiple"></i>
                        {{ trans("finance.make_payment") }}
                      </button>
                  </div>
                  </div>
                                    
                  

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal to add card -->
    <div id="modal-add-credit-card" class="modal fade" role="dialog">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">
              &times;
            </button>
            <h4 class="modal-title">{{ trans("finance.new_card") }}</h4>
          </div>
          <div class="modal-body">
          	<iframe v-if="IframeAddCard" class="col-12" @load="iframeCardAdded" height="450" :src="IframeAddCard" title="Juno"></iframe>
            <div v-else class="row">
              <div class="col-lg-6">
                <div class="card-wrapper"></div>
              </div>
              <div class="col-lg-6">
                <div
                  id="field-errors"
                  class="alert alert-danger alert-dismissable"
                  style="display:none;margin-left:0;"
                ></div>
                <form
                  v-on:submit.prevent="submitNewCard"
                  id="add-credit-card"
                  method="POST"
                  data-toggle="validator"
                  role="form"
                >
                  <input
                    type="hidden"
                    name="user-id"
                    value="<?= $user->id ?>"
                  />
                  <div class="form-group">
                    <input
                      v-model="card_number"
                      id="card-number"
                      name="number"
                      v-mask="['#### #### #### #### ###']"
                      type="text"
                      class="form-control"
                      :placeholder="trans('finance.card_number')"
                      required
                      :data-error="trans('finance.card_number')"
                      aria-invalid="true"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                  <div class="form-group">
                    <input
                      v-model="card_holder"
                      id="card-holder"
                      name="name"
                      type="text"
                      class="form-control"
                      :placeholder="trans('finance.card_holder_name')"
                      required
                      :data-error="trans('finance.card_holder')"
                      aria-invalid="true"
                    />
                    <div class="help-block with-errors"></div>
                  </div>
                  <div class="form-group">
                    <div class="row">
                      <div class="col-6">
                        <input
                          v-model="card_cvv"
                          v-mask="['####']"
                          id="card-cvv"
                          name="cvv"
                          type="text"
                          class="form-control"
                          :placeholder="trans('finance.cvv')"
                          required
                        />
                      </div>
                      <div class="col-6">
                        <input
                          v-model="card_exp"
                          v-mask="yearMaks"
                          id="card-exp"
                          name="expiry"
                          class="form-control mb-2"
                          type="text"
                          placeholder="MM/YYYY"
                          required
                        />
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <button
                      type="button"
                      id="btn-cancel"
                      class="btn btn-default"
                      data-dismiss="modal"
                    >
                      {{ trans("finance.cancel") }}
                    </button>
                    <button
                      type="submit"
                      id="btn-add-card"
                      class="btn btn-primary"
                    >
                      {{ trans("finance.save") }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.custom_btn {
  border: 2px solid black;
  border-radius: 5px;
  background-color: white;
  color: black;
  padding: 14px 28px;
  font-size: 16px;
  cursor: pointer;
}
.custom_btn:focus,.btn:active {
   outline: none !important;
   box-shadow: none;
}
/* Gray */
.default {
  border-color: #e7e7e7;
  color: black;
}

.default:hover {
  background: #f7f7f7;
}
</style>