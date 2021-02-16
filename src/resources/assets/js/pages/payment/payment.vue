<script>
import axios from "axios";
export default {
  props: [
    "user_balance",
    "user_cards",
    "save_payment_route",
    "request_payment_route",
    "add_new_billet_route",
    "financial_report_route",
    "delete_user_card",
    "PrepaidSettings"
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
      if(this.prepaid_settings.prepaid_billet_user && this.prepaid_settings.prepaid_tax_billet && parseFloat(this.prepaid_settings.prepaid_tax_billet) > 0) {
        totalBillet = parseFloat(this.value) + parseFloat(this.prepaid_settings.prepaid_tax_billet);
        textMsg = this.trans("finance.tax_value") + ": R$ " + this.prepaid_settings.prepaid_tax_billet + ". " + this.trans("finance.total") + ": R$ " + totalBillet.toFixed(2);
      } else {
        totalBillet = parseFloat(this.value);
        textMsg = this.trans("finance.confirm_create_billet_msg") + ": R$ " + totalBillet.toFixed(2);

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
      return "R$ " + numF;
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

      <div v-if="prepaid_settings.prepaid_billet_user && prepaid_settings.prepaid_billet_user != '0'">
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

      <div v-if="prepaid_settings.prepaid_card_user && prepaid_settings.prepaid_card_user != '0'">
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
            <div class="row">
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