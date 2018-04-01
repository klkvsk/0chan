<template>
    <div>
        <Headline>
            <span slot="title">
                <span v-if="formType === 'login'">Вход</span>
                <span v-if="formType === 'register'">Регистрация</span>
            </span>
        </Headline>
        <div class="login-form-wrapper">
            <div class="login-form">
                <div v-if="formType == 'login'">
                    <form @submit.prevent="doLogin()">
                        <input type="text" v-model="login.login" placeholder="Логин">
                        <br/>
                        <input type="password" v-model="login.password" placeholder="Пароль">
                        <br/>
                        <ul>
                            <li style="color: #cc6666" v-for="error in loginErrors" v-if="error != null">{{error}}.</li>
                        </ul>
                        <br/>
                        <button type="submit" class="btn btn-info pull-left">
                            <i class="fa fa-sign-in"></i>
                            Войти
                        </button>
                    </form>
                    <button class="btn btn-link pull-right" @click="formType = 'register'">создать аккаунт</button>
                    <br/>
                </div>

                <div v-else-if="formType == 'register'">
                    <form @submit.prevent="doRegister">
                        <input type="text" :class="{'form-error': registerErrors.login }" v-model="register.login" placeholder="Логин">
                        <br/>
                        <input type="password" :class="{'form-error': registerErrors.password }" v-model="register.password" placeholder="Пароль">
                        <br/>
                        <input type="password" :class="{'form-error': registerErrors.password2 }" v-model="register.password2" placeholder="Пароль (повторно)">
                        <!--<br/>-->
                        <!--<input type="email" ng-class="{'form-error': registerErrors.email }" v-model="register.email" placeholder="Е-mail (не обязательно)">-->
                        <!--<br/>-->
                        <br/>
                        <ul>
                            <li style="color: #cc6666" v-for="error in registerErrors" v-if="error != null">{{error}}.</li>
                        </ul>
                        <ul>
                            <li>
                                Логин используется для входа и не отображается при общении в разделах.
                            </li>
                            <li>
                                При назначении модератором и при владении доской, логин будет виден другим модераторам.
                            </li>
                            <li>
                                В любом случае, стоит придумать новый логин, с которым не связаны другие аккаунты в интернете.
                            </li>
                            <!--<li>-->
                            <!--Е-mail нужен для восстановления доступа к аккаунту.-->
                            <!--<br/>-->
                            <!--Не хранится в открытом виде, нигде не отображается.-->
                            <!--<br/>-->
                            <!--Указывать не обязательно.-->
                            <!--</li>-->
                            <li>
                                Пароль должен быть не короче 8 символов.
                            </li>
                            <li>
                                Из-за отсутствия привязки к почте или иным внешним идентификаторам, возможности восстановления аккаунта нет.
                                Введенные данные следует сохранить.
                            </li>
                        </ul>
                        <br/>
                        <button type="submit" class="btn btn-info pull-left">
                            <i class="fa fa-sign-in"></i>
                            Создать аккаунт
                        </button>
                    </form>

                    <button class="btn btn-link pull-right" @click="formType = 'login'">войти</button>
                </div>

            </div>

        </div>
    </div>

</template>

<script>
    import BusEvents from '../app/BusEvents'
    import User from '../services/User';
    import Session from '../services/Session';
    import Headline from './Headline.vue';

    export default {
        props: [ 'redir' ],
        components: {
            Headline
        },
        data () {
            return {
                formType: 'login',
                login: {
                    login: '', password: ''
                },
                loginErrors: {},

                register: {
                    login: '', password: '', password2: '', email: ''
                },
                registerErrors: {},
            }
        },
        created() {
            if (Session.auth) {
                this.redirectOnAuth();
            }
        },
        methods: {
            doLogin() {
                this.loginErrors = {};

                User.login(this.login.login, this.login.password)
                    .then((response) => {
                        let data = response.data || {};
                        if (data['form-errors']) {
                            this.loginErrors = data['form-errors'];

                        } else if (data.ok) {
                            this.redirectOnAuth();

                        } else {
                            this.loginErrors = {
                                user: 'Введённый аккаунт не найден или пароль не подходит'
                            }
                        }
                    })
            },
            doRegister() {
                if (this.register.password
                    && this.register.password != this.register.password2
                ) {
                    this.registerErrors.password2 = 'Пароли не совпадают';
                    return;
                } else {
                    this.registerErrors.password2 = null;
                }

                User.register(this.register.login, this.register.password, this.register.email)
                    .then((response) => {
                        let data = response.data;
                        if (data['form-errors']) {
                            this.registerErrors = data['form-errors'];
                        } else if (data.ok) {
                            this.redirectOnAuth();
                        }
                    })
            },
            redirectOnAuth() {
                let route;
                if (this.redir) {
                    try {
                        route = JSON.parse(atob(this.redir));
                    } catch (e) {
                        route = null;
                    }
                }
                if (!route) {
                    route = { name: 'home' };
                }
                setTimeout(() => this.$router.push(route), 100);
            }
        }
    }
</script>

<style>
    .login-form-wrapper {
        text-align: center;
        margin-top: 50px;
        height: 100%;
    }
    .login-form {
        vertical-align: middle;
        text-align: left;
    }

    .login-form ul li {
        color: #777;
        margin: 6px 0;
        line-height: 1.1em;
    }

    .login-form .form-error {
        border: 1px solid red;
        box-shadow: 0 0 3px #ff3333;
    }

    .login-form .separator {
        margin: 2px 10px;
    }

    .login-form input {
        width: 100%;
        line-height: 1.2em;
        height: auto;
        font-size: 1.2em;
        display: inline-block;
        margin: 4px 0;
        padding: 10px;
        border: 1px solid #dddddd;
        border-radius: 3px;
    }

    .login-form input:focus {
        box-shadow: none;
    }

    .login-form > div {
        max-width: 400px;
        margin: auto;
        position: relative;
        height: 100px;
    }

</style>
