<template>
    <div>
        <Headline>
            <span slot="title">{{isNew ? 'Создание доски' : 'Редактирование доски /' + this.dir + '/' }}</span>
        </Headline>

        <div v-if="isNew || board != null">
            <div class="vspace center-block" style="max-width: 800px">
                <div class="panel panel-default">
                    <form @submit.prevent="onSubmit">
                        <div class="panel-body">
                            <FormBuilder :form="form" :data="board"></FormBuilder>
                        </div>
                        <div class="panel-footer">
                            <router-link :to="{ name: 'admin' }" class="btn" :exact="true">
                                <i class="fa fa-chevron-left"></i> Назад
                            </router-link>
                            <button type="submit" class="btn btn-primary pull-right"  :disabled="submitting">
                                <i class="fa" :class="{'fa-plus': isNew, 'fa-save': !isNew}"></i> {{isNew ? 'Создать' : 'Сохранить'}}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="vspace2 separator"></div>

                <div v-if="!isNew" class="panel panel-default">
                    <div class="panel-body">
                        <form class="form form-inline" @submit.prevent="changeOwner">
                            Передать управление доской:
                            <div class="input-group">
                                <input type="text" placeholder="логин" v-model="newOwner" class="form-control" />
                                <span class="input-group-btn">
                                <button type="submit" class="btn btn-danger" :disabled="submitting">
                                    <i class="fa fa-check"></i>
                                </button>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import BusEvents from '../../app/BusEvents';
    import Headline from '../Headline.vue';
    import FormBuilder from '../FormBuilder.vue';
    import Management from '../../services/Management'

    export default {
        components: {
            Headline, FormBuilder
        },
        props: [ 'dir' ],
        data() {
            return {
                board: null,
                form: {},
                newOwner: '',
                submitting: false,
            }
        },
        computed: {
            isNew() { return !this.dir; }
        },
        methods: {
            fetch() {
                return Management.board(this.dir).then(
                    response => {
                        this.board = response.data.board;
                        this.form  = response.data.form;
                    }
                )
            },
            onSubmit() {
                this.submitting = true;
                Management.board(this.dir, this.board).then(
                    response => {
                        if (response.data.error) {
                            this.form = response.data.form;
                        } else {
                            this.$bus.emit(BusEvents.REFRESH_SIDEBAR);
                            this.$router.push({ name: 'admin' })
                        }
                        this.submitting = false;
                    }
                )
            },
            changeOwner() {
                if (!this.newOwner) return;
                if (!confirm('Вы потеряете доступ к управлению этой доской. Продолжить?')) {
                    return;
                }
                this.submitting = true;
                Management.changeOwner(this.dir, this.newOwner).then(
                    response => {
                        if (response.data.ok) {
                            this.$bus.emit(BusEvents.ALERT_SUCCESS, `Доступ к /${this.dir}/ передан ${this.newOwner}`);
                            this.$router.push({ name: 'admin' })
                        } else {
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.reason || 'Ошибка');
                        }
                        this.submitting = false;
                    }
                )
            }
        }
    }
</script>

