<template>
    <div>
        <div v-if="stats">
            <div>
                <line-chart id="board-stats" :data="chart.data" :xkey="chart.xkey" :ykeys="chart.ykeys" :labels="chart.labels" :resize="true"></line-chart>
            </div>
        </div>
        <div v-if="!dir" class="empty-page">
            Не выбрана доска
        </div>
    </div>
</template>

<script>
    import Moderation from '../../services/Moderation';
    import { LineChart } from 'vue-morris'

    export default {
        components: {
            LineChart
        },
        props: [
            'dir'
        ],
        methods: {
            fetch() {
                this.stats = null;
                if (this.dir) {
                    return Moderation.stats(this.dir).then(
                        response => {
                            this.stats = response.data.stats;
                        }
                    );
                } else {
                    return Promise.resolve()
                }
            },
        },
        data() {
            return {
                stats: null,
            }
        },
        computed: {
            chart() {
                return {
                    xkey: 'key',
                    ykeys: [ 'posts', 'threadsNew', 'threadsActive', 'uniquePosters' ],
                    labels: [ 'Новых постов', 'Новых тредов', 'Активных тредов', 'Уникальных авторов' ],
                    data: this.stats
                };

            },

        }
    }
</script>
