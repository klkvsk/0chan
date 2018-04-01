<template>
    <div v-if="identities" class="panel panel-default">
        <div class="panel-body">
            <div class="text-center" style="margin: 40px 0">
                <form @submit.prevent="startDialog" class="form form-inline">
                    <div class="form-group">
                        Начать диалог с:
                        <span class="input-group">
                            <input type="text" class="form-control" v-model="toAddress" required placeholder="0x........"/>
                            <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-arrow-right"></i>
                            </button>
                            </span>
                        </span>
                    </div>
                </form>
            </div>

            <div class="row center-block" style="max-width: 600px">
                <table class="table">
                    <thead>
                    <tr>
                        <td colspan="2">
                            Мои личности:
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="id in identities">
                        <td>
                            <span class="im-address" v-clipboard="id.address" @success="addressCopied">{{id.address}}</span>
                        </td>
                        <td width="100%">
                            <div class="pull-right">
                                <a class="im-button btn-default fa fa-search" @click.prevent="$emit('search', id.address)"></a>
                                <a class="im-button btn-default fa fa-clipboard"  v-clipboard="id.address" @success="addressCopied"></a>
                                <a class="im-button btn-default fa fa-link"  v-clipboard="id.link" @success="linkCopied"></a>
                                <a class="im-button btn-default fa fa-trash" @click.prevent="deleteIdentity(id.address)"></a>
                            </div>
                            {{id.name}}
                        </td>
                    </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <div class="add-identity" @click="showAddIdentity">
                                    <i class="fa fa-plus fa-fw"></i> Добавить еще одну личность
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row text-muted center-block" style="max-width: 600px; padding: 20px 40px 50px">
                Подсказка:
                <ul class="vspace">
                    <li>Чтобы оставить адрес в посте, нужно просто вставить его как есть.</li>
                    <li>
                        Чтобы оставить адрес на другом сайте или передать иным способом,
                        можно использовать ссылку вида: {{exampleLink}}</li>
                </ul>
            </div>

        </div>

    </div>
</template>

<script>
    import Dialog from '../../services/Dialog'
    import AddIdentity from './AddIdentity.vue'
    import BusEvents from '../../app/BusEvents'

    export default {
        data() {
            return {
                identities: null,
                toAddress: '',
            }
        },
        computed: {
            exampleLink() {
                return this.identities[0].link;
            }
        },
        methods: {
            fetch() {
                return Dialog.identities().then(
                    response => {
                        if (response.data.identities.length) {
                            this.identities = response.data.identities.map(id => {
                                const route = this.$router.resolve({ name: 'im_address', params: { address: id.address } });
                                id.link = window.location.protocol + '//' + window.location.host + route.href;
                                return id;
                            });
                        } else {
                            this.$router.push({name: 'im_init'});
                        }
                    }
                )
            },
            startDialog() {
                this.$router.push({ name: 'im_start', params: { toAddress: this.toAddress } })
            },
            showAddIdentity() {
                this.$bus.emit(BusEvents.SHOW_MODAL, AddIdentity)
            },
            deleteIdentity(address) {
                if (!confirm('Личность удаляется окончательно с отвязкой адреса от аккаунта. Восстановить нельзя. Удаляем?')) return;
                return Dialog.deleteIdentity(address).then(
                    response => {
                        this.$bus.emit(BusEvents.REFRESH_CONTENT)
                    }
                )
            },
            addressCopied() {
                this.$bus.emit(BusEvents.ALERT_INFO, 'Адрес скопирован в буфер')
            },
            linkCopied() {
                this.$bus.emit(BusEvents.ALERT_INFO, 'Ссылка скопирована в буфер')
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import '~assets/styles/_vars';

    .im-button {
        display: inline-block;
        text-align: center;
        color: $color-grey-lt !important;
        &:hover {
            background: none !important;
            color: $color-green !important;
        }
        width: 32px;
    }

    .im-address {
        cursor: pointer;
    }

    .add-identity {
        cursor: pointer;
        color: $color-grey;
        transition: color .2s;
        &:hover {
            color: $color-grey-dk;
        }
    }

</style>