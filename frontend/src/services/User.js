import Api from './Api'
import Session from './Session'
import BusEvents from '../app/BusEvents'

export default {
    login(login, password) {
        return Api.post('user/login', { login, password })
            .then(response => {
                if (response.data.ok) {
                    Session.get();
                    BusEvents.$bus.emit(BusEvents.REFRESH_SIDEBAR);
                }

                return response;
            });
    },
    logout() {
        return Api.get('user/logout')
            .then(response => {
                if (response.data.ok) {
                    Session.get();
                    BusEvents.$bus.emit(BusEvents.REFRESH_SIDEBAR);
                }
                return response;
            });
    },
    register(login, password) {
        return Api.post('user/register', { login, password }).then(
            response => {
                if (response.data.ok) {
                    Session.get();
                    BusEvents.$bus.emit(BusEvents.REFRESH_SIDEBAR);
                }
                return response;
            }
        );
    },
    changePassword(oldPassword, newPassword) {
        return Api.post('user/changePassword', { oldPassword, newPassword });
    },
    get() {
        return Api.get('user');
    },
    save(settings) {
        return Api.post('user', settings);
    }
}