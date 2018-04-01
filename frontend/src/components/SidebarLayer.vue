<template>
    <div class="sidebar" :class="{ 'mobile-open': visible }">
        <button class="btn btn-link visible-xs-inline-block btn-hide-sidebar" @click="visible = false">
            <i class="fa fa-bars"></i>
        </button>
        <SidebarMenu v-if="session.auth" class="sidemenu-menu-top" :menu="menuTop"></SidebarMenu>

        <div v-else class="sidebar-logo">
            <content-link :to="{name: 'home'}">
                <img src="../assets/images/logo-full.png" class="hidden-xs">
                <div class="hidden visible-xs" style="margin-top: 12px">
                    <img src="../assets/images/logo-mob.png" :srcset="require('../assets/images/logo-mob@2x.png') + ' 2x'" />
                </div>
            </content-link>
        </div>

        <div class="sidemenu-boards">
            <form class="form-horizontal" @submit.prevent="onBoardEnter">
                <div class="sidemenu-boards-search has-feedback" :class="search ? 'filled' : 'empty'">
                    <input type="text" autocorrect="off" autocomplete="off" autocapitalize="off" ref="search" v-model="search" class="form-control" placeholder="Найти раздел" />
                    <span :class="search ? 'glyphicon-remove' : 'glyphicon-search'"
                          @click="search = ''"
                          class="search-clear glyphicon form-control-feedback"></span>
                </div>
            </form>

            <div class="sidemenu-boards-list-box">
                <div class="sidemenu-boards-list">
                    <div class="sidemenu-board-item" v-for="board in filteredBoards">
                        <content-link :to="{name: 'board', params: { dir: board.dir } }">
                            <span class="pull-right sidemenu-board-icons">
                                <span v-if="board.isModerated" title="Модерируемая"><i class="fa fa-eye"></i></span>
                                <span v-if="board.isFavourite" title="Избранная"><i class="fa fa-heart-o"></i></span>
                            </span>
                            <span class="sidemenu-board-title">{{board.name}}</span>
                            <span class="sidemenu-board-dir">&mdash; {{board.dir}}</span>
                        </content-link>
                    </div>
                </div>
            </div>
        </div>

        <SidebarMenu class="sidemenu-menu-btm" :menu="menuBottom"></SidebarMenu>

        <div class="sidemenu-toggle fa"></div>
    </div>
</template>

