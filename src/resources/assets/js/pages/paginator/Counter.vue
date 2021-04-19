<template>
    <div>
        <p>{{ trans('finance.indexing') }} {{ dataCountItems }} {{trans('finance.of')}} {{ dataTotalItems }} {{trans('finance.items')}}.</p>
    </div>
</template>

<script>
export default {
    props: [
        'countItems',
        'totalItems',
        'translate',
        'balance'
    ],
    data() {
        return {
            dataCountItems: "",
            dataTotalItems: ""
        }
    },
    watch: {
        balance: function (item) {
            this.dataCountItems = item.detailed_balance_count;
            this.dataTotalItems = item.detailed_balance_total;
        }
    },
    created() {        
        // Seta valores padrão para as variáveis
        this.dataCountItems = this.countItems;
        this.dataTotalItems = this.totalItems;
    }
}
</script>