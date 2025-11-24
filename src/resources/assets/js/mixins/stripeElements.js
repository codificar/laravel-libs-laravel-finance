/**
 * Mixin reutilizável para integração com Stripe Elements
 * 
 * Fornece métodos e propriedades para inicializar e usar Stripe Elements
 * quando o gateway padrão for Stripe.
 * 
 * @mixin StripeElementsMixin
 */
export default {
  data() {
    return {
      stripe: null,
      elements: null,
      cardElement: null,
      stripeError: null,
      isStripeInitialized: false,
    };
  },
  
  methods: {
    /**
     * Inicializa Stripe.js com a chave pública
     * 
     * @param {string} publishableKey - Chave pública do Stripe
     * @returns {boolean} - true se inicializado com sucesso
     */
    initStripe(publishableKey) {
      if (!publishableKey) {
        console.warn('Stripe publishable key não fornecida');
        return false;
      }

      if (typeof Stripe === 'undefined') {
        console.error('Stripe.js não está carregado. Certifique-se de incluir <script src="https://js.stripe.com/v3/"></script>');
        return false;
      }

      try {
        this.stripe = Stripe(publishableKey);
        this.elements = this.stripe.elements();
        this.isStripeInitialized = true;
        return true;
      } catch (error) {
        console.error('Erro ao inicializar Stripe:', error);
        return false;
      }
    },

    /**
     * Cria e monta o CardElement do Stripe
     * 
     * @param {string} elementId - ID do elemento HTML onde o CardElement será montado
     * @param {object} options - Opções de estilo para o CardElement
     * @returns {boolean} - true se criado com sucesso
     */
    mountCardElement(elementId, options = {}) {
      if (!this.isStripeInitialized || !this.elements) {
        console.error('Stripe não foi inicializado. Chame initStripe() primeiro.');
        return false;
      }

      try {
        const defaultOptions = {
          style: {
            base: {
              fontSize: '16px',
              color: '#424770',
              '::placeholder': {
                color: '#aab7c4',
              },
            },
            invalid: {
              color: '#9e2146',
            },
          },
        };

        this.cardElement = this.elements.create('card', {
          ...defaultOptions,
          ...options,
        });

        const element = document.getElementById(elementId);
        if (!element) {
          console.error(`Elemento com ID "${elementId}" não encontrado`);
          return false;
        }

        this.cardElement.mount(`#${elementId}`);
        this.cardElement.on('change', (event) => {
          this.stripeError = event.error ? event.error.message : null;
        });

        return true;
      } catch (error) {
        console.error('Erro ao montar CardElement:', error);
        return false;
      }
    },

    /**
     * Cria um Payment Method usando os dados do CardElement
     * 
     * @param {string} cardHolderName - Nome do portador do cartão
     * @returns {Promise<{success: boolean, paymentMethodId?: string, error?: string}>}
     */
    async createPaymentMethod(cardHolderName) {
      if (!this.stripe || !this.cardElement) {
        return {
          success: false,
          error: 'Stripe não foi inicializado ou CardElement não está montado',
        };
      }

      try {
        const { paymentMethod, error } = await this.stripe.createPaymentMethod({
          type: 'card',
          card: this.cardElement,
          billing_details: {
            name: cardHolderName || '',
          },
        });

        if (error) {
          return {
            success: false,
            error: error.message,
          };
        }

        return {
          success: true,
          paymentMethodId: paymentMethod.id,
        };
      } catch (error) {
        return {
          success: false,
          error: error.message || 'Erro ao criar Payment Method',
        };
      }
    },

    /**
     * Limpa e desmonta o CardElement
     */
    unmountCardElement() {
      if (this.cardElement) {
        try {
          this.cardElement.unmount();
          this.cardElement = null;
        } catch (error) {
          console.error('Erro ao desmontar CardElement:', error);
        }
      }
    },

    /**
     * Verifica se o gateway padrão é Stripe
     * 
     * @param {string} defaultPayment - Gateway de pagamento padrão
     * @returns {boolean}
     */
    isStripeGateway(defaultPayment) {
      return defaultPayment === 'stripe';
    },
  },

  /**
   * Cleanup ao destruir o componente
   */
  beforeDestroy() {
    this.unmountCardElement();
  },
};

