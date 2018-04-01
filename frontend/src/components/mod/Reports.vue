<template>
    <div v-if="reports">
        <Pagination :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>
        <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>Пост</th>
                <th>Почему горит жопа</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="report in reports" :class="{ 'report': true, 'report-post-approved': report.isApproved }">
                <td width="50%">
                    <Post :post="report.post" />
                </td>
                <td>
                    <div>
                        <ul>
                            <li v-for="reason in report.reasons">
                                {{ reason.message }}
                            <span class="text-muted">({{reason.date | timestamp}})</span>
                            </li>
                        </ul>
                    </div>
                </td>
                <td align="right">
                    <div>
                        <button class="btn btn-default" title="Не принимать больше жалобы на этот пост" @click.prevent="markApproved(report)">
                            <i v-if="report.isApproved === true"  class="fa fa-fw fa-check-square-o"></i>
                            <i v-if="report.isApproved === null"  class="fa fa-fw fa-spin fa-spinner"></i>
                            <i v-if="report.isApproved === false" class="fa fa-fw fa-square-o"></i>
                            Проверенный
                        </button>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        </div>
        <Pagination :page="pagination.page" :total-pages="pagination.total" @change="onPageChange"></Pagination>
    </div>
</template>

<script>
    import Pagination from '../Pagination.vue';

    import Post from '../Post.vue';
    import Moderation from '../../services/Moderation';

    export default {
        props: [
            'dir'
        ],
        components: {
            Post, Pagination
        },
        data() {
            return {
                reports: null,
                pagination: null
            }
        },
        methods: {
            fetch() {
                this.reports = null;
                return Moderation.reports(this.dir, this.$route.query.page).then(
                    response => {
                        this.reports = response.data.reports;
                        this.pagination = response.data.pagination;
                    }
                );
            },
            markApproved(report) {
                const oldState = report.isApproved;
                report.isApproved = null;
                return Moderation.markApproved(report.post.id, !oldState).then(
                        response => {
                            if (response.data.ok) {
                                report.isApproved = response.data.isApproved;
                            } else {
                                report.isApproved = oldState;
                            }
                        }
                );
            },
            onPageChange(page) {
                this.$router.push({ name: 'mod_reports', params: {dir: this.dir}, query: {page: page} });
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    .report {
        transition: opacity .2s linear;
        &.report-post-approved > td > div {
            height: 34px !important;
            overflow: hidden;

            > * { opacity: 0.5 }

            .post:hover {
                position: absolute;
                opacity: 1;
                z-index: 1;
            }
        }
    }
</style>
