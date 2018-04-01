<template>
    <div class="profile-page">
        <Headline>
            <span slot="title">
                Аккаунт и настройки
            </span>
        </Headline>

        <div v-if="user" style="margin-top: 10px">
            <div class="row">
                <div class="col-md-12">
                    <form @submit.prevent="saveSettings">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <b>Аккаунт: {{user.login}}</b>
                            </div>
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <FormBuilder :form="form" :data="user" />
                                </div>
                            </div>
                            <div class="panel-footer text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-gears"></i> Сохранить
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-12">
                    <form @submit.prevent="changePassword">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <b>Сменить пароль</b>
                            </div>
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <div class="form-group" :class="{'has-error': passwordChangeFormErrors.oldPassword}">
                                        <label class="control-label col-md-8">Старый пароль</label>
                                        <div class="col-md-12">
                                            <input type="password" class="form-control" v-model="passwordChangeForm.oldPassword" />
                                        </div>
                                    </div>
                                    <div class="form-group" :class="{'has-error': passwordChangeFormErrors.newPassword}">
                                        <label class="control-label col-md-8">Новый пароль</label>
                                        <div class="col-md-12">
                                            <input type="password" class="form-control" v-model="passwordChangeForm.newPassword" />
                                        </div>
                                    </div>
                                    <div class="form-group" :class="{'has-error': passwordChangeFormErrors.newRepeated}">
                                        <label class="control-label col-md-8">Повтор</label>
                                        <div class="col-md-12">
                                            <input type="password" class="form-control" v-model="passwordChangeForm.newRepeated" />
                                        </div>
                                    </div>
                                    <ul class="has-error col-md-offset-2">
                                        <li class="help-block" v-for="error in passwordChangeFormErrors">{{error}}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="panel-footer text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-lock"></i> Сменить
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    import BusEvents from '../app/BusEvents';
    import User from '../services/User';
    import Session from '../services/Session';
    import Headline from './Headline.vue';
    import FormBuilder from './FormBuilder.vue';

    export default {
        components: {
            Headline, FormBuilder
        },
        data() {
            return {
                user: null,
                passwordChangeForm: {
                    oldPassword: '',
                    newPassword: '',
                    newRepeated: ''
                },
                passwordChangeFormErrors: {
                    oldPassword: null,
                    newPassword: null,
                    newRepeated: null
                }
            }
        },
        methods: {
            fetch() {
                return User.get().then(
                    response => {
                        if (response.data.ok) {
                            this.user = response.data.user;
                            this.form = response.data.form;
                        }
                    }
                )
            },
            saveSettings() {
                User.save(this.user).then(
                    response => {
                        if (response.data.ok) {
                            this.$bus.emit(BusEvents.ALERT_SUCCESS, 'Настройки сохранены');
                            this.$bus.emit(BusEvents.REFRESH_SIDEBAR);
                            Session.checker.checkNow();
                        }
                    }
                )
            },
            changePassword() {
                this.passwordChangeFormErrors = {};
                if (this.passwordChangeForm.newPassword != this.passwordChangeForm.newRepeated) {
                    this.passwordChangeFormErrors.newRepeated = 'Пароли не совпадают';
                    return
                }
                User.changePassword(this.passwordChangeForm.oldPassword, this.passwordChangeForm.newPassword).then(
                    response => {
                        if (response.data.ok) {
                            this.passwordChangeFormErrors = {};
                            this.$bus.emit(BusEvents.ALERT_SUCCESS, 'Пароль изменён');
                            this.passwordChangeForm = {
                                oldPassword: '',
                                newPassword: '',
                                newRepeated: ''
                            };
                            setTimeout(() => this.passwordChanged = false, 3000);
                        } else {
                            this.passwordChangeFormErrors = response.data['form-errors'];
                        }
                    }
                )
            }
        }
    }
</script>


<style lang="scss" rel="stylesheet/scss">
    .profile-page {
        .panel { position: relative }
        .alert { position: absolute; top: 10px; right: 10px }
    }
</style>