import Api from './Api'

export default {
    list() {
        return Api.get('globals/list');
    },
    moderators(boardDir) {
        return Api.get('management/moderators', { params: { board: boardDir }});
    },
    add(userLogin, isAdmin) {
        return Api.get('globals/add', { params: { user: userLogin, isAdmin: !!isAdmin }})
    },
    remove(userLogin) {
        return Api.get('globals/remove', { params: { user: userLogin }})
    },
}
