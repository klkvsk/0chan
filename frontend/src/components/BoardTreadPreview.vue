<template>
    <div class="thread">
        <ThreadPosts :thread="thread.thread" :posts="posts" :root="thread.opPost" :tree="false" @reply="onReply">
            <div :class="$style.omittedPosts" slot="omittedPosts">
                <span v-if="thread.skippedPosts > 0">
                    {{skippedPostsText}} &mdash;
                </span>
                <router-link :to="threadRoute">Перейти к треду</router-link>
            </div>
        </ThreadPosts>
    </div>
</template>

<script>
    import Post from '../components/Post.vue'
    import ThreadPosts from '../components/ThreadPosts.vue'

    export default {
        props: [ 'thread' ],
        components: {
            Post, ThreadPosts
        },
        computed: {
            posts() {
                return [ this.thread.opPost, ...this.thread.lastPosts ];
            },
            skippedPostsText() {
                let n = this.thread.skippedPosts;
                if (n % 10 == 1 && n % 100 != 11) {
                    return `Пропущено ${n} сообщение`;
                } else if (n % 10 >= 2 && n %10 <= 4 && (n % 100 < 10 || n % 100 >= 20)) {
                    return `Пропущено ${n} сообщения`;
                } else {
                    return `Пропущено ${n} сообщений`;
                }
            },
            threadRoute() {
                const thread = this.thread.thread;
                return { name: 'thread', params: { dir: thread.board.dir, threadId: thread.id } };
            }
        },
        methods: {
            onReply(post) {
                this.$emit('reply', post)
            }
        }
    }
</script>

<style module lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .omittedPosts {
        font-weight: bold;
        margin-left: 35px;

        @media (max-width: $screen-xs-max) {
            margin-left: 5px;
            padding-left: 5px;
        }
    }
</style>