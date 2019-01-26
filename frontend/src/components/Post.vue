<template>
    <div v-if="!(!tree && post.isDeleted && !post.canBeModerated)">
        <div :class="{ 'block': true, 'post': true, 'post-op': post.isOpPost, 'post-deleted': post.isDeleted, 'post-highlighted': isHighlighted }">

            <div class="post-header">
                <a :name="post.id" v-if="anchored"></a>
                <span class="post-id">
                    <router-link :to="boardRoute">
                        /{{post.boardDir}}/
                    </router-link>
                    <span v-if="thread && post.isOpPost">
                        &mdash; {{thread.board.name}} &mdash;
                    </span>
                    <router-link :to="threadRoute" @click.native="highlightPost">
                        #{{post.id}}
                    </router-link>
                    <b class="post-username" v-if="post.username">&mdash; {{post.username}}</b>
                </span>
                <span class="pull-right">
                    <span v-if="thread && post.isOpPost" class="post-thread-options">
                        <span v-if="thread.isBumpLimitReached" title="Достигнут бамп-лимит">
                            <i class="fa fa-fw fa-arrow-up active" style="text-decoration: overline"></i>
                        </span>
                        <span v-if="thread.purgedAt" @click="post.canBeModerated && unpurgeThread()">
                            <i class="fa fw-fw fa-trash"
                               v-if="thread.purgedAt !== -1"
                               :title="purgeDateTitle"
                               :class="{ 'active': true, 'hand': post.canBeModerated }"></i>
                            <i class="fa fa-spinner fa-spin fa-fw" v-if="thread.purgedAt === -1"></i>
                        </span>
                        <span @click="post.canBeModerated && togglePin()">
                            <i class="fa fw-fw fa-thumb-tack"
                               v-if="thread.isPinned === true || (post.canBeModerated && thread.isPinned !== null)"
                               :title="thread.isPinned ? 'Прикреплен' : 'Прикрепить'"
                               :class="{ 'active': thread.isPinned === true, 'hand': post.canBeModerated }"></i>
                            <i class="fa fa-spinner fa-spin fa-fw" v-if="thread.isPinned === null"></i>
                        </span>
                        <span @click="post.canBeModerated && toggleLock()">
                            <i class="fa fw-fw fa-lock"
                               :title="thread.isLocked ? 'Закрыть' : 'Закрыть'"
                               v-if="thread.isLocked === true || (post.canBeModerated && thread.isLocked !== null)"
                               :class="{ 'active': thread.isLocked === true, 'hand': post.canBeModerated }"></i>
                            <i class="fa fa-spinner fa-spin fa-fw" v-if="thread.isLocked === null"></i>
                        </span>
                    </span>
                    <span class="post-date">{{post.date | timestamp }}</span>
                </span>
            </div>

            <div class="post-body" :class="{'post-inline-attachment': isInlineAttachment}" v-if="!isHidden || readonly">
                <div class="post-attachments" v-if="post.attachments.length && !isHidden">
                    <PostAttachment v-for="attachment in post.attachments" :key="attachment.id"
                                    :attachment="attachment"
                                    :moderatable="!readonly && post.canBeModerated"
                                    @opened="onOpenedAttachment"
                    >
                    </PostAttachment>
                </div>

                <div class="post-body-message">
                    <div class="post-parent" v-if="post.parentId && post.parentId != post.opPostId && !tree">
                        <a :data-post="post.parentId">&gt;&gt;{{post.parentId}}</a>
                    </div>
                    <div v-html="post.messageHtml" v-once></div>
                </div>
                <div v-if="post.isUserBanned" class="user-was-banned">(USER WAS BANNED FOR THIS POST)</div>
                <div v-if="!tree && !post.isOpPost && post.repliedByIds.length" class="post-replied-by">
                    Ответы:
                    <span v-for="replyPostId in post.repliedByIds">
                            <a :data-post="replyPostId">&gt;&gt;{{replyPostId}}</a><span>, </span>
                        </span>
                </div>
                <div v-if="post.referencedByIds.length" class="post-referenced-by">
                    Упоминания:
                    <span v-for="refPostId in post.referencedByIds">
                            <a :data-post="refPostId">&gt;&gt;{{refPostId}}</a><span>, </span>
                        </span>
                </div>
            </div>

            <div class="post-footer" v-if="!readonly">
                <div class="pull-left">
                    <span class="post-button" @click="toggleHidePost" :title="isHidden ? 'Показать' : 'Скрыть'">
                        <i :class="{ 'fa': true, 'fa-minus-square-o': !isHidden, 'fa-plus-square-o': isHidden }"></i>
                    </span>
                </div>

                <div class="pull-left" v-if="!post.canBeModerated && post.canBeReported">
                    <span class="post-button" @click="reportPost" v-if="!post.isDeleted" title="Пожаловаться"><i class="fa fa-exclamation"></i></span>
                </div>

                <div class="pull-left" style="position: relative" v-if="post.canBeModerated">
                    <span class="post-button" @click="deletePost" v-if="post.isDeleted === false" title="Удалить"><i class="fa fa-fw fa-times"></i></span>
                    <span class="post-button" v-if="post.isDeleted === null"><i class="fa fa-fw fa-spinner fa-spin"></i></span>
                    <span class="post-button" @click="restorePost" v-if="post.isDeleted === true" title="Восстановить"><i class="fa fa-fw fa-undo"></i></span>
                    <span class="post-button" @click="delallPost" v-if="post.isDeleted === false" title="Делол"><i class="fa fa-fw fa-eraser"></i></span>
                    <span class="post-button" @click="toggleBanForm" v-if="!post.isUserBanned" :class="{ active: isBanFormShown }" title="Забанить автора"><i class="fa fa-fw fa-gavel"></i></span>
                    <div v-if="isBanFormShown" class="ban-popup panel panel-default block" slot="content">
                        <form class="form" @submit.prevent="submitBan">
                            <div class="panel-body">
                                <div class="form-group">
                                    <label>Причина</label>
                                    <input type="text" v-model="banForm.reason" class="form-control" required />
                                </div>
                                <div class="form-group">
                                    <label>Срок</label>
                                    <div>
                                        <label class="radio-inline" v-for="(name, val) in banTimePresets">
                                            <input type="radio" name="time" v-model="banForm.time" :value="val">
                                            <span>{{name}}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right panel-footer">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-fw fa-gavel"></i> Забанить
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="pull-right" v-if="!post.isDeleted">
                    <!--<span class="post-button"><i class="fa fa-angle-up"></i></span>-->
                    <!--<span class="post-button"><i class="fa fa-angle-down"></i></span>-->
                    <!--<span class="post-button"><i class="fa fa-envelope-o"></i></span>-->
                    <span class="post-button post-button-reply" @click="toggleReplyForm" v-if="post.canReplyTo"><i class="fa fa-reply"></i></span>
                    <span class="post-button" v-if="post.ban" title="Забанен" @click="showBanInfo">
                        <i v-if="!isFetchingBanInfo" class="fa fa-fw fa-gavel" ></i>
                        <i v-else-if="isFetchingBanInfo" class="fa fa-fw fa-spin fa-spinner"></i>
                    </span>
                </div>
            </div>

            <transition name="fade">
                <Post v-if="popupPost" class="post-popup" :post="popupPost" :posts="posts" :thread="thread" :readonly="readonly" :tree="false" :anchored="false"></Post>
            </transition>

        </div>

        <div style="margin-left: 25px;" v-if="post.canReplyTo">
            <PostForm v-if="isReplyFormShown" :reply-to="post" @submit="onReply" ref="form"></PostForm>
        </div>
    </div>

