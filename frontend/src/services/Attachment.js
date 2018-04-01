import Api from './Api'

export default {
    upload(file, progressCallback) {
        const formData = new FormData();
        formData.append('file', file);
        return Api.post('attachment/upload', formData, { onUploadProgress: progressCallback });
    },
    embed(url) {
        return Api.post('attachment/embed', {}, { params: { url: url } });
    },
    cancel(id, token) {
        return Api.get('attachment/cancel', { params: { attachment: id, token: token } });
    },
    markNsfw(id, token, isNsfw) {
        return Api.get('attachment/markNsfw', { params: { attachment: id, token: token, isNsfw: isNsfw }});
    }
}