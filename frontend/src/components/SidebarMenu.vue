<template>
    <ul class="list">
        <li v-for="item in menu" class="menu-item">
            <content-link :title="item.title" :class="[item.html_class]" :to="item.url || '#'" :clickHandler="item.click">
                <i v-if="item.icon" :class="['fa', 'fa-' + item.icon]"></i>
                <span class="hidden-xs">{{item.title}}</span>
                <span class="hidden-xs menu-new-count" v-if="item.count">+{{item.count}}</span>
            </content-link>
            <div v-if="item.sub">
                <ul>
                    <li v-for="item in item.sub">
                        <content-link :to="item.url">{{item.title}}</content-link>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</template>

<script>
    import ContentLink from './ContentLink.vue'
    export default {
        props: [ 'menu' ],
        components: { ContentLink },
        data() {
            return {
                noop: () => { console.log('noops')}
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss" scoped>
    [class*="li_"]::before {
        font-size: 150%;
        line-height: 1;
    }

    li:hover {
        [class*="li_"]::before { transition: color 0.2s linear !important; }

    }
</style>