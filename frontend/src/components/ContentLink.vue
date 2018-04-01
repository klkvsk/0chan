<template>
    <a :href="href" @click.prevent.stop="onClick" :class="{ active: isActive }">
        <slot></slot>
    </a>
</template>

<script>
    import Router from '../app/Router'
    import BusEvents from '../app/BusEvents'

    function isObjectEqual (a, b) {
        if ( a === void 0 ) a = {};
        if ( b === void 0 ) b = {};

        const aKeys = Object.keys(a);
        const bKeys = Object.keys(b);
        if (aKeys.length !== bKeys.length) {
            return false
        }
        return aKeys.every(function (key) { return String(a[key]) === String(b[key]); })
    }

    function sameRoute(a, b) {
        return a.name === b.name
            //&& a.hash === b.hash
            && isObjectEqual(a.params, b.params)
            && isObjectEqual(a.query, b.query);
    }

    export default {
        props: [ 'to', 'class', 'clickHandler' ],
        data() {
            return {
                current: null
            }
        },
        created() {
            this.current = Router.currentRoute;
        },
        computed: {
            href() {
                return Router.resolve(this.to).href
            },
            route() {
                return Router.resolve(this.to).route
            },
            isActive() {
                return sameRoute(this.route, this.current);
            }
        },
        watch: {
            $route() {
                this.current = Router.currentRoute;
            }
        },
        methods: {
            onClick() {
                if (this.clickHandler) {
                    this.clickHandler();
                    return false;
                }
                if (this.isActive) {
                    this.$bus.emit(BusEvents.REFRESH_CONTENT);
                } else {
                    this.$router.push(this.to);
                }
            }
        }
    }
</script>