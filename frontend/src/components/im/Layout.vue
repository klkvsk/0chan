<template>
    <div>
        <Headline>
            <span slot="title">Сообщения</span>
            <span slot="buttons">
                <button class="btn btn-primary hidden-lg" @click="isDialogsView = !isDialogsView">
                    <i class="fa fa-fw fa-list"></i> {{isDialogsView ? 'Скрыть' : 'Показать'}} диалоги
                </button>
                <button class="btn btn-default hidden visible-lg" @click="isSwapLayout = !isSwapLayout">
                    <i class="fa fa-fw fa fa-arrows-h"></i>
                </button>
            </span>
        </Headline>

        <div class="im-box">

            <div class="dialog-list" :class="{ 'hidden-xs hidden-sm hidden-md': !isDialogsView, 'swap-layout': isSwapLayout }">
                <DialogList ref="list"></DialogList>
            </div>

            <div class="dialog-view" :class="{ 'hidden-xs hidden-sm hidden-md': isDialogsView, 'swap-layout': isSwapLayout } ">
                <router-view ref="view" @search="setListFilter"></router-view>
            </div>
        </div>
    </div>
    </div>
</template>

<script>
    import Headline from '../Headline.vue'
    import DialogList from './List.vue'

    export default {
        components: {
            Headline,
            DialogList,
        },
        contentClass: 'dialogs',
        data() {
            return {
                ready: false,
                isDialogsView: false,
                isSwapLayout: false,
            }
        },
        watch: {
            $route() {
                this.isDialogsView = false;
            }
        },
        methods: {
            fetch() {
                let promise = null;
                if (this.$refs.view && this.$refs.view.fetch) {
                    promise = this.$refs.view.fetch();
                } else {
                    promise = Promise.resolve();
                }


                promise = promise.then( this.$nextTick(() => this.$refs.list.fetch()) );

                promise = promise.then( () => this.ready = true );

                return promise;
            },
            setListFilter(search) {
                this.$refs.list.setListFilter(search);
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import '~assets/styles/_vars';

    #content.dialogs {
        position: fixed !important;
        top: 0; bottom: 0; right: 0; left: 0;
    }

    $list-width: 300px;

    .im-box {
        position: absolute;
        top: 50px;
        left: 10px;
        right: 10px;
        bottom: 10px;

        .dialog-list {
            position: absolute;
            &.swap-layout { left: auto; right: 0 }
            left: 0;
            top: 0;
            bottom: 0;
            width: $list-width;
            height: 100%;

            @media(max-width: $screen-md-max) {
                left: 0 !important;
                right: 0 !important;
                width: 100%;
            }

            .dialog-list-filter {
                position: absolute;
                left: 0;
                top: 0;
                right: 0;
            }
            .dialog-list-items {
                position: absolute;
                left: 0;
                top: 40px;
                right: 0;
                bottom: 0;
                overflow: auto;

                .dialog-last-message-text {
                    padding: 2px 0;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .dialog-list-count-unread {
                    position: absolute;
                    right: 5px;
                    top: 5px;
                    padding: 0 4px;
                    background: $color-green;
                    border-radius: 10px;
                    color: $color-white;
                    font-weight: bold;
                    font-size: 0.8em;
                }

                a {
                    &:focus {
                        background: #ffffff;
                    }
                    &:hover {
                        background: inherit;
                    }
                    &.router-link-active {
                        border-right: 2px $color-green solid;
                    }
                }
            }

            .search-clear {
                color: $color-grey-lt;
                transition: color 0.2s;

                &.search-filled:hover {
                    color: $color-green;
                }
            }
        }
        .dialog-view {
            position: absolute;

            left: $list-width + 10px;
            right: 0;
            &.swap-layout {
                left: 0;
                right: $list-width + 10px;
            }

            top: 0;
            bottom: 0;
            background: $color-white-dk;

            @media(max-width: $screen-md-max) {
                right: 0 !important;
                left: 0 !important;
            }
        }

        .dialog-start {
            margin: 40px auto;
            width: 300px;
        }
    }
</style>