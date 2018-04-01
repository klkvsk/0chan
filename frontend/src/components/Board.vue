<template>
    <div v-if="board !== null">
        <Headline>
            <span slot="title">
                <ContentLink :to="{ name: $route.name, params: $route.params }">
                    <span v-if="board.dir">
                        /{{board.dir}}/
                        <span class="hidden-xs">
                            <span v-if="board.dir"> &mdash; </span>
                            {{board.name}}
                        </span>
                    </span>
                    <span v-else-if="!board.dir">{{board.name}}</span>
                </ContentLink>
                <sup v-if="board.isNsfw">(NSFW)</sup>
                <sup v-if="board.isBlockRu">(<s>РФ</s>)</sup>
            </span>

            <div slot="buttons" class="btn-group" v-if="board.dir">
                <button v-if="board.description" class="btn btn-link" @click="showBoardInfo">
                    <i class="fa fa-fw fa-info-circle"></i>
                </button>

                <button class="btn btn-primary" v-if="board.canCreateThread" @click="toggleNewThreadForm()" type="button">
                    <i class="fa fa-pencil-square-o"></i> <span class="btn-caption hidden-xs">Создать тред</span>
                </button>

                <button v-if="auth" class="btn btn-default" type="button" @click="toggleFavourite()">
                    <i :hidden="board.isFavourite !== null" class="fa fa-spinner fa-spin fa-fw"></i>
                    <i :hidden="board.isFavourite !== false" class="fa fa-fw fa-heart-o"></i>
                    <i :hidden="board.isFavourite !== true" class="fa fa-fw fa-heart" style="color: indianred"></i>
                </button>

                <BoardControlButton :board="board"></BoardControlButton>
            </div>
        </Headline>

        <div class="new-thread-form" v-show="newThreadForm && board.canCreateThread">
            <div>
                <PostForm :board="board" @submit="onPostSubmit" />
            </div>
            <div class="separator"></div>
        </div>

        <div class="threads-scroll-spy hidden-md hidden-sm hidden-xs" v-show="threads.length > 0 && !newThreadForm" ref="scrollSpy">
            <div class="threads-scroll-toggler text-right">
                <i class="fa fa-angle-left" v-if="!storage.isThreadListVisible()" @click="storage.setThreadListVisibility(true)"></i>
                <i class="fa fa-angle-right" v-if="storage.isThreadListVisible()" @click="storage.setThreadListVisibility(false)"></i>
            </div>
            <ul v-show="storage.isThreadListVisible()">
                <li v-for="(t, index) in threads" :class="{ active: (threadScrollPos == index) }" @click="$scrollTo(index)">
                    <a>/{{t.thread.board.dir}}/ &mdash; {{t.thread.title}}</a>
                </li>
            </ul>
        </div>

        <div v-scroll-spy="threadScrollPos">
            <div v-for="thread in threads" style="margin-top: 20px;">
                <BoardThreadPreview :thread="thread" @reply="onPostSubmit" />
                <div class="separator thread-separator"></div>
            </div>
        </div>

        <div v-if="board && !threads.length">
            <div class="empty-page">
                <h3>Здесь пока ничего нет</h3>
                <br/>
                <div v-if="type == 'watched'">
                    Чтобы добавить сюда тред, нужно нажать в нем кнопку <i class="fa fa-star"></i>.
                </div>
                <div v-if="type == 'favourite'">
                    Чтобы подписаться на доску, нужно нажать в ней кнопку <i class="fa fa-heart"></i>.
                </div>
            </div>
        </div>

        <div class="vspace2 text-center" v-if="pagination.hasMore">
            <VisibilityTracker @enter="getMoreThreads" :area="200">
                <button class="btn btn-default" @click.prevent="getMoreThreads" :disabled="fetchingMore">
                <span v-if="fetchingMore">
                    <i class="fa fa-spinner fa-spin fa-fw"></i> подгружаем еще треды
                </span>
                    <span v-if="!fetchingMore">
                    <i class="fa fa-ellipsis-h"></i> подгрузить еще треды
                </span>
                </button>
            </VisibilityTracker>
        </div>
    </div>
</template>

