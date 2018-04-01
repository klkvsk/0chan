<template>
    <div>
        <Headline>
            <span slot="title">Модерация <span v-if="dir">/{{dir}}/</span></span>
        </Headline>
        <div class="panel panel-default vspace">
            <div v-if="moderatedBoards !== null" class="panel-heading">
                <ul class="nav nav-pills pull-left">
                    <router-link v-for="section in sections"
                                 :key="section.route"
                                 :to="{ name: section.route, params: { dir }}"
                                 tag="li" class="nav-item" active-class="active">
                        <a class="nav-link">
                            <i :class="'fa fa-fw ' + section.icon"></i>
                            {{section.title}}
                            <sup v-if="section.count > 0">({{section.count}})</sup>
                        </a>
                    </router-link>
                </ul>
                <div class="col-md-8 pull-right">
                    <select class="form-control" v-model="boardSelector">
                        <option :value="null">-- все модерируемые --</option>
                        <option v-for="board in moderatedBoards" :value="board.dir">{{board.dir}} &mdash; {{board.name}}</option>
                    </select>
                </div>

                <div class="clearfix"></div>
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
    import Post from '../Post.vue';
    import Moderation from '../../services/Moderation';

    export default {
        props: [ 'dir' ],
        components: {
            Headline
        },
        created() {
            if (this.dir) {
                this.boardSelector = this.dir;
            }
        },
        data() {
            return {
                loading: false,
                newReports: 0,
                newAppeals: 0,
                moderatedBoards: null,
                boardSelector: null,
            }
        },
        watch: {
            boardSelector() {
                this.$router.push({ name: this.$route.name, params: { dir: this.boardSelector } });
            }
        },
        computed: {
            sections() {
                return [
                    { title: 'Фид картинок',    icon: 'fa-film',        route: 'mod_feed' },
                    { title: 'Жалобы',          icon: 'fa-flag-o',      route: 'mod_reports', count: this.newReports },
                    { title: 'Баны',            icon: 'fa-gavel',       route: 'mod_bans',    count: this.newAppeals },
                    { title: 'Логи',            icon: 'fa-history',     route: 'mod_logs' },
                    { title: 'Статистика',      icon: 'fa-bar-chart',   route: 'mod_stats'}
                ]
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                return Promise.all([
                    Moderation.get(this.dir).then(
                        response => {
                            this.moderatedBoards = response.data.moderatedBoards;
                            this.newReports      = response.data.newReports;
                        }
                    ),
                    this.$refs.view.fetch().then(r => { this.loading = false; return r })
                ]);
            }
        }
    }
</script>
