<template>
    <div id="app">
        <vue-progress-bar></vue-progress-bar>
        <AlertsLayer />
        <CaptchaLayer />
        <ModalsLayer />
        <SidebarLayer id="sidebar" :class="{blurred: blurred}" />
        <ContentLayer id="content" :class="{blurred: blurred, [contentClass]: true}" />
    </div>
</template>

<script>
    import moment from 'moment';
    import BusEvents from '../app/BusEvents';
    import CaptchaLayer from './CaptchaLayer.vue';
    import ModalsLayer  from './ModalsLayer.vue';
    import AlertsLayer  from './AlertsLayer.vue';
    import ContentLayer from './ContentLayer.vue';
    import SidebarLayer from './SidebarLayer.vue';

    import $ from 'jquery'

    export default {
        name: 'app',
        components: {
            CaptchaLayer,
            ModalsLayer,
            AlertsLayer,
            SidebarLayer,
            ContentLayer
        },
        data() {
            return {
                blurred: false,
                contentClass: null,
            }
        },
        watch: {
            $route() {
                this.checkContentClass();
            }
        },
        created() {
            this.$bus.on(BusEvents.SET_BLUR, blurred => this.blurred = blurred);
            this.checkContentClass();

            $(document).click(event => {
                if (event.target && event.target.tagName && event.target.tagName.toLowerCase() === 'address') {
                    const address = event.target.innerText;
                    if (address.match(/0x[0-9a-f]{8}/i)) {
                        this.$router.push({ name: 'im_start', params: { toAddress: address } });
                    }
                }
            });
        },
        mounted() {
            if (!(""+document.cookie).match(/welcome_text/)) {
                setTimeout(() => {
                    this.$bus.emit(BusEvents.ALERT_SUCCESS, 'Добро пожаловать! Снова.');
                    const cookieDate = moment().add(7, 'days').toDate();
                    document.cookie = "welcome_text=true; expires=" + cookieDate.toUTCString();
                }, 1500);
            }
        },
        methods: {
            checkContentClass() {
                this.contentClass = null;
                for (let comp of this.$router.getMatchedComponents()) {
                    if (comp.contentClass) {
                        this.contentClass = comp.contentClass;
                    }
                }
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "../assets/styles/global.scss";

    #sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        width: $sidebar-width;
    }

    #content {
        position: relative;
        min-height: 100vh;
        margin-left: $sidebar-width;
        background: #eeeeee url("~assets/images/bg-light.png") repeat;
        > * { padding: 40px 15px; }
    }


    @media (max-width: $screen-xs-max) {
        //@include media-breakpoint-down(xs) {
        #sidebar {
            z-index: 999;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        #content {
            margin-left: 0;
            background: #eeeeee url("~assets/images/bg-light.png") repeat;
            > * { padding: 40px 4px; }
        }

        .panel {
            .panel-body, .panel-heading, .panel-footer {
                padding: 8px;
            }
        }
    }

</style>
