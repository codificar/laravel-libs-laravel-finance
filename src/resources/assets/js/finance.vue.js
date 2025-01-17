window.vue = require('vue');

require('lodash');

import Vue from 'vue';

import VueTheMask from 'vue-the-mask';

import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';
import pagination from 'laravel-vue-pagination';
import VueResource from 'vue-resource';
import VueSweetalert2 from 'vue-sweetalert2';
import VTooltip from 'v-tooltip';
import VueClipboard from 'vue-clipboard2'


import FinancialAccountStatement from './pages/finance/FinancialAccountStatement.vue';
Vue.component('financial-account-statement', FinancialAccountStatement);

import statement from './pages/consolidated/statement.vue';
Vue.component('consolidated-statement', statement);

import EmptyBoxComponent from './pages/empty_box/EmptyBoxComponent.vue';
Vue.component('empty-box', EmptyBoxComponent);

import payment from './pages/payment/payment.vue';
Vue.component('payment', payment);

import Counter from './pages/paginator/Counter.vue';
Vue.component('paginator-counter', Counter);

Vue.component('pagination', pagination);
Vue.component('loading', Loading);

import Pix from './pages/payment/pix.vue';
Vue.component('pix', Pix);


Vue.use(VueTheMask);
Vue.use(require('vue-moment'));
Vue.use(VueResource);
Vue.use(VueSweetalert2);
Vue.use(Loading);
Vue.use(VTooltip);
Vue.use(VueClipboard)

//Allows localization using trans()
Vue.prototype.trans = (key) => {
    return _.get(window.lang, key, key);
};
//Tells if an JSON parsed object is empty
Vue.prototype.isEmpty = (obj) => {
    return _.isEmpty(obj);
};


//Main vue instance
new Vue({
    el: '#codificar-finance',

    data: {
    },

    components: {

    },

    created: function () {
    }
})