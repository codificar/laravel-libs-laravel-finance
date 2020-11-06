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
        'translate'
    ],
    data() {
        return {
            dataCountItems: "",
            dataTotalItems: ""
        }
    },
    created() {        
        // Seta valores padrão para as variáveis
        this.dataCountItems = this.countItems;
        this.dataTotalItems = this.totalItems;
    }
}
</script>