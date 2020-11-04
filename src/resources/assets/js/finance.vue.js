window.vue = require('vue');

require('lodash');

import Vue from 'vue';


// Finance settings
import FinanceVuejs from './pages/example.vue';

import VueTheMask from 'vue-the-mask';

import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';

Vue.component('financial-account-statements', require('./pages/finance/FinancialAccountStatement.vue'));
Vue.component('empty-box', require('./pages/empty_box/EmptyBoxComponent.vue'));

Vue.use(VueTheMask);

Vue.use(Loading);
Vue.component('loading', Loading);

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
        financevuejs: FinanceVuejs
    },

    created: function () {
    }
})