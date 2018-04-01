<template>
    <div>
        <Headline>
            <span slot="title">Глобальные модераторы</span>
        </Headline>

        <div class="panel panel-default vspace" v-if="globals !== null">
            <div class="panel-body">
                <div class="pull-right">
                    <form class="vspace form-inline" @submit.prevent="add">
                        <div class="form-group">
                            <label class="control-label hidden-xs hidden-sm">Добавить:</label>
                            <div class="input-group">
                                <input type="text" v-model="newModeratorLogin" placeholder="логин модератора" class="form-control"
                                       :disabled="isAdding"/>

                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-primary" :disabled="isAdding">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" v-model="newModeratorIsAdmin" class="form-control"
                                           :disabled="isAdding" />
                                    админ
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="clearfix"></div>

                <div class="table-responsive">
                    <table v-if="globals.length" class="table vspace">
                        <thead>
                        <tr>
                            <th>Логин</th>
                            <th>Роль</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="global in globals">
                            <td>{{global.login}}</td>
                            <td>{{global.role }}</td>
                            <td align="right">
                                <div class="btn-group">
                                <span class="btn btn-default" @click="remove(global)">
                                    <i v-if="!global.isRemoving" class="fa fa-trash"></i>
                                    <i v-if="global.isRemoving" class="fa fa-spinner fa-spin fa-fw"></i>
                                    Отобрать права
                                </span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!globals.length" class="empty-page">
                    <h3>Нет модеров</h3>
                    <span class="text-muted">
                        Не пора ли выдать банхаммер кому-нибудь?
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import Headline from '../Headline.vue';
    import Globals from '../../services/Globals'

    export default {
        components: {
            Headline
        },
        created() {
            this.fetch();
        },
        data() {
            return {
                globals: null,

                newModeratorLogin: '',
                newModeratorIsAdmin: false,

                isAdding: false,
            }
        },
        methods: {
            fetch() {
                return Globals.list().then(
                    response => {
                        this.globals = response.data.globals;
                    }
                );
            },
            add() {
                if (this.isAdding) return;
                this.isAdding = true;
                Globals.add(this.newModeratorLogin, this.newModeratorIsAdmin).then(
                    response => {
                        if (response.data.ok) {
                            return this.fetch();
                        } else if (response.data.error) {
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.error);
                        }
                    }
                ).then(
                    () => {
                        this.isAdding = false;
                        this.newModeratorLogin = '';
                        this.newModeratorIsAdmin = false;
                    }
                )
            },
            remove(mod) {
                if (!confirm(`Удалить ${mod.login}?`))
                if (mod.isRemoving) return;
                mod.isRemoving = true;
                Globals.remove(mod.login).then(
                    response => {
                        if (response.data.ok) {
                            return this.fetch();
                        } else if (response.data.error) {
                            mod.isRemoving = false;
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.error);
                        }
                    }
                )
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss" scoped>
</style>
