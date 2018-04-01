<template>
    <div>
        <Post :post="root" :thread="thread" :posts="posts" :tree="tree" @hidden="onToggleHidden" @reply="onReply"></Post>
        <slot name="omittedPosts" v-if=" !isRootHidden"></slot>
        <div class="thread-tree" v-if="replies.length && !isRootHidden">
            <div class="thread-reply-chain" @click="scrollToRoot"></div>
            <Post v-if="!tree" v-for="post in replies" :key="post.id" :post="post" :posts="posts" :thread="thread" @reply="onReply" />
            <ThreadPosts v-if="tree" v-for="post in replies" :key="post.id" :thread="thread" :posts="posts" :root="post" :tree="true" @reply="onReply" />
        </div>
    </div>
</template>

<script>
    import Post from './Post.vue'
    import $ from 'jquery';
    import UI from '../app/UI';
    import Storage from '../services/Storage';

    export default {
        components: {
            Post
        },
        props: [ 'thread', 'posts', 'root', 'tree' ],
        beforeCreate: function () {
            // circular reference -- lazy loading
            this.$options.components.ThreadPosts = require('./ThreadPosts.vue')
        },
        data() {
            return {
                isRootHidden: Storage.isHiddenPost(this.root.id)
            }
        },
        computed: {
            replies() {
                let posts = this.posts;
                if (this.tree) {
                    return posts.filter(post => post.parentId == this.root.id);
                } else {
                    return posts.slice(1);
                }
            }
        },
        methods: {
            scrollToRoot() {
                UI.scrollTo('a[name="' + this.root.id + '"]', -10);
            },
            onToggleHidden(isHidden) {
                this.isRootHidden = isHidden;
            },
            onReply(post) {
                this.$emit('reply', post)
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .thread-tree {
        margin-left: 5px;
        padding-left: 25px;
        position: relative;
    }

    .thread-reply-chain {
        border-left: 1px dotted $color-grey-lt;
        position: absolute;
        left: 0;
        top: 0;
        bottom: 30px;
        width: 20px;
    }

    .thread-reply-chain:hover {
        border-color: $color-grey;
    }

    // root is not indented
    .thread > div > .thread-tree {
        border-left: none;
    }

    @media (max-width: $screen-xs-max) {
        .post {
            min-width: inherit;
        }
        .thread-tree {
            margin-left: 5px;
            padding-left: 5px;
        }
    }

</style>

