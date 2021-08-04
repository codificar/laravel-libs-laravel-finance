<script>
import axios from "axios";
export default {
  props: ["BillingDays", "BillingExist", "BillingStatus", "AverageBilling"],
  data() {
    return {
      active: 1,
      inactive: 2,
      request_activation: 3,
      billing_day: "0",
      average: "0",
      tax_withholding: false,
      accept_terms: false,
      billing_status: false
    };
  },
  components: {},
  methods: {
    submitForm() {
      //Submit form
      new Promise((resolve, reject) => {
        axios
          .post("/api/v3/billing/store", {
            billing_day: this.billing_day,
            average: this.average,
            tax_withholding: this.tax_withholding,
            accept_terms: this.accept_terms
          })
          .then(response => {
            if (response.data.success) {
              this.$swal({
                title: this.trans("institution.added_billing_success"),
                type: "success"
              }).then(result => {
                  this.billing_status = response.data.billing_status;
              });
            } else {
              this.$swal({
                title: this.trans("institution.added_billing_fail"),
                html:
                  '<label class="alert alert-danger alert-dismissable text-left">' +
                  response.data.errors.join("<br>") +
                  "</label>",
                type: "error"
              });
            }
          })
          .catch(error => {
            console.log(error.response);
            reject(error);
            return false;
          });
      });
    }
  },
  created() {
    this.billing_status = this.BillingStatus;
    this.billing_days = JSON.parse(this.BillingDays);
    this.average_billing = JSON.parse(this.AverageBilling);
    
  }
};
</script>
<template>
  <div>
    <div class="col-lg-12">
      <div class="card card-outline-info">
        <div class="card-header">
          <h4 class="m-b-0 text-white">{{ trans('finance.billing_statement') }}</h4>
        </div>
        <div class="card-block">
          <div class="row">
            <div class="col-md-12">
              <div class="box box-warning">
                <div class="list-group">
                  <h4 class="list-group-item-heading">Como Funciona</h4>
                  <!--<div class="list-group-item">-->
                  <p
                    class="list-group-item-heading col-12 text-left"
                  >Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                  <!--</div>-->
                </div>
                <br />
                <div class="box-header" v-if="billing_status" :v-model="billing_status">
                  <div v-if="billing_status == inactive">
                    <h4
                      class="box-title"
                      align="center"
                    >Parabéns! Sua conta de faturamento está criada, porém, ainda está inativa.</h4>
                  </div>
                  <div v-else-if="billing_status == request_activation">
                    <h4
                      class="box-title"
                      align="center"
                    >Parabéns! Sua conta de faturamento está criada, porém, aguardando ativação.</h4>
                  </div>
                  <div v-else>
                    <h4
                      class="box-title"
                      align="center"
                    >Parabéns! Sua conta de faturamento está criada e ativa.</h4>
                  </div>
                </div>
                <div class="box-header" v-else>
                  <h4 class="box-title">Bem-vindo a opção de Faturamento</h4>
                  <!--<form method="post" v-bind:action="url">-->
                  <form data-toggle="validator" v-on:submit.prevent>
                    <div class="box-body">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <select
                              v-model="billing_day"
                              name="billing_day"
                              class="select form-control"
                            >
                              <option value="0">{{ trans('institution.billing_days') }}</option>
                              <option
                                v-for="day in billing_days"
                                v-bind:value="day"
                                v-bind:key="day"
                              >Todo dia {{ day }} do mês</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <select v-model="average" name="average" class="select form-control">
                              <option value="0">{{ trans('institution.average_billing') }}</option>
                              <option
                                v-for="average in average_billing"
                                v-bind:value="average.value"
                                v-bind:key="average.value"
                              >{{ average.name }}</option>
                            </select>
                          </div>
                        </div>

                        <!--span-->
                        <div class="col-md-6">
                          <div class="form-inline">
                            <!--<div class="input-group">-->
                            <input
                              type="checkbox"
                              class="form-control"
                              id="tax_withholding"
                              name="tax_withholding"
                              v-model="tax_withholding"
                            />
                            <label>&nbsp;Desejar realizar retenção de imposto ?</label>
                            <!--</div>-->
                          </div>
                        </div>

                        <div class="col-md-12">
                          <div class="form-inline">
                            <input
                              type="checkbox"
                              class="form-control"
                              id="accept_terms"
                              name="accept_terms"
                              v-model="accept_terms"
                              
                            />
                            &nbsp; Aceitar os
                            <a
                              href="/termsncondition/user"
                              target="_blank"
                            >&nbsp; termos e condições para faturamento</a>
                          </div>
                        </div>
                      </div>
                      <!--/ end-row-->
                      <button type="button" class="btn btn-success" v-on:click="submitForm()">
                        <i class="fa fa-money"></i> Cria minha conta
                      </button>
                    </div>
                  </form>
                </div>
                <!--/ box-body -->
              </div>
              <!--/ box-warning -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>