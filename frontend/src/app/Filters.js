import moment from 'moment';
moment.locale('ru');

function importDate(value) {
    if (!value) {
        return null;
    }
    let date = new Date(value);
    if (date.getFullYear() == 1970) date = new Date(value*1000);
    return date;
}

export default {
    timestamp: (value) => {
        const date = importDate(value);
        if (!date) return '-';
        return moment(date).format('YYYY-MM-DD HH:mm:ss');
    },
    timespan: (value) => {
        return moment().subtract(value, 'seconds').fromNow(true);
    },
    timeago: (value) => {
        const date = importDate(value);
        return moment(date).fromNow();
    },
    shorten: (value, symbols) => {
        value = value.toString();
        if (value.length > symbols) {
            value = value.substr(0, symbols) + '…'
        }
        return value;
    }
}