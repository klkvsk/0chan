<template>
    <div v-if="thread">
        <Headline>
            <span slot="title">
                <ContentLink :to="{ name: 'board', params: { dir: thread.board.dir } }">
                    <span :title="thread.board.name">/{{thread.board.dir}}/</span>
                </ContentLink>
                <span class="hidden-xs">
                    &mdash;
                    <ContentLink :to="{ name: 'thread', params: { dir: thread.board.dir, threadId: thread.id } }">
                    {{thread.title}}
                    </ContentLink>
                </span>
                <sup v-if="thread.board.isNsfw">(NSFW)</sup>
                <sup v-if="thread.board.isBlockRu">(<s>РФ</s>)</sup>
            </span>

            <div slot="buttons" class="btn-group">
                <button class="btn btn-info" style="width: 40px" @click="tree=!tree">
                    <i :class="{'fa': true, 'fa-code-fork': tree, 'fa-ellipsis-v': !tree}"></i>
                </button>
                <button class="btn btn-primary" type="button" v-if="opPost.canReplyTo" @click.prevent="replyToOpPost">
                    <i class="fa fa-pencil-square-o"></i> <span class="btn-caption hidden-xs">Ответить</span>
                </button>
                <button class="btn btn-default" @click="toggleWatch()" v-if="auth" type="button">
                    <i :class="{hide: thread.isWatched !== null}"  class="fa fa-fw fa-spinner fa-spin fa-fw"></i>
                    <i :class="{hide: thread.isWatched !== false}" class="fa fa-fw fa-star-o"></i>
                    <i :class="{hide: thread.isWatched !== true}"  class="fa fa-fw fa-star"  style="color: #dab750"></i>
                </button>

                <BoardControlButton :board="board"></BoardControlButton>
            </div>

        </Headline>

        <div class="threads" v-if="thread">
            <div style="margin-top: 20px; margin-bottom: 40px" v-if="posts">
                <ThreadPosts :thread="thread" :posts="posts" :root="opPost" :tree="tree" @reply="onReply" />
            </div>

            <div v-show="isReplyToOpPostFormShown">
                <PostForm v-if="isReplyToOpPostFormEnabled" :reply-to="opPost" @submit="onReply" ref="form"></PostForm>
            </div>

            <div style="margin-top: 20px; margin-bottom: 40px" class="btn-group">
                <button class="btn btn-primary" type="button" v-if="opPost.canReplyTo"  @click.prevent="replyToOpPost">
                    <i class="fa fa-fw fa-pencil-square-o"></i> <span class="btn-caption hidden-xs">Ответить на оп-пост</span>
                </button>
                <button class="btn btn-default" type="button" @click.prevent="checkNewReplies">
                    <i class="fa fa-fw" :class="isFetchingMore ? 'fa-spin fa-spinner' : 'fa-refresh'"></i>
                    <span class="btn-caption hidden-xs">Подгрузить ответы</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from 'jquery';
    import UI from '../app/UI';
    import BusEvents from '../app/BusEvents';

    import Headline from './Headline.vue';
    import ThreadPosts from './ThreadPosts.vue';
    import ContentLink from './ContentLink.vue';
    import PostForm from './PostForm.vue';
    import BoardControlButton from './BoardControlButton.vue';

    import Session from '../services/Session';
    import Thread from '../services/Thread';

    export default {
        props: [ 'dir', 'threadId' ],
        components: {
            Headline, ThreadPosts, ContentLink, PostForm, BoardControlButton
        },
        data () {
            return {
                auth: Session.auth,
                thread: null,
                board: null,
                posts: [],
                ready: false,
                error: null,
                tree: Session.settings.treeView,

                isFetchingMore: false,
                isReplyToOpPostFormShown: false,
                isReplyToOpPostFormEnabled: true,
            }
        },
        computed: {
            opPost() {
                return this.posts[0];
            }
        },

        beforeRouteUpdate(to, from, next) {
            if (!this.$router.sameRoute(from, to) && this.ready) {
                this.$router.saveStateCache(this);
                this.thread = null; // -> v-if hides components, animations work
                this.ready = false;
            }
            next();
        },
        beforeRouteLeave(to, from, next) {
            if (!this.$router.sameRoute(from, to) && this.ready) {
                this.$router.saveStateCache(this);
            }
            next();
        },

        methods: {
            fetch(lastPostId) {
                if (!lastPostId) {
                    if (this.$router.restoreStateCache(this)) {
                        return Promise.resolve();
                    }
                    this.thread = null;
                    this.board = null;
                    this.posts = null;
                }
                return Thread.get(this.threadId, lastPostId).then(
                    response => {
                        this.board = response.data.board;
                        this.thread = response.data.thread;
                        if (this.dir != this.thread.board.dir) {
                            this.$router.replace({ ...this.$route, ...{params: { dir: this.thread.board.dir }} });
                        }
                        if (lastPostId) {
                            if (response.data.posts.length) {
                                for (let newPost of response.data.posts) {
                                    // update refs
                                    for (let post of this.posts) {
                                        if (newPost.referencesToIds.indexOf(post.id) != -1) {
                                            post.referencedByIds.push(newPost.id);
                                        }
                                        if (newPost.parentId == post.id) {
                                            post.repliedByIds.push(newPost.id);
                                        }
                                    }
                                    this.posts.push(newPost);
                                }

                                const firstNewPost = response.data.posts[0];
                                this.scrollToPost(firstNewPost.id);
                            }
                        } else {
                            let posts = response.data.posts;
                            this.posts = [];
                            const totalPosts = posts.length;
                            let promise = new Promise(
                                resolve => {
                                    const fill = () => {
                                        this.$Progress.set(100 - (posts.length / totalPosts) * 50);
                                        if (this.posts === null) return; // cancel filling
                                        this.posts = this.posts.concat(posts.splice(0, 20));
                                        const next = () => posts.length ? fill() : resolve();
                                        if (window.requestIdleCallback) {
                                            window.requestIdleCallback(next);
                                        } else {
                                            setTimeout(next, 1000/60)
                                        }
                                    };
                                    fill();
                                }
                            );
                            promise = promise.then(() => this.ready = true);
                            promise = promise.then(() => this.checkPostAnchor());
                            return promise;
                        }
                    }
                );
            },
            checkNewReplies() {
                this.isFetchingMore = true;
                const lastPost = this.posts[this.posts.length - 1];
                return this.fetch(lastPost.id).then(() => {
                    this.isFetchingMore = false;
                });
            },
            checkPostAnchor() {
                this.$nextTick(() => {
                    const postAnchor = this.$route.hash.match(/^#([0-9]+)/);
                    if (postAnchor) {
                        const postId = postAnchor[1];
                        this.scrollToPost(postId);
                        this.$bus.emit(BusEvents.HIGHLIGHT_POST, postId);
                    }
                });
            },
            toggleWatch() {
                const isWatched = this.thread.isWatched;
                this.thread.isWatched = null;
                Thread.watch(this.thread.id, !isWatched)
                    .then(response => {
                        if (response.data && response.data.ok) {
                            this.thread.isWatched = response.data.isWatched;
                        } else {
                            this.thread.isWatched = isWatched;
                        }
                    });
            },
            replyToOpPost() {
                this.isReplyToOpPostFormShown = !this.isReplyToOpPostFormShown;
                if (this.isReplyToOpPostFormShown) {
                    this.$nextTick(() => {
                        this.$refs.form.focus()
                    });
                }
                //this.$bus.$emit(BusEvents.REPLY_TO_OP_POST);
            },
            onReply(post) {
                this.isReplyToOpPostFormEnabled = false;
                this.isReplyToOpPostFormShown = false;
                this.checkNewReplies().then(() => {
                    this.isReplyToOpPostFormEnabled = true;
                });
            },

            scrollToPost(postId) {
                this.$nextTick( () => UI.scrollTo(`a[name="${postId}"]`, -10) );
            }
        }
    }
</script>