</template>

<script>
    import $ from 'jquery';
    import UI from '../app/UI';
    import BusEvents from '../app/BusEvents';
    import Filters from '../app/Filters';
    import Moderation from '../services/Moderation';
    import Post from '../services/Post';
    import PostForm from '../components/PostForm.vue';
    import PostAttachment from './PostAttachment.vue';
    import Storage from '../services/Storage';
    import BanInfo from '../components/BanInfo.vue';

    export default {
        props: {
            thread:     null,
            post:       null,
            posts:      null,
            tree:       { type: Boolean, default: false},
            readonly:   { type: Boolean, default: false},
            anchored:   { type: Boolean, default: true}
        },
        data() {
            return {
                popupPost: null,
                banForm: {
                    reason: '',
                    time: 60,
                },
                banTimePresets: {
                    [60]: 'час',
                    [60 * 24]: 'сутки',
                    [60 * 24 * 7]: 'неделя',
                    [60 * 24 * 30]: 'месяц',
                    [60 * 24 * 365 * 2]: 'джва года'
                },
                isReplyFormShown: false,
                isBanFormShown: false,
                isHidden: false,
                isHighlighted: false,
                isFetchingBanInfo: false,
                openedAttachments: 0,

                busEvents: {
                    [BusEvents.REFRESH_CONTENT]:    () => this.isReplyFormShown = false,
                    [BusEvents.HIGHLIGHT_POST]:    (id) => {
                        if (id == this.post.id) {
                            this.isHighlighted = true;
                            setTimeout(() => this.isHighlighted = false, 3000);
                            this.$nextTick(() => {
                                if (this.anchored) {
                                    UI.scrollTo(`a[name=${id}]`, -10);
                                }
                            })
                        }
                        this.popupPost = null;
                    },
                    [BusEvents.REPLY_TO_OP_POST]:   () => {
                        if (this.post.isOpPost) {
                            this.$nextTick(() => {
                                UI.scrollTo('body', 0).then(
                                    () => {
                                        this.toggleReplyForm();
                                    }
                                );
                            })
                        }
                    }
                }
            }
        },
        components: { PostForm, PostAttachment },
        beforeCreate: function () {
            // circular reference -- lazy loading
            this.$options.components.Post = require('./Post.vue')
        },
        methods: {
            onReply(post) {
                this.isReplyFormShown = false;
                this.$emit('reply', post);
            },
            deletePost() {
                this.post.isDeleted = null;
                Moderation.deletePost(this.post.id).then(response => {
                    if (response.data.ok) {
                        Object.assign(this.post, response.data.post);
                    }
                })
            },
            delallPost() {
                const reason = prompt('Вы уверены? (Напишите: Да):');
                if(!reason) return;
                this.post.isDeleted = null;
                Moderation.delallPost(this.post.id).then(response => {
                    if (response.data.ok) {
                        this.$bus.emit(BusEvents.ALERT_SUCCESS, 'Вычистил все посты :3');
                    }
                })
            },
            restorePost() {
                this.post.isDeleted = null;
                Moderation.restorePost(this.post.id).then(response => {
                    if (response.data.ok) {
                        Object.assign(this.post, response.data.post);
                    }
                })
            },
            reportPost() {
                const reason = prompt('Укажите причину жалобы:');
                if (!reason) return;
                Moderation.reportPost(this.post.id, reason).then(
                    response => {
                        if (response.data.ok) {
                            this.$bus.emit(BusEvents.ALERT_SUCCESS, 'Спасибо, мочераторы оповещены');
                        } else {
                            this.$bus.emit(BusEvents.ALERT_ERROR, 'Ошибка - причина не заполнена (или слишком длинная)');
                        }
                    }
                )
            },
            toggleBanForm() {
                this.isBanFormShown = !this.isBanFormShown;
            },
            submitBan() {
                Moderation.addBan(this.post.id, this.banForm.reason, this.banForm.time).then(
                    response => {
                        if (response.data.ok) {
                            this.isBanFormShown = false;
                            Object.assign(this.post, response.data.ban.post);
                        }
                    }
                )
            },
            toggleReplyForm() {
                this.isReplyFormShown = !this.isReplyFormShown;
                if (this.isReplyFormShown) {
                    this.$nextTick(() => {
                        this.$refs.form.focus()
                    });
                }
            },
            toggleHidePost() {
                this.isHidden = !this.isHidden;
                Storage.setHiddenPost(this.post.id, this.isHidden);
                this.$emit('hidden', this.isHidden);
            },

            // thread controls
            togglePin() {
                const isPinned = this.thread.isPinned;
                this.thread.isPinned = null;
                Moderation.pinThread(this.thread.id, !isPinned).then(
                    response => {
                        if (response.data.ok) {
                            this.thread.isPinned = response.data.isPinned;
                        } else {
                            this.thread.isPinned = isPinned;
                        }
                    }
                )
            },
            toggleLock() {
                const isLocked = this.thread.isLocked;
                this.thread.isLocked = null;
                Moderation.lockThread(this.thread.id, !isLocked).then(
                    response => {
                        if (response.data.ok) {
                            this.thread.isLocked = response.data.isLocked;
                        } else {
                            this.thread.isLocked = isLocked;
                        }
                    }
                )
            },
            showBanInfo() {
                this.isFetchingBanInfo = true;
                Moderation.banInfo(this.post.ban).then(
                    response => {
                        this.isFetchingBanInfo = false;
                        if (response.data.ban) {
                            this.$bus.emit(BusEvents.SHOW_MODAL, BanInfo, { ban: response.data.ban, appealable: true }) ;
                        }
                    }
                )
            },
            loadPost(postId) {
                if (this.posts) {
                    for (let post of this.posts) {
                        if (post.id == postId) {
                            return Promise.resolve(post);
                        }
                    }
                }

                return Post.get(postId).then(
                    response => response.data.post,
                    error => { return null; }
                );
            },
            highlightPost() {
                if (this.$router.sameRoute(this.$route, this.threadRoute)) {
                    this.$bus.emit(BusEvents.HIGHLIGHT_POST, this.post.id);
                }
            },
            unpurgeThread() {
                const purgedAt = this.thread.purgedAt;
                this.thread.purgedAt = -1;
                return Moderation.unpurgeThread(this.thread.id).then(
                    response => {
                        if (response.data.ok) {
                            this.thread.purgedAt = null;
                        } else {
                            this.thread.purgedAt = purgedAt;
                        }
                    }
                )
            },
            onOpenedAttachment(isOpen) {
                this.openedAttachments += isOpen ? 1 : -1;
            }
        },
        created() {
            this.isHidden = Storage.isHiddenPost(this.post.id);
        },
        mounted() {
            UI.setupPostPopup(this, (post) => this.popupPost = post);
        },
        computed: {
            boardRoute() {
                return { name: 'board', params: { dir: this.post.boardDir } }
            },
            threadRoute() {
                return {
                    name: 'thread', params: { dir: this.post.boardDir, threadId: this.post.threadId },
                    hash: this.post.isOpPost ? '' : '#' + this.post.id
                };
            },
            purgeDateTitle() {
                let title = 'Отмечен к удалению в ' + Filters.timestamp(this.thread.purgedAt);
                if (this.post.canBeModerated) {
                    title += " \nКлик, чтобы спасти тред: отменить удаление и поднять вверх";
                }
                return title;
            },
            isInlineAttachment() {
                return this.openedAttachments === 0
                    && this.post.attachments.length === 1;
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .post-popup {
        position: absolute;
        z-index: 1;
        .post {
            padding: 0;
            margin: 0;
            box-shadow: 0 2px 6px $color-grey-lt;
        }
        .post-deleted {
            opacity: 1;
            .post-header, .post-body, .post-footer {
                opacity: 0.5;
            }
        }
    }

    .ban-popup {
        position: absolute;
        z-index: 1;
        width: 375px;
        padding: 0 !important;
        .panel-body, .panel-footer { padding: 8px }
        .form-group { margin-bottom: 8px; }
    }

    .post-highlighted {
        border-color: $color-green-lt !important;
        box-shadow: 0 0 20px $color-green-bg inset, 0 1px 4px $color-grey-lt !important;
    }

    .post {
        position: relative;
        padding: 0 !important;
        min-width: 400px;
        max-width: 100%;
        clear: both;
        transition: opacity 0.5s, border-color 1s ease-out;

        &.post-deleted {
            opacity: 0.5;
        }

        .post-header {
            padding: 2px 10px;
            font-size: 8pt;
            .post-date {
                font-style: italic;
                margin-left: 10px;
                color: $color-grey-lt;
            }

            .post-thread-options {
                .active {
                    color: $color-black;
                }
                i {
                    width: 1.5em;
                    color: $color-grey-lt;
                    text-align: center;
                }
            }
        }

        .post-body {
            padding: 0 10px;

            @media(min-width: $screen-lg-min) {
                max-width: 1000px;
            }

            &.post-inline-attachment{
                max-height: 500px;
                overflow: auto;
            }
            .post-body-message {
                max-height: 400px;
                width: 100%;
                overflow: auto;
            }

            .user-was-banned {
                color: $color-red;
                padding-top: 0.5em;
                font-size: 80%;
                font-weight: bold;
            }

            .post-referenced-by, .post-replied-by {
                padding-top: 6px;
                font-size: 80%;
                font-style: italic;
                color: $color-grey-dk;

                span:last-child > span {
                    display: none;
                }

                & + & {
                    padding-top: 0;
                }
            }

            .post-replied-by + .post-referenced-by { padding-top: 0; }
        }

        .post-inline-attachment {
            .post-attachments {
                display: inline-block;
                float: left;
                padding-right: 10px;
            }
            .post-body-message {
                display: inline;
            }
        }

        .post-footer {
            clear: both;
            border-top: 1px solid $color-white-dk;
            cursor: default;
            margin-top: 5px;
            padding: 2px 0;


            .post-button-reply {
                color: $color-green-dk;
                &:hover { color: $color-green; }
            }
        }
    }

    .post-button {
        transition: color 0.2s;
        cursor: pointer;
        padding-left: 10px;
        padding-right: 10px;
        color: $color-grey-lt;
        text-align: center;
        i {
            width: 1.5em;
        }

        &:hover, &.active {
            color: $color-green;
        }
    }

    .new-thread-form {
        padding: 5px;
        margin-top: 30px;
        /*text-align: center;*/
    }

    .new-thread-form .reply-form {
        width: 100% !important;
        margin: 0;
    }

    .new-thread-form .reply-form textarea {
        width: 100%;
    }

</style>
