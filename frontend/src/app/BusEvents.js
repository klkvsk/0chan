import Vue from 'vue';
import VueBus from 'vue-bus';
Vue.use(VueBus);
const $bus = Vue.prototype.$bus;

export default {
    $bus, // shortcut
    REFRESH_CONTENT:    'refreshContent',
    REFRESH_CONTENT_DONE:'refreshContentDone',
    REFRESH_SIDEBAR:    'refreshSidebar',
    TOGGLE_SIDEBAR:     'toggleSidebar',
    SET_BLUR:           'blur',
    REQUEST_CAPTCHA:    'requestCaptcha',
    REPLY_TO_OP_POST:   'replyToOpPost',
    SHOW_MODAL:         'showModal',
    ALERT_SUCCESS:      'alertSuccess',
    ALERT_INFO:         'alertInfo',
    ALERT_ERROR:        'alertError',
    NEW_MESSAGE:        'newMessage',
    HIGHLIGHT_POST:     'highlightPost',
}