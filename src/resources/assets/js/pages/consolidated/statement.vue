<template>
  <div class="col-12 tbl-box">

    <!-- Filter -->
    <div class="card card-outline-info">
      <div class="card-header">
        <h4 class="m-b-0 text-white">{{ trans('finance.filter') }}</h4>
      </div>
      <div class="card-block">
        <div class="row">
          <div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label">{{ trans('finance.key_word') }}</label>
              <input v-model="key_word" type="text" class="form-control" :placeholder="trans('finance.key_word')">
						</div>
					</div>

          <div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label">{{ trans('finance.type') }}</label>
              <select v-model="type" class="form-control">
                <option value="">{{ trans('finance.select') }}</option>
                <option value="user">{{ trans('finance.user') }}</option>
                <option value="corp">{{ trans('finance.corp') }}</option>
                <option value="provider">{{ trans('finance.provider') }}</option>
              </select>
						</div>
					</div>

          <div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label">{{ trans('finance.balance') }}</label>
              <select v-model="balance" class="form-control">
                <option value="">{{ trans('finance.select') }}</option>
                <option value="positive">{{ trans('finance.positive') }}</option>
                <option value="negative">{{ trans('finance.negative') }}</option>
              </select>
						</div>
					</div>
        </div>

        <div class="row">
          <div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label">{{ trans('finance.location') }}</label>
              <select v-model="location" class="form-control">
                <option value="">{{ trans('finance.select') }}</option>
                <option v-for="(item, index) in Locations" :key="index" :value="item.id">{{ item.name }}</option>
              </select>
						</div>
					</div>

          <div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label">{{ trans('finance.partner') }}</label>
              <select v-model="partner" class="form-control">
                <option value="">{{ trans('finance.select') }}</option>
                <option v-for="(item, index) in Partners" :key="index" :value="item.id">{{ item.name }}</option>
              </select>
						</div>
					</div>

          <div class="col-md-4 col-sm-12">
						<div class="form-group">
							<label class="control-label">{{ trans('finance.ocurrency') }}</label>
              <div class="input-daterange input-group" id="date-range">
                <datepicker v-model="startDate" input-class="dateStyle" class="form-control" lang="en" format="yyyy-MM-dd"></datepicker>
                <span class="input-group-addon bg-info b-0 text-white">to</span>
                <datepicker v-model="endDate" input-class="dateStyle" class="form-control" lang="en" format="yyyy-MM-dd"></datepicker>
              </div>
						</div>
					</div>
        </div>

        <div class="row">
					<div class="col-sm-12">
            <div class="box-footer">
              <div class="pull-right">
								<button @click="download" type="button" class="btn btn-info right">
									<i class="mdi mdi-download"></i> {{ trans('finance.down_report') }}
								</button>
								<button @click="fetch" type="button" class="btn btn-success">
									<i class="fa fa-search"></i> {{ trans('finance.search') }}
								</button>
							</div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- clients_notify -->
    <div class="card card-outline-info">
      <div class="card-header">
        <h4 class="m-b-0 text-white">{{ trans('finance.clients_notify') }}</h4>
      </div>
      <div class="card-block">

        <form action="/admin/notifications/debit_notification_all" method="post">
          <div class="row">
            <div class="col-md-6 col-sm-12">
              <div class="form-group">
                <label class="control-label">{{ trans('finance.title') }}</label>
                <input required type="text" class="form-control" name="msg_title" :placeholder="trans('finance.title')">
              </div>
            </div>
            <div class="col-md-6 col-sm-12">
              <div class="form-group">
                <label class="control-label">{{ trans('finance.message') }}</label>
                <input required type="text" class="form-control" name="msg_body" :placeholder="trans('finance.message')">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="box-footer">
                <div class="pull-right">
                  <button type="submit" class="btn btn-success">
                    <i class="fa fa-search"></i> {{ trans('finance.send_notification') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </form>
        
      </div>
    </div>

    <!-- table -->
    <div class="card card-outline-info">
      <div class="card-header">
        <h4 class="m-b-0 text-white">{{ trans("finance.consolidated_extract") }}</h4>
      </div>
      <div class="card-block">
        <pagination :data="consolidated_data" @pagination-change-page="fetch" ></pagination> 

        <table class="table table-bordered">
            <tr>
              <th>{{ trans("finance.ledger_id") }}</th>
              <th>{{ trans("finance.name") }}</th>
              <th>{{ trans("finance.type") }}</th>
              <th>{{ trans("finance.period_requests_count") }}</th>
              <th>{{ trans("finance.total_ro_receive") }}</th>
              <th>{{ trans("finance.future_balance") }}</th>
              <th>{{ trans("finance.current_balance") }}</th>
              <th>{{ trans("finance.hit_value") }}</th>
              <th>{{ trans("finance.actions") }}</th>
            </tr>
            <tr v-for="(item, index) in consolidated_data.data" :key="index">
              <td>{{ item.ledger_id }}</td>
              <td>{{ item.user_name }}</td>
              <td>{{ item.user_type }}</td>
              <td>{{ item.balances.period_request_count }}</td>
              <td>
                <span :class="[item.balances.total_balance >= 0 ? 'text-success' : 'text-danger']">
                  {{ item.balances.total_balance_text }}
                  </span>
                </td>
              <td>
                <span :class="[item.balances.future_balance >= 0 ? 'text-success' : 'text-danger']">
                  {{ item.balances.future_balance_text }}
                </span>
              </td>
              <td>
                <span :class="[item.balances.current_balance >= 0 ? 'text-success' : 'text-danger']">
                  {{ item.balances.current_balance_text }}
                </span>
              </td>
              <td>
                <span v-if="item.balances.payment_value >= 0" :class="[item.balances.payment_value >= 0 ? 'text-success' : 'text-danger']">
                  {{ item.balances.payment_value_text }}
                </span>
                <span v-else>
                  {{ item.balances.payment_value_text }}
                </span>
              </td>
              <td>
                <div class="dropdown">
                    <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown">
                        {{ trans('finance.action_grid') }}
                        <span class="caret"></span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenu1">
                      <!-- account_extract -->
                      <a class="dropdown-item" tabindex="-1" :href="item.extract_url">{{ trans('finance.account_statement') }}</a>

                      <!-- debit_notification -->
                      <a class="dropdown-item" tabindex="-1" :href="'/admin/notifications/debit_notification/' + item.ledger_id">{{ trans('finance.debit_notification') }}</a>
                    </div>
                </div>
              </td>
            </tr>
        </table>

        <pagination :data="consolidated_data" @pagination-change-page="fetch" ></pagination> 
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import Datepicker from 'vuejs-datepicker';

export default {
  props: [
    "Locations",
    "Partners"
  ],
  components: {
      Datepicker
  },
  data() {
    return {
      consolidated_data: {},
      key_word: '',
      type: '',
      balance: '',
      location: '',
      partner: '',
      startDate: '',
      endDate: ''
    }
  },
  methods: {
    async fetch(page = 1) {
      const { data } = await axios.get('/admin/libs/finance/consolidated_extract/fetch', {
        params: {
          page: isNaN(page) ? 1 : page,
          key_word: this.key_word,
          type: this.type,
          balance: this.balance,
          location: this.location,
          partner: this.partner,
          startDate: this.startDate,
          endDate: this.endDate
        }
      });

      this.consolidated_data = data.consolidated;

      console.log(this.consolidated_data);
    },
    async download() {
      try {
        const response = await axios.get('/admin/libs/finance/consolidated_extract/download', {
          params: {
            key_word: this.key_word,
            type: this.type,
            balance: this.balance,
            location: this.location,
            partner: this.partner,
            startDate: this.startDate,
            endDate: this.endDate
          }
        });

        var blob = new Blob(["\ufeff" + response.data], {
            type: "text/csv"
        });

        var url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = url;
        link.setAttribute("download", "extrato-consolidado.csv");
        document.body.appendChild(link);

        link.click();
      } catch (error) {
        
      }
    }
  },
  created() {
    this.fetch();
    console.log(this.Locations);
  }
}
</script>

<style>
.dateStyle {
    width: 100%;
}
</style>