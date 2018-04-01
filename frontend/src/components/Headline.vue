<template>
    <div class="headmenu">
        <div class="headmenu-title" ref="title">
            <slot name="title"></slot>
        </div>
        <div class="headmenu-buttons headmenu-buttons-right">
            <slot name="buttons"></slot>
        </div>
        <div class="headmenu-buttons headmenu-buttons-left">
            <button @click="toggleMobile()" class="btn btn-link visible-xs btn-open-sidebar">
                <i class="fa fa-bars"></i>
            </button>
        </div>
    </div>
</template>

<script>
    import BusEvent from '../app/BusEvents'

    export default {
        mounted() {
            const title = this.$refs.title.textContent.trim().replace(/ — /g, ' - ').replace(/(\s+)/g, ' ');
            document.title = title;
        },
        methods: {
            toggleMobile() {
                this.$bus.emit(BusEvent.TOGGLE_SIDEBAR, true);
            },
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .btn-open-sidebar {
        color: $color-black !important;
        font-size: 14px;
    }
    .headmenu {
        background: lighten($color-grey, 25%);
        border-bottom: $color-grey-lt 1px solid;
        height: 39px;
        padding: 2px 10px;
        z-index: 15;
        position: fixed;
        top: 0; left: $sidebar-width; right: 0;
    }

    .slide-fade-enter-active .headmenu {
        left: 0;
    }

    .headmenu .sidemenu-toggle {
        display: none;
    }

    .headmenu-center {
        text-align: center;
    }

    .headmenu-title {
        position: absolute;
        left: 0;
        right: 0;
        margin: auto;
        font-family: 'Open Sans Condensed', sans-serif;
        font-size: 32px;
        font-weight: 300;
        line-height: 1em;
        text-align: center;
        z-index: 0;
        span, a {
            color: $color-black !important;
        }
    }

    .headmenu-buttons {
        position: absolute;
        z-index: 1;

        &-right { right: 3px; }
        &-left  {  left: 3px; }
    }

    @media (max-width: $screen-xs-max) {
    //@include media-breakpoint-down(xs) {
        .headmenu {
            z-index: 15;
            position: fixed;
            left: 0;
        }

        .headmenu-title {
            position: absolute;
            left: 50px;
            right: 0;
            text-align: left;
        }
    }

    @media (min-width: $screen-sm-min) and (max-width: $screen-md-max) {
    //@include media-breakpoint-down(xs) {
        .headmenu-title {
            position: absolute;
            left: 15px;
            right: 0;
            text-align: left;
        }
    }

</style>