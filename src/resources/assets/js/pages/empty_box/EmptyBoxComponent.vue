<template>
    <div class="col-lg-12">
        <div class="card card-outline-info box">
            <img class="box-picture" src="/images/box.svg" alt="">
            <h2>{{ trans('finance.no_data_found') }}</h2>
            <p>{{ trans('finance.try_again_later') }}</p>
        </div>
    </div>
</template>

<script>
export default {
    }
</script>

<style scoped>
    .box{
        display: flex;
        flex: 1;
        align-content: center;
        align-items: center;
        justify-content: center;
        text-align: center;
        flex-direction: column;
    }
    .box-picture{width: 120px;}
</style>