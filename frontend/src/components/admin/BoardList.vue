<template>
    <div>
        <Headline>
            <span slot="title">Управление досками</span>
            <span slot="buttons">
                <router-link :to="{ name: 'admin_newBoard'}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> <span class="hidden-xs">Создать доску</span>
                </router-link>
            </span>
        </Headline>

        <div v-if="boards != null">
            <div v-if="boards.length" class="panel panel-default vspace">
                <div class="panel-body">
                    <table v-if="boards.length" class="table">
                        <thead>
                        <tr>
                            <th>Путь</th>
                            <th class="hidden-xs">Название</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="board in boards">
                            <td><router-link :to="{ name: 'board', params: { dir: board.dir } }">/{{board.dir}}/</router-link></td>
                            <td class="hidden-xs">{{board.name}}</td>
                            <td align="right">
                                <div class="btn-group btn-group-xs">
                                    <router-link :to="{ name: 'mod_logs', params: { dir: board.dir } }" class="btn btn-default">
                                        <i class="fa fa-eye"></i> <span class="hidden-xs hidden-sm">Модерация</span>
                                    </router-link>
                                    <router-link :to="{ name: 'admin_mods', params: { dir: board.dir } }" class="btn btn-default">
                                        <i class="fa fa-users"></i> <span class="hidden-xs hidden-sm">Модераторы</span>
                                    </router-link>
                                    <router-link :to="{ name: 'admin_editBoard', params: { dir: board.dir } }" class="btn btn-default">
                                        <i class="fa fa-edit"></i> <span class="hidden-xs hidden-sm">Параметры</span>
                                    </router-link>
                                    <span class="btn btn-default" @click="deleteBoard(board)">
                                        <i class="fa fa-trash"></i>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="!boards.length" class="empty-page">
                <h3>Нет собственных досок</h3>
                Но можно создать прямо сейчас! Кнопка "+" вверху справа.
            </div>
        </div>
    </div>
</template>

<script>
    import Headline from '../Headline.vue';
    import Management from '../../services/Management'

    export default {
        components: {
            Headline
        },
        data() {
            return {
                boards: null
            }
        },
        created() {
            this.fetch();
        },
        methods: {
            fetch() {
                this.boards = null;
                return Management.list().then(
                    response => {
                        this.boards = response.data.boards;
                    }
                )
            },
            deleteBoard(board) {
                if (!confirm(`Удалить доску /${board.dir}/ "${board.name}"? Восстановить ее будет невозможно.`)) {
                    return;
                }

                Management.deleteBoard(board.dir).then(this.fetch)
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss" scoped>
    .table td {
        vertical-align: inherit;
    }
</style>
