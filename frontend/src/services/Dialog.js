import Api from './Api'

export default {
    list() {
        return Api.get('dialog/list');
    },
    start(to) {
        return Api.get('dialog/start', { params: { to: to }});
    },
    get(asAddress, toAddress, after, before) {
        return Api.get('dialog', { params: { as: asAddress, to: toAddress, after: after, before: before }});
    },
    send(asAddress, toAddress, message) {
        return Api.post('dialog/send', { text: message }, { params: { as: asAddress, to: toAddress }});
    },
    addIdentity(name) {
        return Api.post('dialog/addIdentity', { name });
    },
    deleteIdentity(address) {
        return Api.get('dialog/deleteIdentity', { params: { address: address } });
    },
    deleteDialog(dialogId) {
        return Api.get('dialog/deleteDialog', { params: { dialog: dialogId } });
    },
    identities() {
        return Api.get('dialog/identities');
    }
}