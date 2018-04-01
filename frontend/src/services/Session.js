import Api from './Api'
import Updater from '../app/Updater';
import BusEvents from '../app/BusEvents';

const SESSION_KEY = 'sessionId';
const storage = window.localStorage;

export default {

    get checker() {
        return this._checker = this._checker || new Updater(() => this.get(), 60000)
    },

    get id() {
        return storage.getItem(SESSION_KEY)
    },
    set id(id) {
        id ? storage.setItem(SESSION_KEY, id) : storage.removeItem(SESSION_KEY);
    },

    auth: false,
    isGlobalAdmin: false,
    isGlobalMod: false,
    version: null,
    messages: null,
    settings: {
        treeView: false,
    },

    start() {
        return this.checker.checkNow(true);
    },

    get() {
        return Api.get('session').then(
            response => {
                this.auth = response.data.auth;
                this.isGlobalAdmin = response.data.isGlobalAdmin;
                this.isGlobalMod = response.data.isGlobalMod;
                this.messages = response.data.messages;
                this.settings = response.data.settings || {};

                if (response.data.version) {
                    if (this.version && this.version != response.data.version) {
                        BusEvents.$bus.emit(BusEvents.ALERT_INFO, "Версия клиента обновлена.\nСледует перезагрузить страницу", 10000)
                    }
                    if (!this.version) {
                        this.version = response.data.version;
                    }
                }
                return response;
            }
        );
    },

}