<script>
    import 'bootstrap-sass/assets/javascripts/bootstrap/dropdown';
    import $ from 'jquery';
    import BusEvents from '../app/BusEvents';
    import Api from '../services/Api';
    import Storage from '../services/Storage';

    import Headline from './Headline.vue';
    import ContentLink from './ContentLink.vue';
    import BoardThreadPreview from './BoardTreadPreview.vue';
    import PostForm from './PostForm';
    import Session from '../services/Session';
    import Board from '../services/Board';
    import VisibilityTracker from './VisibilityTracker.vue';
    import BoardInfo from './BoardInfo.vue';
    import BoardControlButton from './BoardControlButton.vue';

    export default {
        components: {
            Headline,
            BoardThreadPreview,
            PostForm,
            ContentLink,
            VisibilityTracker,
            BoardControlButton
        },
        data () {
            return {
                auth: Session.auth,
                storage: Storage,
                type: null,
                board: null,
                threads: [],
                newThreadForm: false,
                pagination: null,
                fetchingMore: false,
                threadScrollPos: 0,
            }
        },
        computed: {
            threadCount() {
                return this.board ? this.board.threads.length : 0;
            }
        },
        beforeRouteUpdate(to, from, next) {
            if (!this.$router.sameRoute(from, to)) {
                this.$router.saveStateCache(this);
                this.board = null; // -> v-if hides components, animations work
            }
            next();
        },
        beforeRouteLeave(to, from, next) {
            if (!this.$router.sameRoute(from, to)) {
                this.$router.saveStateCache(this);
            }
            next();
        },
        mounted() {
            $(window).on('scroll resize', this.updateThreadScrollSpy);
        },
        beforeDestroy() {
            $(window).off('scroll resize', this.updateThreadScrollSpy);
        },
        methods: {
            fetch() {
                if (this.$router.restoreStateCache(this)) {
                    return Promise.resolve();
                }
                this.board = null;
                this.threads = [];
                this.type = this.$route.name;

                return Board.get(this.getRouteParams()).then(response => {
                    this.board   = response.data.board;
                    this.threads = response.data.threads;
                    this.pagination = response.data.pagination;
                    this.threadScrollPos = 0;
                });
            },
            getRouteParams() {
                const params = {};
                if (this.$route.params.dir) {
                    params.dir = this.$route.params.dir;
                } else if (this.type == 'watched' || this.type == 'favourite') {
                    params[this.type] = true;
                }
                return params;
            },
            toggleNewThreadForm() {
                this.newThreadForm = !this.newThreadForm;
            },
            toggleFavourite() {
                const isFav = this.board.isFavourite;
                this.board.isFavourite = null;
                Board.favourite(this.board.dir, !isFav)
                    .then(response => {
                        if (response.data && response.data.ok) {
                            this.board.isFavourite = response.data.isFavourite;
                        } else {
                            this.board.isFavourite = isFav;
                        }
                    });
            },
            getMoreThreads() {
                if (this.fetchingMore || !this.pagination || !this.pagination.hasMore) {
                    return;
                }
                this.fetchingMore = true;
                let params = this.getRouteParams();
                params = { ...params, ...{ cursor: this.pagination.cursor, page: this.pagination.page + 1} };
                Board.get(params).then(response => {
                    this.pagination = response.data.pagination;
                    this.threads = this.threads.concat(response.data.threads);
                    this.fetchingMore = false;
                });
            },
            onPostSubmit(post) {
                this.$router.push({
                    name: 'thread',
                    params: { dir: post.boardDir, threadId: post.threadId },
                    hash: '#' + post.id
                });
            },
            showBoardInfo() {
                this.$bus.emit(BusEvents.SHOW_MODAL, BoardInfo, { board: this.board })
            },
            updateThreadScrollSpy() {
                if (!this.$refs.scrollSpy) { return }

                const offset = 40;
                const viewH = window.innerHeight - offset /*top*/ - offset /* bottom */;
                const scrollPos = window.document.body.scrollTop / (window.document.body.scrollHeight - viewH);
                const fullH = this.$refs.scrollSpy.clientHeight;

                let overflow = fullH - viewH;
                if (overflow < 0) {
                    overflow = 0;
                }

                this.$refs.scrollSpy.style.top = (offset - overflow * scrollPos) + 'px';
            },
        },
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .thread-separator {
        margin-top: 40px !important;
        margin-bottom: 40px !important;
    }

    .threads-scroll-spy {
        -webkit-transform: translateZ(0);
        -webkit-backface-visibility: hidden;

        position: fixed;
        z-index: 1;
        //top: 50px;
        right: 10px;
        width: 400px;
        opacity: 0.2;
        transition: opacity .2s linear;

        .threads-scroll-toggler {
            position: absolute;
            right: -10px;
            top: 0;
            padding: 2px;
            color: $color-grey-dk;
            cursor: pointer;
        }

        &:hover {
            opacity: 1;
            background: rgba(255,255,255,0.75);
            box-shadow: 0 1px 4px #ccc;


            li:hover, li.active {
                background: rgba(desaturate($input-border-focus, 50%), .1);
            }
        }

        ul {
            list-style: none;
            margin: 0;
            padding: 0;

            a {
                text-decoration: none;
            }

            li {
                transition: background .2s linear, border-color .2s linear;
                border-right: 4px solid rgba(255,255,255,0);
                padding: 2px 4px;
            }
            li:hover, li.active {
                border-color:  $input-border-focus;
            }
        }
    }
</style>
