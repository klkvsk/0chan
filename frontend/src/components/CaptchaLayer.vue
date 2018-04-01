<template>
    <div class="captcha-form-wrapper" v-if="visible">
        <div class="captcha-form">
            <span class="captcha-close-btn" @click="close()"><i class="fa fa-times"></i></span>
            <b>Введите капчу</b>
            <div class="separator"></div>
            <img v-show="image != null" :src="image" width="200" height="60" class="pointer" @click="reload()" />
            <form @submit.prevent="send()">
                <input type="text" v-model="answer" ref="answer">
                <button type="submit" class="btn btn-lg btn-info">
                    <i class="fa fa-arrow-circle-right"></i>
                </button>
            </form>
        </div>
    </div>
</template>

<script>
    import BusEvents from '../app/BusEvents';
    import Captcha from '../services/Captcha';

    export default {
        data() {
            return {
                visible: false,
                image: null,
                answer: '',
            }
        },
        created() {
            this.$bus.on(BusEvents.REQUEST_CAPTCHA, this.ask);
        },
        methods: {
            close() {
                this.visible = false;
                this.image = null;
                this.$bus.emit(BusEvents.SET_BLUR, false);
            },
            ask(callback) {
                this.answer = '';
                this.visible = true;
                this.callback = callback || null;
                Captcha.getImage().then((image) => { this.image = image });
                this.$bus.emit(BusEvents.SET_BLUR, true);
                this.$nextTick(() => {
                    this.$refs.answer.focus();
                });
            },
            reload() {
                this.ask(this.callback);
            },
            send() {
                Captcha.answer(this.answer).then((isValid) => {
                    if (isValid) {
                        if (this.callback) {
                            this.$bus.emit(BusEvents.SET_BLUR, false);
                            this.callback(Captcha.captcha);
                        }
                        this.close();
                    } else {
                        this.reload();
                    }
                })
            }
        }
    }
</script>

<style>
    .captcha-form-wrapper {
        position: fixed;
        background: rgba(255,255,255,0.6);
        z-index: 10;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
    }

    .captcha-form {
        background: rgba(255,255,255,0.8);
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        margin-left: auto;
        margin-right: auto;
        margin-top: -70px;
        height: 140px;
        text-align: center;
        width: 200px;
    }

    .captcha-form .separator {
        margin: 2px 0;
    }

    .captcha-form input {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 140px;
        line-height: 1.6em;
        height: 50px;
        font-size: 1.6em;
        padding: 4px;
        border: 1px solid #dddddd;
        border-radius: 3px;
        text-align: center;
    }

    .captcha-form input:focus {
        box-shadow: none;
    }

    .captcha-form button[type=submit] {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 50px;
        height: 50px;
    }

    .captcha-form .captcha-close-btn {
        position: absolute;
        right: 0;
        cursor: pointer;
    }
</style>
