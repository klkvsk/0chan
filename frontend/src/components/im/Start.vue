<template>
    <div class="panel panel-default">
        <div class="panel-heading">
            <a class="fa fa-arrow-left" @click="$router.go(-1)"></a>
            <b style="padding-left: 20px">
                Начало диалога
            </b>
        </div>
        <div class="panel-body">

            <div v-if="notFound" class="empty-page">
                <h4>Адрес <span class="im-address im-grey">{{toAddress}}</span> не найден</h4>
                <br>
                Либо он удален, либо не существовал никогда.
            </div>

            <div v-else-if="ready" class="center-block" style="max-width: 800px">
                <form  @submit.prevent="gotoDialog" class="form">
                    <div class="form-group">
                        <b>Начать диалог с:</b>
                        <div class="vspace list-group-item">
                            <b>
                                <span class="im-address">{{to.address}}</span>
                                {{to.name}}
                            </b>
                        </div>
                    </div>
                    <br>
                    <div class="form-group">
                        <b>От имени:</b>
                        <div class="vspace list-group">
                            <router-link v-for="startAs in as" :key="startAs.address" class="list-group-item"
                                         :to="{ name: 'im_dialog', params: { toAddress: to.address, asAddress: startAs.address } }">
                                <div class="row">
                                    <div class="col-md-12 list-group-item-heading">
                                        <span class="im-address">{{startAs.address}}</span>
                                        {{startAs.name}}
                                    </div>
                                    <div class="col-md-12 list-group-item-text text-right">
                                    <span v-if="startAs.last" class="color-grey-dk" :title="startAs.last | timestamp">
                                        посл. сообщение: {{ startAs.last | timeago }}
                                    </span>
                                        <span v-else class="color-grey-lt">
                                        нет предыдущих сообщений
                                    </span>
                                    </div>
                                </div>
                            </router-link>
                            <a href="#" @click.prevent="showAddIdentity" class="list-group-item">
                                <i class="fa fa-plus"></i> Создать новую личность
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div v-else class="empty-page">
                <i class="fa fa-spinner fa-pulse fa-3x"></i>
            </div>
        </div>
    </div>
</template>

<script>
    import AddIdentity from './AddIdentity.vue'
    import Dialog from '../../services/Dialog'
    import BusEvents from '../../app/BusEvents'

    export default {
        props: [ 'toAddress' ],
        data() {
            return {
                to: null,
                as: [],
                asAddress: null,
                notFound: false,
                ready: false,
            }
        },
        methods: {
            fetch() {
                this.notFound = false;
                this.ready = false;
                return Dialog.start(this.toAddress).then(
                    response => {
                        if (response.data.ok) {
                            this.to = response.data.to;
                            this.as = response.data.as;
                            if (this.as.length > 0) {
                                this.asAddress = this.as[0].address;
                            }
                        } else {
                            this.notFound = true;
                        }
                        this.ready = true;
                    }
                );
            },
            showAddIdentity() {
                this.$bus.emit(BusEvents.SHOW_MODAL, AddIdentity)
            },
            gotoDialog() {
                this.$router.push({ name: 'im', params: { to: this.toAddress, as: this.asAddress } })
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    .dialog-start {
        margin: 40px auto;
        width: 300px;
    }
</style>