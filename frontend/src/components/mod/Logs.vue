<template>
    <div v-if="logs">
        <form v-if="dir" @submit.prevent="onMessageSubmit" class="form-inline">
            <div class="form-group">
                <label>Сообщение:</label>
                <input type="text" v-model="message" maxlength="255" class="form-control" style="width: 400px;">
                <button type="submit" :disabled="!message" class="btn btn-primary"><i class="fa fa-send"></i></button>
            </div>
        </form>
        <Pagination :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="120px">Когда</th>
                    <th width="120px">Где</th>
                    <th width="150px">Кто</th>
                    <th width="250px">Что</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs">
                    <td>{{log.date | timestamp }}</td>
                    <td><router-link :to="{ name: 'board', params: { dir: log.board.dir } }">/{{log.board.dir}}/</router-link></td>
                    <td>{{log.user}}</td>
                    <td>{{log.event}}</td>
                    <td>
                        <div v-if="log.info">
                            <div v-if="log.info.user"><b>{{log.info.user}}</b></div>

                            <div v-if="log.info.threadId">
                                <router-link :to="{ name: 'thread', params: { dir: log.board.dir, threadId: log.info.threadId } }">
                                    Тред №{{log.info.threadId}}: "{{log.info.threadTitle}}"
                                </router-link>
                            </div>

                            <div v-if="log.info.attachmentId">
                                Пост <a :data-post="log.info.postId">&gt;&gt;{{ log.info.postId }}</a>
                                <div v-if="log.info.attachment">
                                    <PostAttachment v-if="log.info.attachment" :moderatable="true" :no-scroll="true" :attachment="log.info.attachment"></PostAttachment>
                                </div>
                                <div v-else>(файл уже подчищен)</div>
                            </div>

                            <div v-else>
                                <Post v-if="log.info.post" :post="log.info.post"></Post>
                                <div v-if="!log.info.post && log.info.postId">
                                    Пост №{{log.info.postId}} (уже подчищен)
                                </div>
                            </div>

                            <div v-if="log.info.ban">
                                <router-link :to="{ name: 'mod_bans', params: { banId: log.info.ban.id } }">
                                    Бан №{{log.info.ban.id}} <br>
                                </router-link>
                                Причина: {{log.info.ban.reason}} <br>
                                Выдан: {{log.info.ban.bannedAt | timestamp }} <br>
                                До:
                                <span :class="{ 'text-deleted': log.info.ban.unbannedAt }">
                                    {{ log.info.ban.bannedTill | timestamp }}</span>
                                <span v-if="log.info.ban.unbannedAt">
                                    {{ log.info.ban.unbannedAt | timestamp }}
                                </span>
                            </div>

                            <div v-if="log.info.message" class="alert alert-warning">
                                <b>{{log.user}}:</b> {{log.info.message}}
                            </div>

                            <div v-if="log.info.change">
                                <b>{{log.info.change.name}}</b>: {{log.info.change.old}} &rarr; {{log.info.change.new}}
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
        <Pagination :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>

        <div v-if="popupPost" class="post-popup">
            <transition name="fade">
                <Post :post="popupPost"></Post>
            </transition>
        </div>
    </div>
</template>

<script>
    import UI from '../../app/UI';
    import ApiPost from '../../services/Post';
    import Post from '../Post.vue';
    import PostAttachment from '../PostAttachment.vue';
    import Pagination from '../Pagination.vue';
    import BanInfo from '../BanInfo.vue';
    import Moderation from '../../services/Moderation';

    export default {
        props: [
            'dir'
        ],
        components: {
            Post, PostAttachment, Pagination, BanInfo
        },
        methods: {
            fetch() {
                this.logs = null;
                return Moderation.logs(this.dir, this.$route.query.page).then(
                    response => {
                        this.logs = response.data.logs;
                        this.pagination = response.data.pagination;

                        this.$nextTick(() => {
                            UI.setupPostPopup(this, (post) => this.popupPost = post);
                        });
                    }
                );
            },
            onPageChange(page) {
                this.$router.push({ name: 'mod_logs', params: {dir: this.dir}, query: {page: page}});
            },
            onMessageSubmit() {
                if (!this.message) return;
                Moderation.message(this.dir, this.message).then(
                    response => {
                        if (response.data.ok) {
                            this.message = '';
                            this.fetch();
                        }
                    }
                )
            },
            loadPost(postId) {
                return ApiPost.get(postId).then(
                    response => response.data.post,
                    error => { return null; }
                );
            },
        },
        data() {
            return {
                message: '',
                logs: null,
                pagination: null,
                popupPost: null,
            }
        },
    }
</script>

