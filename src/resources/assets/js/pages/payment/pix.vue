<script>
import axios from "axios";
export default {
  props: [
    "Enviroment",
    "GatewayTransactionId",
    "PixCopyPaste",
    "PixBase64",
    "Value"
  ],
  data() {
    return {
      copied: false
    };
  },
  components: {},
  methods: {
    onCopy: function (e) {
      this.copied = true;
      var that = this;
      setTimeout(function() { //depois de 2 segundos, resetar a variavel copied
        that.copied = false;
      }, 2000);
    },
    onError: function (e) {
      alert('Erro ao copiar')
    },

    retrievePix() {
      new Promise((resolve, reject) => {
        axios.get('pix/retrieve/' + this.GatewayTransactionId, {}).then(response => {
          if(response.data.paid) {
            this.$swal({
              title: 'Pagamento confirmado!',
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
    }
  },
  mounted() {
    this.retrievePix(); // ao entrar na tela, verifica se o pagamento ja foi realizado
    var that = this;
    //verificar de 15 em 15 segundos se o pagamento foi realizado
    setInterval(function(){
      that.retrievePix();
    },15000);
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
              <h4 style="margin: 5px;">Pague com Pix e receba a confirmação na hora</h4>
              <div class="d-flex flex-row" style="margin-top: 10px">
                <div class="circle"><div class="text"><b>1</b></div></div>
                <p class="instructions">Abra o app da sua instituição financeira</p>
              </div>
              <div class="d-flex flex-row" style="margin-top: 10px">
                <div class="circle"><div class="text"><b>2</b></div></div>
                <p class="instructions">Faça um Pix lendo o QR Code ou copiando o código para pagamento</p>
              </div>
              <div class="d-flex flex-row" style="margin-top: 10px">
                <div class="circle"><div class="text"><b>3</b></div></div>
                <p class="instructions">Revise as informações, aguarde a confirmação e pronto!</p>
              </div>
            </div>
            <div class="col-sm-4">
              <img width="200" height="200" :src="'data:image/png;base64, ' + PixBase64" />
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
              {{ copied ? 'Copiado!' : 'Copiar' }}
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
  width: 30px;
  height: 30px;
  border-radius: 50%;
  padding:8px;
  position:relative;
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