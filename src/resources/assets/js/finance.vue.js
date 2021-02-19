window.vue = require('vue');

require('lodash');

import Vue from 'vue';

import VueTheMask from 'vue-the-mask';

import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';
import pagination from 'laravel-vue-pagination';
import VueResource from 'vue-resource';
import VueSweetalert2 from 'vue-sweetalert2';


Vue.component('financial-account-statement', require('./pages/finance/FinancialAccountStatement.vue'));
Vue.component('payment', require('./pages/payment/payment.vue'));
Vue.component('paginator-counter', require('./pages/paginator/Counter.vue'));
Vue.component('pagination', pagination);
Vue.component('loading', Loading);


Vue.use(VueTheMask);
Vue.use(require('vue-moment'));
Vue.use(VueResource);
Vue.use(VueSweetalert2);
Vue.use(Loading);


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
    el: '#VueJs',

    data: {
    },

    components: {

    },

    created: function () {
    }
})