<script type="text/babel">
    import Updater from '../app/Updater';
    import SidebarMenu from './SidebarMenu.vue';
    import BusEvents from '../app/BusEvents';
    import User from '../services/User';
    import Session from '../services/Session';
    import Board from '../services/Board';
    import ContentLink from './ContentLink.vue';

    export default {
        components: {
            SidebarMenu, ContentLink
        },
        data() {
            return {
                visible: false,
                session: Session,
                search: '',
                boardList: []
            }
        },
        watch: {
            '$route': function () {
                this.visible = false;
            }
        },
        created() {
            new Updater(() => this.fetchBoardList(), 150000).checkLater();
            this.$bus.on(BusEvents.REFRESH_CONTENT, () => this.visible = false);
            this.fetchBoardList();
            this.$bus.on(BusEvents.REFRESH_SIDEBAR, () => {
                this.fetchBoardList();
            });
            this.$bus.on(BusEvents.TOGGLE_SIDEBAR,
                isVisible => {
                    if (isVisible === undefined) {
                        this.visible = !this.visible;
                    } else {
                        this.visible = isVisible;
                    }
                }
            );
        },
        methods: {
            logout() {
                return User.logout().then(() => {
                    if (this.$route.meta.auth) {
                        this.$router.push({name: 'home'})
                    } else {
                        this.$bus.emit(BusEvents.REFRESH_CONTENT);
                    }
                    this.$bus.emit(BusEvents.REFRESH_SIDEBAR);
                });
            },
            fetchBoardList() {
                return Board.list().then((response) => {
                    this.boardList = response.data.boards;
                })
            },
            forceBoardRoute(route) {
                if (route.name === this.$route.name && route.params.dir === this.$route.params.dir) {
                    this.$bus.emit(BusEvents.REFRESH_CONTENT);
                    this.visible = false;
                } else {
                    this.$router.push(route);
                }
            },
            onBoardEnter() {
                const boards = this.filteredBoards;
                if (!boards || !boards.length) {
                    if (this.search.match(/^[a-z0-9]+$/i)) {
                        this.forceBoardRoute({ name: 'board', params: { dir: this.search }});
                    }
                    return;
                }
                const board = boards[0];
                this.forceBoardRoute({ name: 'board', params: { dir: board.dir }});
                this.search = '';
                this.$refs.search.blur();
            }
        },
        computed: {
            filteredBoards() {
                try {
                    const searchRE = new RegExp(this.search, 'i');
                    return this.boardList.filter((board) => {
                        return board.dir.match(searchRE)
                            || board.name.match(searchRE)
                    });
                } catch (e) {
                    return this.boardList;
                }
            },
            menuTop() {
                return [
                    {title: 'Все треды',    url: {name: 'home'} ,       html_class: 'li_news'},
                    {title: 'Моя подборка', url: {name: 'favourite'} ,  html_class: 'li_heart'},
                    {title: 'Отмеченное',   url: {name: 'watched'},     html_class: 'li_star'},
                    {title: 'Сообщения',    url: {name: 'im'},          html_class: 'li_mail', count: Session.messages }
                ];
            },
            menuBottom() {
                return Session.auth ? [
                        {title: 'Управление',   url: { name: 'admin' },     icon: 'sitemap'},
                        {title: 'Модерация',    url: { name: 'mod' } ,      icon: 'eye'},
                        {title: 'Аккаунт',      url: { name: 'account' } ,  icon: 'user-circle-o'},
                        {title: 'Выход',        click: this.logout,         icon: 'sign-out'}
                    ] : [
                        {title: 'Вход', url: {name: 'login'}, icon: 'sign-in'}
                    ];
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .sidebar {
        -webkit-transform: translateZ(0);
        -webkit-backface-visibility: hidden;

        padding: 20px;
        background: #2C333D url("../assets/images/bg-dark-side.png") repeat-y right;

        .sidebar-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        a {
            text-decoration: none;
            color: darken($color-grey, 10%);
        }

        ul.list {
            padding: 0;

            li {
                list-style: none;
                padding-top: 5px;
                padding-left: 0;
                text-transform: uppercase;

                a {
                    &:hover span,
                    &:active span {
                        color: $color-white;
                    }

                    &:hover i,
                    &:hover::before {
                        color: $color-green-lt;
                    }
                }
            }
        }

        .menu-new-count {
            color: $color-green-lt;
            float: right;
            margin-top: 1px;
            margin-right: 10px;
        }

        .sidemenu-menu-btm {
            bottom: 15px;
            position: absolute;
        }

        .sidemenu-boards-search
        {
            margin-bottom: 10px;

            input {
                background: $color-blue-dk;
                border-color: $color-grey-dk;
                border-style: solid;
                color: $color-grey-lt;
                outline: none;
                border-radius: 20px;
                padding: 4px 10px;


                &:focus {
                    border-color: $color-green-lt;
                    box-shadow: 0 0 3px $color-green-lt;
                }
            }

            .search-clear {
                color: $color-grey-dk;
                right: 0;
                transition: color 0.2s linear;
            }

            &.filled .search-clear:hover {
                color: $color-green-lt;
            }
        }

        .sidemenu-boards-list-box {
            position: absolute;
            top: 175px;
            left: 0;
            right: 0;
            bottom: 120px;
            overflow: hidden;

            .sidemenu-boards-list {
                position: absolute;
                overflow-y: scroll;
                overflow-x: hidden;
                top: 0;
                right: -18px;
                bottom: 0;
                left: 0;

                .sidemenu-board-item {

                    a {
                        transition: background .2s linear, border-color .2s linear, color .2s linear !important;
                        border-left: 22px solid rgba(0,0,0,0);
                        border-right: 25px solid rgba(0,0,0,0);
                        line-height: 1em;
                        padding: 3px 0;
                        display: block;
                    }

                    .sidemenu-board-title {
                        color: $color-grey-lt;
                    }

                    .sidemenu-board-dir {
                        color: $color-grey;
                        font-size: 80%;
                        white-space: nowrap;
                    }

                    .sidemenu-board-icons {
                        > span {
                            padding-left: 4px;
                        }
                    }

                    a:hover, a.active {
                        background: $color-blue;
                        border-color: $color-blue;

                        .sidemenu-board-title {
                            color: $color-white;
                        }
                        .sidemenu-board-dir {
                            color: $color-white-dk;
                        }
                    }
                }
            }

        }
    }

    @media (max-width: $screen-xs-max) {
        //@include media-breakpoint-down(xs) {
        .sidebar {
            transition: margin-left 0.3s cubic-bezier(0,.85,.72,.99);
            margin-left: -100vw;
            &.mobile-open {
                margin-left: 0;
            }

            .btn-hide-sidebar {
                position: absolute;
                left: 3px;
                top: 2px;
                z-index: 1000;
                font-size: 14px;
                color: $color-grey-lt !important;
            }
            padding: 0;
            .list {
                font-size: 150%;
                width: 100%;
                li {
                    display: inline-block;
                    margin-left: 16px;
                    margin-right: 16px;
                }
            }
            .sidemenu-menu-top {
                text-align: center;
                top: 10px;
                position: absolute;
            }
            .sidemenu-menu-btm {
                text-align: right;
                right: 20px;
                bottom: 0;
                position: absolute;
            }

            .sidemenu-boards-search {
                position: absolute;
                top: 70px;
                left: 20px;
                right: 20px;
                input { font-size: 120% }
            }

            .sidemenu-boards-list-box {
                position: absolute;
                top: 120px;
                left: 0;
                right: 0;
                bottom: 60px;

                .sidemenu-boards-list {
                    .sidemenu-board-item {
                        a {
                            line-height: 1.5em;
                            font-size: 120%;
                            display: block;
                        }
                    }
                }
            }
        }
    }
</style>