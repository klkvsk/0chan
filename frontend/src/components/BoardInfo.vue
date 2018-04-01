<template>
    <div class="block board-info">
        <p class="lead">/{{board.dir}}/ &mdash; {{board.name}}</p>
        <p v-html="board.descriptionHtml"></p>
    </div>
</template>

<script>
    import Moderation from '../services/Moderation'
    import BusEvents from '../app/BusEvents'

    export default {
        props: [ 'board' ],
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import '~assets/styles/_vars';

    .board-info.block {
        width: 600px;
        @media(max-width: $screen-xs-max) {
            width: 80vw;
        }
    }
</style>