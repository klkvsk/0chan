<template>
    <div v-if="board != null">
        <Headline>
            <span slot="title">Модераторы /{{board.dir}}/</span>
        </Headline>

        <div class="panel panel-default vspace">
            <div class="panel-body">

                <div class="vspace pull-left">
                    <router-link :to="{ name: 'admin' }" class="btn btn-default">
                        <i class="fa fa-chevron-left"></i> <span class="hidden-xs">Назад</span>
                    </router-link>
                </div>

                <div class="pull-right">
                    <form class="vspace form-inline" @submit.prevent="addModerator">
                        <div class="form-group">
                            <label class="control-label hidden-xs hidden-sm">Добавить модератора:</label>
                            <div class="input-group">
                                <input type="text" v-model="newModeratorLogin" placeholder="логин модератора" class="form-control"
                                       :disabled="isAddingModerator"/>
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-primary" :disabled="isAddingModerator">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="clearfix"></div>

                <div class="table-responsive">
                    <table v-if="moderators.length" class="table vspace">
                        <thead>
                        <tr>
                            <th>Модератор</th>
                            <th>Когда добавлен</th>
                            <th>Кем добавлен</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="mod in moderators">
                            <td>{{mod.moderator}}</td>
                            <td>{{mod.createdAt | timestamp }}</td>
                            <td>{{mod.initiator }}</td>
                            <td align="right">
                                <div class="btn-group">
                                <span class="btn btn-default" @click="removeModerator(mod)">
                                    <i v-if="!mod.isRemoving" class="fa fa-trash"></i>
                                    <i v-if="mod.isRemoving" class="fa fa-spinner fa-spin fa-fw"></i>
                                    Отобрать банхаммер
                                </span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!moderators.length" class="empty-page">
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
    import FormBuilder from '../FormBuilder.vue';
    import Management from '../../services/Management'
    import BusEvents from '../../app/BusEvents'

    export default {
        components: {
            Headline
        },
        created() {
            this.dir = this.$route.params.dir;
            this.fetch();
        },
        data() {
            return {
                dir: null,
                board: null,
                moderators: null,

                newModeratorLogin: '',
                isAddingModerator: false,
            }
        },
        methods: {
            fetch() {
                return Management.moderators(this.dir).then(
                    response => {
                        this.board = response.data.board;
                        this.moderators = response.data.moderators;
                    }
                );
            },
            addModerator() {
                if (this.isAddingModerator) return;
                this.isAddingModerator = true;
                Management.addModerator(this.dir, this.newModeratorLogin).then(
                    response => {
                        if (response.data.ok) {
                            return this.fetch();
                        } else if (response.data.error) {
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.error);
                        }
                    }
                ).then(
                    () => {
                        this.isAddingModerator = false;
                        this.newModeratorLogin = '';
                    }
                )
            },
            removeModerator(mod) {
                mod.isRemoving = true;
                Management.removeModerator(this.dir, mod.moderator).then(
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
    .table td {
        vertical-align: inherit;
    }
</style>