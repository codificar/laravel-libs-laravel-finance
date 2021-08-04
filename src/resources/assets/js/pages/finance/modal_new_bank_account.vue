<script>
export default {
  props: ["Ledger", "BankList", "AccountTypes"],
  data() {
    return {
      ledger: "",
      error_messages: [],
      banks: [],
      account_types: [],
      bank_account_id: 0,

      holder_name: "",
      bank_id: 1,
      document_option: "individual",
      document: "",
      account_type: "conta_corrente",
      agency: "",
      agency_digit: "",
      account: "",
      account_digit: ""
    };
  },
  methods: {
    saveBankAccount() {
      // Make the post request do Add an Withdraw Request
      this.$http
        .post(
          window.location.pathname +
            "/create-user-bank-account" +
            "?ledger-id=" +
            this.ledger.ledger.id +
            "&user-id=" +
            this.ledger.ledger.user_id +
            "&provider-id=" +
            this.ledger.ledger.provider_id +
            "&holder=" +
            this.holder_name +
            "&bank-id=" +
            this.bank_id +
            "&option-document=" +
            this.document_option +
            "&document=" +
            this.document +
            "&account-type=" +
            this.account_type +
            "&agency=" +
            this.agency +
            "&agency-digit=" +
            this.agency_digit +
            "&account=" +
            this.account +
            "&account-digit=" +
            this.account_digit
        )
        .then(
          response => {
            if (response.body.success == false) {
              this.error_messages = response.body.messages;
            } else {
              this.$emit("newModalBankClosed", response.body.bank_account);
              //Emit action to parent component to show success message and reload page
            }
          },
          response => {
            // error callback
          }
        );
    },
    addNewBankAccount() {
      this.$emit("newModalBankClosed");
    },
    closeModal() {
      this.$emit("newModalBankClosed", 0);
    }
  },
  // Watches for changes on props or data
  watch: {},
  created() {
    this.ledger = this.Ledger;
    this.banks = this.BankList;
    this.account_types = this.AccountTypes;
  }
};
</script>
<template>
    <div>
        <div id="modal-new-bank-account" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <button v-on:click="closeModal" type="button" class="close">&times;</button>
                        <h4 class="modal-title text-white">{{ trans('finance.add_new_bank_account') }}</h4>
                    </div>
                    <div class="modal-body">
                        <form data-toggle="validator" >
                            <div class="card">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{trans('finance.holder_name') }} *</label>
                                            <input type="text" v-model="holder_name" class="form-control" minlength="5" maxlength="30" name="holder" required>
                                            <div class="help-block with-errors"></div>
                                            <p></p>
                                            <h4>
                                                <span class="label label-warning">{{trans('finance.holder_name_info') }}</span>
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <label>{{trans('finance.bank') }} *</label>
                                        <select class="form-control" v-model="bank_id" name="bank_id" required>
                                            <option v-for="bank in banks" v-bind:value="bank.id" v-bind:key="bank.id">{{ bank.code }} - {{ bank.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('finance.document_type') }}: </label><br>
                                            <div class="form-check">
                                                <label class="custom-control custom-radio">
                                                    <input v-model="document_option" :value="'individual'" type="radio" id="cpf" class="custom-control-input">
                                                    <span class="custom-control-indicator"></span>
                                                    <span class="custom-control-description">CPF</span>
                                                </label>
                                                <label class="custom-control custom-radio">
                                                    <input v-model="document_option" :value="'company'" type="radio" id="cnpj" class="custom-control-input">
                                                    <span class="custom-control-indicator"></span>
                                                    <span class="custom-control-description">CNPJ</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('finance.document_number') }}: * </label>
                                            <input v-model="document" v-if="document_option == 'individual'" type="text" class="form-control" name="document" v-mask="['###.###.###-##']" required>
                                            <input v-model="document" v-else type="text" class="form-control" name="document" v-mask="['##.###.###/####-##']" required>
                                            <span id="invalid-cpf-bank" class="hide">{{trans('providerController.cpf_invalid') }}</span>
                                            <span id="invalid-cnpj-bank" class="hide">{{trans('providerController.cnpj_invalid') }}</span>
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('finance.account_types')}} *</label>
                                            <select v-model="account_type" class="form-control" name="account_types" required>
                                                <option v-for="type in account_types" v-bind:key="type.id" v-bind:value="type.id">{{ type.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{trans('finance.agency') }} *</label>
                                            <input v-model="agency" type="number" class="form-control" name="agency" v-mask="['###########',]" maxlength="10" minlength="2" required>
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{trans('finance.agency_digit') }} *</label>
                                            <input v-model="agency_digit" type="number" class="form-control" name="agency_digit" v-mask="['#']" maxlength="1" minlength="1" required>
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{trans('finance.account_number') }} *</label>
                                            <input v-model="account" type="number" class="form-control" name="account" v-mask="['###########',]" maxlength="10" minlength="2" required>
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{trans('finance.account_digit') }} *</label>
                                            <input v-model="account_digit" type="number" class="form-control" name="account_digit" v-mask="['#']" maxlength="1" minlength="0" required>
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!isEmpty(error_messages)" id="field-errors" class="alert alert-danger alert-dismissable">
                                <div v-for="message in error_messages" :key="message">
                                    {{message}}
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer ">
                        <div class="pull-rigth ">
                            <div class="row">
                                <div class="col">
                                    <button v-on:click="closeModal" type="button" id="cancel_bank" class="btn btn-inverse btn-flat btn-block">
                                        {{trans('finance.cancel') }}
                                    </button>
                                </div>
                                <div class="col">
                                    <button v-on:click="saveBankAccount" type="button" id="save_bank" class="btn btn-success btn-flat btn-block"
                                        :disabled="
                                            holder_name.length < 5 ||
                                            !bank_id ||
                                            !document ||
                                            !agency ||
                                            !agency_digit ||
                                            !account ||
                                            !account_digit">
                                        {{trans('finance.save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>