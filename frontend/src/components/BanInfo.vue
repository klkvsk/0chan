<template>
    <div v-if="ban" class="block ban-info">
        Бан №{{ban.id}} <br>
        Причина: {{ban.reason}} <br>
        <br>
        Выдан: {{ban.bannedAt | timestamp }} <br>
        Действует до: {{ban.bannedTill | timestamp }} <br>
        Осталось: {{ban.timeleft | timespan }}
        <div v-if="appealable">
            <div class="separator"></div>
            Можно оставить объяснительную:
            <div class="input-group">
                <input type="text" :value="ban.appeal" v-model="appeal" class="form-control" />
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-default" @click.prevent="sendAppeal">OK</button>
                </span>
            </div>
        </div>
        <div v-else>
            <span v-if="ban.appeal">Объяснительная: <i>{{ban.appeal}}</i></span>
        </div>
    </div>
</template>

<script>
    import Moderation from '../services/Moderation'
    import BusEvents from '../app/BusEvents'

    export default {
        props: [ 'ban', 'appealable' ],
        data() {
            return { appeal: '' }
        },
        created() {
            this.appeal = this.ban.appeal || '';
        },
        methods: {
            sendAppeal() {
                if (!this.appealable || this.appeal == '' || this.appeal === this.ban.appeal) {
                    return;
                }
                Moderation.appealBan(this.ban.id, this.appeal).then(
                    response => {
                        if (response.data.ok) {
                            this.$bus.emit(BusEvents.ALERT_INFO, 'Мочераторы подумают, но ничего не обещают.');
                        }
                    }
                );
                this.close();
            },
            close() {
                this.$emit('close');
            }
        }
    }
</script>

<style >
    .ban-info.block {
        width: 400px;
    }
</style>