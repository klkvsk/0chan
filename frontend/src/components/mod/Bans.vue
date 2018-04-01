<template>
    <div v-if="bans">
        <div class="">
            <form class="form form-inline" @submit.prevent="onBanSearchSubmit" @reset="onBanSearchReset">
                Поиск по ID:
                <span class="input-group">
                    <input type="number" v-model="banSearch" class="form-control" />
                    <span class="input-group-btn">
                        <button type="reset" v-if="banId" class="btn btn-default">
                            <i class="fa fa-times"></i>
                        </button>
                        <button type="submit" class="btn btn-default">
                            <i class="fa fa-search"></i>
                        </button>
                    </span>
                </span>
            </form>
        </div>
        <Pagination v-if="pagination" :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>

        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Статус</th>
                    <th>Доска</th>
                    <th>Причина</th>
                    <th>Срок</th>
                    <th>Разбан</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="ban in bans">
                    <td>{{ban.id}}</td>

                    <td>
                        <div v-if="ban.isActive" class="label label-danger">Активен</div>
                        <div v-else-if="!ban.isActive" class="label label-default">Истёк</div>
                    </td>
                    <td><router-link :to="{ name: 'board', params: { dir: ban.board.dir } }" :title="ban.board.name">/{{ban.board.dir}}/</router-link></td>
                    <td>
                        {{ban.reason}}
                        <div class="vspace2"></div>
                        <Post :post="ban.post" v-if="ban.post" :readonly="true" />
                        <span v-if="!ban.post"><i>(пост уже удален)</i></span>
                    </td>
                    <td>
                        {{ ban.bannedTill - ban.bannedAt | timespan }}
                        <span class="text-muted">(до {{ban.bannedTill | timestamp }})</span>
                        <br><br>
                        Выдан модератором <b>{{ ban.bannedBy }}</b> <br>
                        {{ban.bannedAt | timestamp}}
                    </td>
                    <td>
                        <div v-if="ban.appeal"><b>Обжалование</b>: <br>{{ban.appeal}}</div>
                        <div v-if="!ban.appeal">Обжалование не подавалось</div>
                        <div class="vspace2"></div>
                        <div v-if="ban.isActive" style="text-align: right">
                            <button class="btn btn-default" @click.prevent="removeBan(ban)">
                                <i class="fa fa-gavel"></i> Разбанить
                            </button>
                        </div>
                        <div v-if="ban.unbannedBy">
                            Разбанен модератором <b>{{ ban.unbannedBy }}</b> <br>
                            {{ ban.unbannedAt | timestamp }}
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="empty-page" v-if="bans.length == 0">
            <h3>Нет банов</h3>
        </div>
        <Pagination v-if="pagination" :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>
    </div>
</template>

<script>
    import Moderation from '../../services/Moderation';
    import Pagination from '../Pagination.vue';
    import Post from '../Post.vue';

    export default {
        props: [
            'dir', 'banId'
        ],
        components: {
            Post, Pagination
        },
        data() {
            return {
                bans: null,
                pagination: null,
                banSearch: '',
            }
        },
        methods: {
            fetch() {
                this.bans = null;
                if (this.banId) {
                    this.banSearch = this.banId;
                    return Moderation.banInfo(this.banId).then(
                        response => {
                            this.bans = [ response.data.ban ];
                            this.pagination = null;
                        },
                        error => {
                            if (error.response.status == 404) {
                                this.bans = [];
                                this.pagination = null;
                            } else {
                                throw error;
                            }
                        }
                    );
                } else {
                    return Moderation.bans(this.dir, this.$route.query.page).then(
                        response => {
                            this.bans = response.data.bans;
                            this.pagination = response.data.pagination;
                        }
                    );
                }
            },

            removeBan(ban) {
                const id = this.bans.indexOf(ban);

                return Moderation.removeBan(ban.id).then(
                    response => {
                        if (response.data.ok) {
                            this.$set(this.bans, id, response.data.ban)
                        }
                    });
            },

            onPageChange(page) {
                this.$router.push({ name: 'mod_bans', params: {dir: this.dir}, query: {page: page}});
            },

            onBanSearchSubmit() {
                if (!this.banSearch) return;
                this.$router.push({ name: 'mod_bans', params: { banId: this.banSearch }})
            },

            onBanSearchReset() {
                this.banSearch = '';
                this.$router.push({ name: 'mod_bans' })
            }
        }
    }
</script>

