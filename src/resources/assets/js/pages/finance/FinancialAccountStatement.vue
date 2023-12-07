<template>
    <div>
        <!-- Request loading -->
        <loading 
            :active.sync="isLoading" 
            :is-full-page="fullPage"
            :loader="loader"
            :color="color"
        ></loading>
        <!-- Request loading -->

        <!-- Modal entry: Inserir transação -->
        <modalentry
            v-if="loginType == 'admin'"
            v-on:entrySuccess="reloadPage"
            :ledger="holder"
            :finance-types="finance_types"
        ></modalentry>
        <!-- Modal entry: Inserir transação -->

        <!-- Modal request with draw: Solicitar saque -->
        <modalrequestwithdraw
            v-show="withDrawSettings.with_draw_enabled == true"
            v-on:newBankAccount="showModalNewBankAccount"
            v-on:addWithDrawRequest="reloadPage"
            :ledger="holder"
            :bank-accounts="bankAccounts"
            :bank-account-id="bank_account_id"
            :bank-list="banks"
            :with-draw-settings="withDrawSettings"
            :available-balance="balanceData.total_balance"
            :currency-symbol="currencySymbol"
        ></modalrequestwithdraw>
        <!-- Modal request with draw: Solicitar saque -->
        
        <!-- Modal new Bank Account -->
        <modalnewbankaccount
            v-on:newModalBankClosed="ModalNewBankClosed"
            :ledger="holder"
            :bank-list="banks"
            :account-types="accountTypes"
        ></modalnewbankaccount>        
        <!-- Modal new Bank Account -->

        <!-- Financial Account Header -->
        <div class="col-lg-12">
            <div class="card card-outline-info">
                <div class="card-header">
                    <h4 class="m-b-0 text-white">{{ trans('finance.account_statement') }}</h4>
                </div>
                <div class="card-block">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-warning">
                                <div class="box-header">
                                    <h3 class="box-title">{{ holder.full_name  }}</h3>
                                </div>
                                <form id="filter-account-statement" method="get" v-bind:action="url ">
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <!--Select transaction type filter-->
                                                    <label for="giveName">{{trans('finance.transaction_type') }}</label>
                                                    <select
                                                        v-model="type_entry"
                                                        name="type_entry"
                                                        class="select form-control"
                                                    >
                                                    <option value="0">{{trans('finance.transaction_type') }}</option>
                                                    <option
                                                        v-for="option in finance_types"
                                                        v-bind:value="option"
                                                        v-bind:key="option"
                                                    >{{ trans("finance.op_"+option.toLowerCase()) }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <!--span-->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="daterange">{{trans('finance.occurrence_date') }}</label>
                                                    <div class="input-daterange input-group">
                                                        <datepicker 
                                                            class="datepicker-box" 
                                                            v-model="startDate" 
                                                            type="date" 
                                                            :lang="language" 
                                                            :format="dateFormat"
                                                            input-class="mx-input"
                                                        ></datepicker>
                                                        <span class="input-group-addon bg-info b-0 text-white">{{trans('finance.to') }}</span>
                                                        <datepicker 
                                                            class="datepicker-box" 
                                                            v-model="endDate" 
                                                            type="date" 
                                                            :lang="language" 
                                                            :format="dateFormat"
                                                            input-class="mx-input"
                                                        ></datepicker>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--/ end-row-->
                                        <div class="box-footer pull-right">
                                            <button
                                            @click="downloadFinancialSummary"
                                            class="btn btn-info right"
                                            type="button"
                                            >
                                            <i class="mdi mdi-download"></i>
                                            {{trans('finance.download')}}
                                            </button>
                                            <button
                                                @click="getFinancialSummary"
                                                class="btn btn-success right"
                                                type="button"
                                                value="Filter_Data"
                                            >
                                            <i class="fa fa-search"></i>
                                            {{ trans('finance.search') }}
                                            </button>
                                        </div>
                                        <button
                                            v-if="loginType == 'admin'"
                                            @click="showModalEntry"
                                            class="btn btn-info"
                                            type="button"
                                        >
                                            <i class="fa fa-exchange"></i>
                                            {{ trans('finance.insert_entry') }}
                                        </button>
                                       
                                    </div>
                                </form>
                                <!--/ box-body -->
                            </div>
                            <!--/ box-warning -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Financial Account Header -->
        
        <!-- Empty box component -->
        <div class="col-md-12" v-if="isDataEmpty">
            <div class="card">
                <div class="card-block">
                    <div>
                        <empty-box></empty-box>
                    </div>
                    <div class="text-center">
                        <h3>
                            <i class="fa fa-money"></i>
                            <strong>{{ trans('finance.current_balance') }}:</strong>
                            
                            <span v-if="balance.total_balance_by_period >= 0" class="text-success">
                                {{ formatCurrency(balance.total_balance_by_period) }}
                            </span>
                            <span v-else class="text-danger">
                                {{ formatCurrency(balance.total_balance_by_period) }}
                            </span>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        <!-- Empty box component -->

        <div v-else>
            <!-- Detailed balance -->
            <div class="col-lg-12" v-if="!isEmpty(balance.current_compensations)">
                <div class="card">
                    <div class="card-block">
                        <h3 class="card-title">{{ cardTitle }}</h3>
                        <div>
                            <i class="fa fa-money"></i>
                            <strong>
                                {{ trans('finance.previous_balance') }}
                                <span style="color: #0275d8" class="mdi mdi-comment-question-outline" v-tooltip="trans('finance.previous_balance_msg')"></span>:
                            </strong>
                            
                            <span v-if="balance.previous_balance >= 0" class="text-success">
                                {{ formatCurrency(balance.previous_balance) }}
                            </span>
                            <span v-else class="text-danger">
                                {{ formatCurrency(balance.previous_balance) }}
                            </span>
                        </div>
                        <div style="margin-top:15px">
                            <i class="fa fa-money"></i>
                            <strong>
                                {{ trans('finance.period_balance') }}
                                <span style="color: #0275d8" class="mdi mdi-comment-question-outline" v-tooltip="trans('finance.period_balance_msg')"></span>:
                            </strong>
                            
                            <span v-if="balance.period_balance >= 0" class="text-success">
                                {{ formatCurrency(balance.period_balance) }}
                            </span>
                            <span v-else class="text-danger">
                                {{ formatCurrency(balance.period_balance) }}
                            </span>
                        </div>

                        <div class="card-block">
                            <div>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>{{ trans("finance.finance_date") }} <i class="fa fa-calendar"></i></th>
                                        <th class="hide-small">{{ trans("finance.finance_time") }} <i class="fa fa-clock-o"></i></th>
                                        <th>{{ trans("finance.transaction_type") }} <i class="fa fa-exchange"></i></th>
                                        <th class="hide-small">{{ trans("finance.reason") }} <i class="fa fa-pencil-square"></i></th>
                                        <th>{{ trans("finance.finance_value") }} <i class="fa fa-money"></i></th>
                                    </tr>

                                    <tr v-for="entry in balance.current_compensations" v-bind:key="entry.id" total="0">
                                        <td>{{ entry.compensation_date | moment("DD/MM/YYYY") }}</td>
                                        <td class="hide-small">{{ entry.compensation_date | moment("hh:mm:ss") }}</td>
                                        <td v-if="entry.reason">{{ trans("finance.op_"+entry.reason.toLowerCase()) }}</td>
                                        <td v-else>{{ trans('finance.reason_not_found') }}</td>
                                        <td class="hide-small">
                                            {{ entry.description }}
                                            <a v-if="entry.request_id" target="_blank" :href="'/admin/request/details/' + entry.request_id" >
                                                {{ entry.request_id }}
                                            </a>
                                        </td>
                                    
                                        <td v-if="entry.value >=0">
                                            <p class="text-success">
                                                {{ formatCurrency(entry.value) }}
                                            </p>
                                        </td>
                                        <td v-else>                                            
                                            <p class="text-danger">
                                                {{ formatCurrency(entry.value) }}
                                            </p>
                                        </td>
                                    </tr>
                                    <tr style="font-weight:bold;text-align:end;">
                                        <td colspan="4" style="text-align:center;">
                                            {{trans('finance.total')}}
                                            <span style="color: #0275d8" class="mdi mdi-comment-question-outline" v-tooltip="trans('finance.period_balance_msg')"></span>
                                        </td>
                                        <td
                                            style="text-align:center;"
                                        >{{ formatCurrency(pageTotalValue()) }}</td>
                                    </tr>
                                </table>                        
                            </div>
                        </div>

                        <div class="card-block">
                            <i class="fa fa-money"></i> 
                            
                            <strong>
                                {{ trans('finance.current_balance') }}
                                <span style="color: #0275d8" class="mdi mdi-comment-question-outline" v-tooltip="trans('finance.current_balance_msg')"></span>:
                            </strong>

                            <span v-if="balance.current_balance >= 0" class="text-success">
                                {{ formatCurrency(balance.current_balance) }}
                            </span>
                            <span v-else class="text-danger">
                                {{ formatCurrency(balance.current_balance) }}
                            </span>
                        </div>

                        <!-- Paginate and counter -->
                        <div class="card-block">
                            <pagination 
                                :data="balance.detailed_balance" 
                                v-on:pagination-change-page="getFinancialSummary"
                                :limit="5"
                            ></pagination>

                            <paginator-counter 
                                :count-items="balance.detailed_balance_count"
                                :total-items="balance.detailed_balance_total"
                                :balance="balance"
                            ></paginator-counter>
                        </div>
                        <!-- Paginate and counter -->

                    </div>                  
                </div>
            </div>
            <!-- Detailed balance -->

            <!-- Future compensations -->
            <div v-else-if="isEmpty(balance.future_compensations)" class="card-block">
                <i class="fa fa-money"></i> <strong>{{ trans('finance.current_balance') }}:</strong>

                <p v-if="balance.current_balance >= 0" class="text-success">
                    {{ formatCurrency(balance.current_balance) }}
                </p>
                <p v-else class="text-danger" >
                    {{ formatCurrency(balance.current_balance) }}
                </p>
            </div>

            <div class="col-lg-12" v-if="!isEmpty(balance.future_compensations)">
                <div class="card">
                    <div class="card-block">
                        <h3 class="card-title">{{ futureCardTitle }}</h3>
                        <div class="card-block">
                            <table class="table table-bordered">
                                <tr>
                                    <th>
                                        {{ trans("finance.finance_date") }}
                                        <i class="fa fa-calendar"></i>
                                    </th>
                                    <th class="hide-small">
                                        {{ trans("finance.finance_time") }}
                                        <i class="fa fa-clock-o"></i>
                                    </th>
                                    <th>
                                        {{ trans("finance.transaction_type") }}
                                        <i class="fa fa-exchange"></i>
                                    </th>
                                    <th class="hide-small">
                                        {{ trans("finance.reason") }}
                                        <i class="fa fa-pencil-square"></i>
                                    </th>
                                    <th>
                                        {{ trans("finance.finance_value") }}
                                        <i class="fa fa-money"></i>
                                    </th>
                                </tr>
                                <tr v-for="entry in balance.future_compensations" v-bind:key="entry.id">
                                    <td>{{ entry.compensation_date | moment("DD/MM/YYYY") }}</td>
                                    <td class="hide-small">{{ entry.compensation_date | moment("hh:mm:ss") }}</td>
                                    <td v-if="entry.reason">{{ trans("finance.op_"+entry.reason.toLowerCase()) }}</td>
                                    <td v-else>{{ trans('finance.reason_not_found') }}</td>
                                    <td class="hide-small">
                                        {{ entry.description }}
                                        <a
                                        v-if="entry.request_id"
                                        target="_blank"
                                        :href="'/admin/request/details/' + entry.request_id"
                                        >{{ entry.request_id }}</a>
                                    </td>
                                    <td v-if="entry.value >=0 ">
                                        <p
                                        class="text-success"
                                        >{{ formatCurrency(entry.value) }}</p>
                                    </td>
                                    <td v-else>
                                        <p
                                        class="text-danger"
                                        >{{ formatCurrency(entry.value) }}</p>
                                    </td>
                                </tr>
                            </table>
                            <i class="fa fa-money"></i>
                            <strong>
                                {{ trans('finance.total_compensations') }}
                                <span style="color: #0275d8" class="mdi mdi-comment-question-outline" v-tooltip="trans('finance.total_compensations_msg')"></span>:
                            </strong>

                            <span
                                v-if="(balance.total_balance - balance.current_balance) >= 0"
                                class="text-success"
                            >{{ formatCurrency((balance.total_balance - balance.current_balance)) }}</span>
                            <span
                                v-else
                                class="text-danger"
                            >{{ formatCurrency((balance.total_balance - balance.current_balance)) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Future compensations -->
        </div>
    </div>
</template>

<script>
import DatePicker from 'vue2-datepicker';
import axios from "axios";
import moment from 'moment';

import ModalEntry from "./modal_entry.vue";
import ModalRequestWithdraw from "./modal_request_withdraw.vue";
import ModalNewBankAccount from "./modal_new_bank_account.vue";

export default {
    props: [
        "Enviroment",
        "holder",
        "loginType",
        "FinanceTypes",
        "bankAccounts",
        "banks",
        "accountTypes",
        "withDrawSettings",
        "currencySymbol",
        "holderType",
        'balanceData',
        'currency'
    ],
    components: {
        modalentry: ModalEntry,
        modalrequestwithdraw: ModalRequestWithdraw,
        modalnewbankaccount: ModalNewBankAccount,
        datepicker: DatePicker
    },    
    data () {
        return {
            isLoading: false,
            fullPage: true,
            loader: "dots",
            color: "#007bff",
            ledger: "",
            type_entry: "0",
            cardTitle: "",
            balance: "",
            language: "",
            dateFormat: "DD/MM/YYYY",
            startDate: "",
            endDate: "",
            page: 1,
            itemsPerPage: 100,
            isDataEmpty: true,
            bank_account_id: 0,
            url: window.location.href            
        }
    },    
    methods:{
        totalize(balance) {
            var totalizer = 0;

            for (var i = 0; i < balance.length; i++) {
                totalizer += parseFloat(balance[i].value);
            }

            return totalizer;
        },
        setTitles() {
            let initialDate = moment(this.startDate).format("DD/MM/YYYY");
            let finalDate = moment(this.endDate).format("DD/MM/YYYY");

            this.cardTitle = this.trans("finance.vue_title_statement");
            this.cardTitle += initialDate;
            this.cardTitle += " " + this.trans("finance.to") + " ";
            this.cardTitle += finalDate;
            this.futureCardTitle = this.trans("finance.title_future_compensation");
        },
        getFinancialSummary(page) {
            if (typeof page === 'undefined' || typeof page === 'object') {
				page = 1;
			}

            // Vue loading
            this.isLoading = true;

            // Trata datas para enviar ao servidor
            let initialDate = moment(this.startDate).format("DD/MM/YYYY");
            let finalDate = moment(this.endDate).format("DD/MM/YYYY");

            // Realiza a requisição
            axios.get('/' + this.Enviroment + '/libs/finance/summary/' + this.holder.id, {
                params: {
                    holder_type: this.holderType,
                    type_entry: this.type_entry,
                    start_date: initialDate,
                    end_date: finalDate,
                    page: page,
                    itemsPerPage: this.itemsPerPage
                }
            })
            .then(response => {
                // Inativa o vue loading
                this.isLoading = false;

                // Atribui valores da requisição à variável
                this.balance = response.data;

                // Totalize method
                this.current_balance = this.totalize(
                    this.balance.detailed_balance.data
                );

                // Verifica se existe compensações para exibir
                if (this.balance.current_compensations.length > 0 || this.balance.future_compensations.length > 0) 
                    this.isDataEmpty = false;
                else 
                    this.isDataEmpty = true;
            })
            .catch(error => {
                // Inativa o vue loading
                this.isLoading = false;

                // Imprime o erro no console
                console.log(error);
            });

            // Monta o título com as datas
            this.setTitles();
        },
        downloadFinancialSummary(page) {
            if (typeof page === 'undefined' || typeof page === 'object') {
				page = 1;
            }

            // Vue loading
            this.isLoading = true;

            // Trata datas para enviar ao servidor
            let initialDate = moment(this.startDate).format("DD/MM/YYYY");
            let finalDate = moment(this.endDate).format("DD/MM/YYYY");

            // Realiza a requisição
            axios.get(window.location.pathname, {
                params: {
                    holder_type: this.holderType,
                    type_entry: this.type_entry,
                    start_date: initialDate,
                    end_date: finalDate,
                    page: page,
                    itemsPerPage: this.itemsPerPage,
                    submit: "Download_Report"
                }
            })
            .then(response => {
                // Inativa o vue loading
                this.isLoading = false;

                var blob = new Blob(["\ufeff" + response.data], {
                    type: "text/csv"
                });

                var url = window.URL.createObjectURL(blob);
                const link = document.createElement("a");

                link.href = url;
                var lastName = this.holder.last_name ? this.holder.last_name : "";
                link.setAttribute("download", this.holder.first_name + " " + lastName + " financial_summary.csv"); // alterar nome do download
                document.body.appendChild(link);

                link.click();
            })
            .catch(error => {
                // Inativa o vue loading
                this.isLoading = false;

                // Imprime o erro no console
                console.log(error);
            });
        },
        showModalEntry() {
            $("#modal-entry").modal("show");
        },
        showModalRequestWithdraw() {
            if (this.bank_account_id == 0 && this.bankAccounts.length > 0) {
                this.bank_account_id = this.bankAccounts[0].id;
            }
            
            $("#modal-request-withdraw").modal("show");
        },        
        showModalNewBankAccount() {
            $("#modal-request-withdraw").modal("hide");
            $("#modal-new-bank-account").modal("show");
        },
        ModalNewBankClosed(newBankAccount) {
            if (newBankAccount != "" && newBankAccount.id != "") {
                this.bankAccounts.push(newBankAccount);
                this.bank_account_id = newBankAccount.id;
            }

            $("#modal-new-bank-account").modal("hide");
            $("#modal-request-withdraw").modal("show");
        },        
        reloadPage() {
            this.$swal(this.trans("finance.transaction_add_success")).then(result => {
                location.reload();
            });
        },
        setLocale() {
            this.language = "pt-br";
        },
        setDateFormat(){
            this.dateFormat = "DD/MM/YYYY";
        },
        formatCurrency(value) {
            if (value != undefined || value != "") {
                let formattedValue = ""
                if (this.currency == "PYG") {
                    let val = (value / 1).toFixed().replace('.', ',')
                    formattedValue = this.currencySymbol + " " + val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "")
                } else {
                    let val = (value / 1).toFixed(2).replace('.', ',')
                    formattedValue = this.currencySymbol + " " + val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                }
                return formattedValue
            } else {
                return "";
            }
        },
        pageTotalValue() {
            const total = this.balance.current_compensations.reduce(function(prev, cur) {
                return prev + cur.value;
            }, 0);

            return total;
        }
    },
    created() {
        this.finance_types = this.FinanceTypes ? JSON.parse(this.FinanceTypes) : null;

        // Seta os dados iniciais das datas
        this.startDate = moment().format('YYYY-MM-DD HH:mm:ss');
        this.endDate = moment().format('YYYY-MM-DD HH:mm:ss');

        // Obtém language padrão
        this.setLocale();

        // Obtém formato de data padrão
        this.setDateFormat();
        
        // Monta título
        this.setTitles();

        // Obtém extrato da conta inicial
        this.getFinancialSummary();
    },  
    mounted() {
        // 
    },    
}
</script>

<style>
@media (max-width: 720px) {
  .small-pd {
    padding-right: 0 !important;
    padding-left: 0 !important;
  }
  .hide-small {
    display: none !important;
  }
  .page-titles {
    margin-left: 10px;
  }
  .btn {
      margin: 5px;
  }
  .card-block {
      padding-left: 7px;
      padding-right: 7px;
  }
}
.datepicker-box{width:100%;}
.mx-input{height: 38px !important;}

.tooltip {
  display: block !important;
  z-index: 10000;
}

.tooltip .tooltip-inner {
  background: black;
  color: white;
  border-radius: 16px;
  padding: 5px 10px 4px;
}

.tooltip .tooltip-arrow {
  width: 0;
  height: 0;
  border-style: solid;
  position: absolute;
  margin: 5px;
  border-color: black;
}

.tooltip[x-placement^="top"] {
  margin-bottom: 5px;
}

.tooltip[x-placement^="top"] .tooltip-arrow {
  border-width: 5px 5px 0 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  bottom: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="bottom"] {
  margin-top: 5px;
}

.tooltip[x-placement^="bottom"] .tooltip-arrow {
  border-width: 0 5px 5px 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-top-color: transparent !important;
  top: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="right"] {
  margin-left: 5px;
}

.tooltip[x-placement^="right"] .tooltip-arrow {
  border-width: 5px 5px 5px 0;
  border-left-color: transparent !important;
  border-top-color: transparent !important;
  border-bottom-color: transparent !important;
  left: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[x-placement^="left"] {
  margin-right: 5px;
}

.tooltip[x-placement^="left"] .tooltip-arrow {
  border-width: 5px 0 5px 5px;
  border-top-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  right: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[aria-hidden='true'] {
  visibility: hidden;
  opacity: 0;
  transition: opacity .15s, visibility .15s;
}

.tooltip[aria-hidden='false'] {
  visibility: visible;
  opacity: 1;
  transition: opacity .15s;
}
</style>