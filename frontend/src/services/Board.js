import Api from './Api'
import BusEvents from '../app/BusEvents'

export default {
    getBoard(boardDir, page, sort) {
        return this.get({ dir: boardDir, page, sort })
    },
    getFavourite(page, sort) {
        return this.get({ favourite: true, page, sort })
    },
    getWatched(page, sort) {
        return this.get({ watched: true, page, sort })
    },
    get(params) {
        return Api.get('board', { params });
    },
    list(search) {
        return Api.get('board/list', { params: { search } });
    },
    favourite(boardDir, isFavourite) {
        return Api.get('board/favourite', { params: { board: boardDir, isFavourite } }).then(
            response => {
                BusEvents.$bus.emit(BusEvents.REFRESH_SIDEBAR);``
                return response;
            }
        );
    }
}