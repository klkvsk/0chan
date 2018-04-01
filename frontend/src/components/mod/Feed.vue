<template>
    <div>
        <div v-if="feed">
            <div>
                <div v-for="item in feed" :key="item" class="feed-item">
                    <div class="color-grey" style="font-size: 90%" :title="item.publishedAt | timestamp">{{ item.publishedAt | timeago }}</div>
                    <PostAttachment :attachment="item.attachment" :moderatable="true" :no-scroll="true">
                    </PostAttachment>
                    <a :data-post="item.postId">&gt;&gt;{{ item.postId }}</a>
                </div>
            </div>
            <Pagination :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>
            <div v-if="popupPost" class="post-popup">
                <transition name="fade">
                    <Post :post="popupPost"></Post>
                </transition>
            </div>
        </div>
    </div>
</template>

<script>
    import UI from '../../app/UI';
    import ApiPost from '../../services/Post';
    import Post from '../Post.vue';
    import PostAttachment from '../PostAttachment.vue';
    import Pagination from '../Pagination.vue';
    import Moderation from '../../services/Moderation';

    export default {
        props: [
            'dir'
        ],
        components: {
            Post, Pagination, PostAttachment
        },
        mounted() {
            UI.setupPostPopup(this, (post) => this.popupPost = post);
        },
        methods: {
            fetch() {
                this.feed = null;
                return Moderation.feed(this.dir, this.$route.query.page).then(
                    response => {
                        this.feed = response.data.feed;
                        this.pagination = response.data.pagination;
                    }
                );
            },
            onPageChange(page) {
                this.$router.push({ name: 'mod_feed', params: {dir: this.dir}, query: {page: page}});
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
                feed: null,
                pagination: null,
                popupPost: null,
            }
        },
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .feed-item {
        display: inline-block;
        width: 240px;
        height: 240px;
        text-align: center;
        vertical-align: top;
    }


    .post-popup {
        position: absolute;
        z-index: 1;
        .post {
            padding: 0;
            margin: 0;
            box-shadow: 0 2px 6px $color-grey-lt;
        }
    }
    .post-img-full {
        position: relative;
        z-index: 1;
    }
</style>