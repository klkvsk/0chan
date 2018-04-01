import Api from './Api'

export default {
    reply(parent, form) {
        return Api.post('thread/reply', form, { params: { parent } });
    },
    create(dir, form) {
        return Api.post('thread/create', form, { params: { board: dir } });
    },
    get(id, lastPostId) {
        return Api.get('thread', { params: { thread: id, after: lastPostId }})
    },
    getByPost(postId) {
        return Api.get('thread', { params: { post: postId }})
    },
    watch(threadId, isWatched) {
        return Api.get('thread/watch', { params: { thread: threadId, isWatched } });
    },
}