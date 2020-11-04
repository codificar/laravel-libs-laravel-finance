<script>
export default {
  props: ["Ledger", "FinanceTypes"],
  data() {
    return {
      ledger: "",
      types: "",
      type_entry: "0",
      entry_description: "",
      entry_value: "",
      error_messages: [],
      entry_date: ""
    };
	},
	
  methods: {
    submitEntry() {
      // Make the post request do Add an Entry
      this.$http
        .post(
          window.location.pathname +
            "/add-entry" +
            "?ledger-id=" +
            this.ledger.ledger.id +
            "&type-entry=" +
            this.type_entry +
            "&entry-description=" +
            this.entry_description +
            "&entry-value=" +
            this.entry_value +
            "&entry-date=" +
            this.entry_date
        )
        .then(
          response => {
            if (response.body.success == false) {
              this.error_messages = response.body.messages;
            } else {
							console.log(response);
              this.$emit("entrySuccess");
              //Emit action to parent component to show success message and reload page
            }
          },
          response => {
           alert("Error");
          }
        ).catch(response =>{
alert("Error");
				});
    },
    resetModal() {
      //Reset the Modal in case the user closes it
      this.type_entry = "0";
      this.entry_description = "";
			this.entry_value = "";
			
      this.error_messages = [];
      this.entry_date = "";
    }
  },
  created() {
    this.ledger = this.Ledger;
    this.types = this.FinanceTypes;
	},
	mounted() { 
    jQuery('#date-modal').datepicker({
        format: 'dd/mm/yyyy',
        language: "pt-BR",
        changeMonth: true,
        numberOfMonths: 1,
        autoclose: true,
        todayHighlight: true,
        toggleActive: true,
        orientation: "bottom auto"
    }).on(
        "changeDate", () => {
          this.entry_date = $('#transaction-date').val();
          
          }
		);
		},
};
</script>
<template>
  <div>
    <div id="modal-entry" class="modal fade" role="dialog">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">{{ trans('finance.insert_entry') }}</h4>
          </div>
          <div class="modal-body">
            <div class="box-body">
              <div class="row">
                <div class="col-sm-4">
                  <!-- Adicionando foto genérica -->
                  <img
                    v-if="ledger.picture == ''"
                    src="http://www.enactus.ufscar.br/wp-content/uploads/2016/09/boneco-gen%C3%A9rico.png"
                    style="width:100px; height: 80px"
                    alt
                    class="img-circle"
                  >
                  <img
                    v-else
                    class="profile-user-img img-circle"
                    :src="ledger.picture"
                    style="width: 80px; height: 80px"
                    alt
                  >
                </div>
                <div class="col-sm-8" style="word-wrap: break-word;">
                  <div class="profile-username">{{ ledger.first_name+' '+ledger.last_name }}</div>
                  <div class="text-muted profile-phone">{{ ledger.phone }}</div>
                  <div class="text-muted profile-email">{{ ledger.email }}</div>
                </div>
              </div>
              <br>
              <div
                v-if="!isEmpty(error_messages)"
                id="field-errors"
                class="alert alert-danger alert-dismissable"
              >
                <div v-for="message in error_messages" :key="message">
                  <p>{{message}}</p>
                </div>
              </div>
              <div class="form-group">
                <select
                  v-model="type_entry"
                  name="type_entry"
                  class="select form-control"
                  required
                  :data-error="trans('finance.type_required')"
                >
                  <option value="0">{{trans('finance.transaction_type') }}</option>
                  <option
                    v-for="option in types"
                    v-bind:value="option"
                    v-bind:key="option"
                  >{{ trans("finance.op_"+option.toLowerCase()) }}</option>
                </select>
                <div class="help-block with-errors"></div>
              </div>
              <div class="form-group">
                <input
                  v-model="entry_description"
                  type="text"
                  class="form-control"
                  required
                  :data-error="trans('finance.description_req')"
                  :placeholder="trans('finance.description')+'*' "
                  maxlength="100"
                >
                <div class="help-block with-errors"></div>
              </div>
              <div class="form-group">
                <input
                  type="number"
                  class="form-control"
                  v-model="entry_value"
                  maxlength="60"
                  :placeholder="trans( 'finance.value') + '*'"
                  required
                  :data-error="trans( 'finance.value_required') "
                >
                <div class="help-block with-errors"></div>
              </div>

              <div class="form-group">
                <div class="input-daterange input-group" id="date-modal">
                  <input
                    type="text"
                    class="form-control"
                    v-model="entry_date"
                    id="transaction-date"
                    name="transaction-date"
                    :placeholder="trans( 'finance.datePattern') + '*'"
                    required
                    :data-error="trans( 'finance.value_required') "
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <div class="pull-rigth">
              <button
                v-on:click="submitEntry"
                type="button"
                class="btn btn-success"
              >{{ trans('finance.insert') }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>