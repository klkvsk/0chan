import Api from './Api'

export default {
    get(postId) {
        return Api.get('post', { params: { post: postId }});
    },
    upvote(postId) {
        return Api.get('post/rate', { params: { post: postId, isLike: true }});
    },
    downvote(postId) {
        return Api.get('post/rate', { params: { post: postId, isLike: false }});
    }
}