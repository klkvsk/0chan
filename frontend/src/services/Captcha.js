import {Vue} from "vue"
import Api from './Api'

export default {
    captcha: null,

    getImage() {
        return Api.get('captcha')
            .then((response) => {
                this.captcha = response.data.captcha;
                this.valid = false;
                return response.data.image;
            });
    },

    answer(answerText) {
        return Api.get('captcha', { params: { captcha: this.captcha, answer: answerText } })
            .then((response) => {
                return response.data.ok || false;
            });
    }

}