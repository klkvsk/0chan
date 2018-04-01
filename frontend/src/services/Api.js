import {Axios} from 'axios'
import BusEvents from '../app/BusEvents'
import Router from '../app/Router'
import $ from 'jquery';

import Session from './Session';
import UI from '../app/UI';

let API_URL;
if (process.env.NODE_ENV == "production") {
    API_URL = document.location.protocol + '//' + document.location.hostname + '/api/';
} else {
    API_URL = '//localhost/api/';
}

const axios = new Axios({
    baseURL: API_URL
});
/*
let errorHappened = false;
const request = axios.request;
axios.request = (config) => {
    if (errorHappened) return new Promise(()=>{});
    let promise = request.call(axios, config);
    let catchOverride = false;
    promise = promise.catch((error) => {
        if (catchOverride) throw e;

        const requestedUrl = error.config.url + (error.config.params ? '?' + $.param(error.config.params) : '');
        const data         = error.response ? error.response.data   : {};
        UI.bsod(
            error,
            '=== REQUEST: ===',
            error.config.method.toUpperCase() + ' ' + requestedUrl + "\n"
            + (error.config.body ? JSON.stringify(error.config.body, null, 4) : ''),
            '=== REPONSE: ===',
            error.response ? (error.response.status + ' ' + error.response.statusText) : error.message,
            data
        );
        errorHappened = true;
    });

    const originalCatch = promise.catch;
    promise.catch = (handler) => {
        catchOverride = true;
        debugger;
        return originalCatch.call(promise, handler);
    };
    return promise;
};
*/
axios.interceptors.request.use(
    config => {
        const session = Session.id;
        if (session) {
            config.params = { ...config.params, session };
        }
        return config;
    }
);

axios.interceptors.response.use(
    response => {
        if (response.headers.hasOwnProperty('x-session')) {
            Session.id = response.headers['x-session'];
        }

        return response;
    },
    error => {
        const status = error.response ? error.response.status : -1;
        const data   = error.response ? error.response.data   : {};

        if (status === 403 && data.details && data.details.require === 'auth') {
            return Session.get().then(() => {
                Router.push({name: 'login'})
            });

        } else if (status === 403 && data.details && data.details.require === 'captcha') {
            return new Promise(resolve => {
                BusEvents.$bus.emit(
                    BusEvents.REQUEST_CAPTCHA,
                    (captcha) => {
                        const repeat = error.config;
                        repeat.params = { ...repeat.params, captcha };
                        resolve(axios.request(repeat));
                    }
                )
            });

        } else if (status === 404 || status === 403) {
            //Router.replace({name: 'notFound'});
            throw error;
        } if (status >= 520) {
            // cf errors
            throw error;
        }

        if (!error.config.silentFail) {
            const requestedUrl = error.config.url + (error.config.params ? '?' + $.param(error.config.params) : '');
            UI.bsod(error,
                '=== REQUEST: ===',
                error.config.method.toUpperCase() + ' ' + requestedUrl + "\n"
                + (error.config.body ? JSON.stringify(error.config.body, null, 4) : ''),
                '=== RESPONSE: ===',
                error.response ? (error.response.status + ' ' + error.response.statusText) : error.message,
                data
            );
            throw error;
        }
    }
);

export default axios;