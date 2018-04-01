import Api from './Api'
import BusEvents from '../app/BusEvents'

function refreshSidebarAfter(request) {
    return request.then(
        response => {
            BusEvents.$bus.emit(BusEvents.REFRESH_SIDEBAR);
            return response;
        }
    )
};

export default {
    list() {
        return Api.get('management/list');
    },
    moderators(boardDir) {
        return Api.get('management/moderators', { params: { board: boardDir }});
    },
    addModerator(boardDir, login) {
        return Api.get('management/addModerator', { params: { board: boardDir, user: login }})
    },
    removeModerator(boardDir, login) {
        return Api.get('management/removeModerator', { params: { board: boardDir, user: login }})
    },
    board(boardDir, form) {
        const params = { params: { board: boardDir }};
        if (form) {
            return refreshSidebarAfter(Api.post('management/board', form, params));
        } else {
            return Api.get('management/board', params);
        }
    },
    deleteBoard(boardDir) {
        return refreshSidebarAfter(Api.get('management/deleteBoard', { params: { board: boardDir }}));
    },
    changeOwner(boardDir, newOwnerLogin) {
        return refreshSidebarAfter(Api.get('management/changeOwner', { params: { board: boardDir, newOwner: newOwnerLogin }}));
    }
}