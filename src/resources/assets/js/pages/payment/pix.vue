<script>
import axios from "axios";
import Echo from 'laravel-echo';
import Qrcode from "v-qrcode";

export default {
  props: [
    "Enviroment",
    "LaravelEchoPort",
    "TransactionId",
    "PixCopyPaste",
    "PixBase64",
    "Value"
  ],
  data() {
    return {
      copied: false
    };
  },
  components: {
    Qrcode
  },
  methods: {
    onCopy: function (e) {
      this.copied = true;
      var that = this;
      setTimeout(function() { //depois de 2 segundos, resetar a variavel copied
        that.copied = false;
      }, 2000);
    },
    onError: function (e) {
      alert('Error')
    },

    retrievePix() {
      new Promise((resolve, reject) => {
        axios.get('pix/retrieve?transaction_id=' + this.TransactionId, {}).then(response => {
          if(response.data.paid) {
            this.$swal({
              title: this.trans("finance.payment_creditcard_success"),
              type: "success",
            }).then((result) => {
              window.history.back();
            });
          }
        })
        .catch(error => {
          console.log(error);
        });
      });
    },
    subscribeToChannelPix() {
      var vm = this;
      window.Echo.channel('pix.' + this.TransactionId).listen('.pixUpate', e => {
        vm.retrievePix();
      });
    },
  },

  mounted() {

    this.retrievePix(); // ao entrar na tela, verifica se o pagamento ja foi realizado

		window.Echo = new Echo({
			broadcaster: 'socket.io',
			client: require('socket.io-client'),
			host: window.location.hostname + ":" + this.LaravelEchoPort
		});

		window.io = require('socket.io-client');
		
		this.subscribeToChannelPix();

    var vm = this;
    // verificacao extra de 30 em 30 segundos, check se pagamento foi realizado (nos casos em que o socket tenha problemas)
    setInterval(function(){
      vm.retrievePix();
    },30000);
	},
  created() {
    
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
              {{ trans("finance.pix") }}
            </h3>
          </div>
        </div>
      </div>

      <div class="card-block">
        <div class="container">
          <div class="row">
            <div class="col-sm-8">
              <h4 style="margin: 5px;">{{ trans("finance.pix_info_1") }}</h4>
              <div class="d-flex flex-row" style="margin-top: 10px">
                <div class="circle"><div class="text"><b>1</b></div></div>
                <p class="instructions">{{ trans("finance.pix_info_2") }}</p>
              </div>
              <div class="d-flex flex-row" style="margin-top: 10px">
                <div class="circle"><div class="text"><b>2</b></div></div>
                <p class="instructions">{{ trans("finance.pix_info_3") }}</p>
              </div>
              <div class="d-flex flex-row" style="margin-top: 10px">
                <div class="circle"><div class="text"><b>3</b></div></div>
                <p class="instructions">{{ trans("finance.pix_info_4") }}</p>
              </div>
            </div>
            <div class="col-sm-4">
              <qrcode
                v-if="PixBase64"
                :size="200"
                :value="PixBase64"
              ></qrcode>
            </div>  
          </div>
          <div class="row">
            <div class="offset-sm-3 col-sm-4">
              <h3 style="text-align: center">{{Value}}</h3>
            </div> 
            
          </div> 

          <div class="row">
            <div class="offset-sm-3 col-sm-4">
              <input onClick="this.select();  " class="form-control" value="Sample Text" v-model="PixCopyPaste" />
            </div> 
            <button 
              type="button"
              :class="copied ? 'btn btn-success' : 'btn btn-info'"
              v-clipboard:copy="PixCopyPaste"
              v-clipboard:success="onCopy"
              v-clipboard:error="onError">
              {{ copied ? trans("finance.copied") : trans("finance.copy") }}
            </button>
          </div>  
          
          
        </div>
        <br />
      </div>
      
    </div>
  </div>
</template>

<style>
.circle {
  background: #2c8aff;
  width: 30px !important;
  height: 30px;
  border-radius: 50%;
  padding:8px !important;
  position:relative;
  margin-right: 5px !important;
}
.text{
  transform:translate(-50%,-50%);
  position:absolute;
  top:50%;
  left:50%;
  color: white;
}
.instructions {
  margin: 5px;
}
</style>