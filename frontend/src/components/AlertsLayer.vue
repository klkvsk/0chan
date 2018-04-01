<template>
    <div class="alerts-wrapper">
        <transition-group name="alerts-list" tag="div">
        <div v-for="alert in alerts"
             class="alerts-item"
             :key="alert"
             :class="{ 'alert': true,
                       'alert-success': alert.type == 'success',
                       'alert-info':    alert.type == 'info',
                       'alert-danger':  alert.type == 'error'
            }">
            <i :class="{ 'fa': true,
                         'fa-check-circle-o':       alert.type == 'success',
                         'fa-info-circle':          alert.type == 'info',
                         'fa-exclamation-circle':   alert.type == 'error'
            }"></i>
            <span style="white-space: pre-wrap">{{alert.text}}</span>
        </div>
        </transition-group>
    </div>
</template>

<script>
    import $ from 'jquery';
    import BusEvents from '../app/BusEvents';

    const TIMEOUT_DEFAULT = 3500;

    export default {
        data() {
            return {
                alerts: [],
            }
        },
        created() {
            this.$bus.on(BusEvents.ALERT_SUCCESS, this.success);
            this.$bus.on(BusEvents.ALERT_INFO, this.info);
            this.$bus.on(BusEvents.ALERT_ERROR, this.error);
        },
        methods: {
            success(text, timeout) {
                this.addAlert('success', text, timeout);
            },
            info(text, timeout) {
                this.addAlert('info', text, timeout);
            },
            error(text, timeout) {
                this.addAlert('error', text, timeout);
            },
            addAlert(type, text, timeout) {
                const alert = { type, text };
                this.alerts.unshift(alert);
                setTimeout(() => { this.closeAlert(alert)}, timeout || TIMEOUT_DEFAULT);
            },
            closeAlert(alert) {
                const idx = this.alerts.indexOf(alert);
                if (idx > -1) {
                    this.alerts.splice(idx, 1);
                }
            },
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import '~assets/styles/_vars';
    .alerts-wrapper {
        position: fixed;
        z-index: 20;
        top: 10px;
        right: 10px;
    }

    .alerts-item {
        box-shadow: 0 1px 4px $color-grey-lt;
        transition: all 0.2s;
        display: block;
        max-width: 80vw;
    }

    .alerts-list-enter, .alerts-list-leave-to
        /* .list-complete-leave-active for <2.1.8 */ {
        opacity: 0;
        transform: translateX(50px);
    }
    .alerts-list-leave-active {
    }
</style>
