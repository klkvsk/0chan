<template>
    <div>
        <transition name="slide-fade">
            <router-view v-show="!hasError" ref="view"></router-view>
        </transition>
        <transition name="slide-fade">
            <NotFound v-if="notFound"></NotFound>
        </transition>
        <transition name="slide-fade">
            <Forbidden v-if="forbidden" :reason="forbiddenReason"></Forbidden>
        </transition>
    </div>
</template>

<script>
    import BusEvents from '../app/BusEvents'
    import NotFound from './NotFound.vue'
    import Forbidden from './Forbidden.vue'

    var contentFetchPromise;

    export default {
        components: { NotFound, Forbidden },
        data() {
            return {
                notFound: false,
                forbidden: false,
                forbiddenReason: undefined,
                contentClass: null,
            }
        },
        mounted() {
            this.$bus.on(BusEvents.REFRESH_CONTENT, () => {
                this.fetch();
            });
            this.fetch();
        },
        watch: {
            $route(to, from) {
                if (!this.$router.sameRoute(to, from)) {
                    this.fetch();
                }
            }
        },
        methods: {
            fetch() {
                this.notFound = false;
                this.forbidden = false;
                this.forbiddenReason = undefined;
                this.$Progress.start();
                this.$nextTick(() => {
                    if (this.$refs.view) {
                        if (contentFetchPromise) {
                            contentFetchPromise.cancel();
                        }
                        if (this.$refs.view.fetch) {
                            contentFetchPromise = this.$refs.view.fetch()
                                .catch(error => {
                                    this.$Progress.fail();
                                    if (error.response && error.response.status == 404) {
                                        this.notFound = true
                                    } else if (error.response && error.response.status == 403) {
                                        this.forbidden = true;
                                        if (error.response.data && error.response.data.details) {
                                            this.forbiddenReason = error.response.data.details.reason;
                                        }
                                    } else {
                                        throw error;
                                    }
                                });
                        } else {
                            contentFetchPromise = Promise.resolve();
                        }

                        contentFetchPromise.then(() => this.$Progress.finish());
                        contentFetchPromise.then(() => this.$bus.emit(BusEvents.REFRESH_CONTENT_DONE));
                    }
                });
            }
        },
        computed: {
            hasError() {
                return this.forbidden || this.notFound;
            }
        }
    }
</script>