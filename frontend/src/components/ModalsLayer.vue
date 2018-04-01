<template>
    <div class="modals-wrapper1" v-if="visible" @click="closeTopModal">
        <div v-for="modal in modals" class="modals-wrapper2">
            <div class="modals-wrapper3">
                <button @click="closeModal(modal)" class="close"><i class="fa fa-fw fa-times"></i></button>
                <component :is="modal.component" v-bind="modal.props" @close="closeModal(modal)" class="modals-content"></component>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from 'jquery';
    import BusEvents from '../app/BusEvents';

    export default {
        data() {
            return {
                modals: []
            }
        },
        computed: {
            visible() { return this.modals.length > 0 }
        },
        watch: {
            visible(isVisible) {
                this.$bus.emit(BusEvents.SET_BLUR, isVisible);
            }
        },
        created() {
            this.$bus.on(BusEvents.SHOW_MODAL, this.showModal);
        },
        methods: {
            showModal(component, props) {
                this.modals.unshift({ component, props });
            },
            closeModal(modal) {
                const idx = this.modals.indexOf(modal);
                if (idx > -1) {
                    this.modals.splice(idx, 1);
                }
            },
            closeTopModal(event) {
                if ($(event.target).closest('.modals-content').length > 0) {
                    return;
                }
                this.modals.splice(-1);
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    .modals-wrapper1 {
        position: fixed;
        height: 100%;
        width: 100%;
        z-index: 1;
        display: table;
    }

    .modals-wrapper2 {
        display: table-cell;
        vertical-align: middle;
        text-align: center;

        .modals-wrapper3 {
            position: relative;
            display: inline-block;
            margin-left: auto;
            margin-right: auto;
            text-align: start;

            > button.close {
                position: absolute;
                right: 10px;
                top: 10px;
            }
        }
    }
</style>
