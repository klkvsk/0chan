<template>
    <div>
        <Headline>
            <span slot="title">Управление</span>
        </Headline>
        <div class="panel panel-default vspace">
            <div class="panel-heading">
                <ul class="nav nav-pills">
                    <router-link v-for="section in sections"
                                 :key="section.route"
                                 :to="{ name: section.route }"
                                 tag="li" class="nav-item" active-class="active">
                        <a class="nav-link">
                            <i :class="'fa fa-fw ' + section.icon"></i>
                            {{section.title}}
                        </a>
                    </router-link>
                </ul>
            </div>

            <div class="panel-body">
                <div v-if="loading" class="empty-page">
                    <i class="fa fa-3x fa-pulse fa-spinner"></i>
                </div>
                <router-view ref="view"></router-view>
            </div>
        </div>
    </div>
</template>

<script>
    import Headline from '../Headline.vue';
    import Session from '../../services/Session'

    export default {
        components: {
            Headline
        },
        data() {
            return {
                loading: false,
            }
        },
        computed: {
            sections() {
                const list = [
                    {
                        title: 'Доски',
                        icon: 'fa-list',
                        route: 'admin_boards'
                    }
                ];
                if (Session.isGlobalAdmin) {
                    list.push({
                        title:  'Глобальные модераторы',
                        icon:   'fa-users',
                        route:  'admin_globals'
                    })
                }
                return list;
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                return this.$refs.view.fetch()
                  .then(r => { this.loading = false; return r });
            }
        }
    }
</script>
