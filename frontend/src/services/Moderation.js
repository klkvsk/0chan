import Api from "./Api";

export default {
    get(boardDir) {
        return Api.get('moderation', { params: { board: boardDir } });
    },
    message(boardDir, message) {
        return Api.post('moderation/message', { message }, { params: { board: boardDir } });
    },
    deletePost(postId) {
        return Api.get('moderation/deletePost', { params: { post: postId } });
    },
    restorePost(postId) {
        return Api.get('moderation/restorePost', { params: { post: postId } });
    },
    deleteAttachment(attachmentId) {
        return Api.get('moderation/deleteAttachment', { params: { attachment: attachmentId } });
    },
    restoreAttachment(attachmentId) {
        return Api.get('moderation/restoreAttachment', { params: { attachment: attachmentId } });
    },
    markNsfwAttachment(attachmentId, isNsfw) {
        return Api.get('moderation/markNsfwAttachment', { params: { attachment: attachmentId, isNsfw: isNsfw }});
    },

    logs(boardDir, page) {
        return Api.get('moderation/logs', { params: { board: boardDir, page: page }});
    },
    feed(boardDir, page) {
        return Api.get('moderation/feed', { params: { board: boardDir, page: page }});
    },
    stats(boardDir) {
        return Api.get('moderation/stats', { params: { board: boardDir }});
    },
    reports(boardDir, page) {
        return Api.get('moderation/reports', { params: { board: boardDir, page: page }});
    },
    bans(boardDir, page) {
        return Api.get('moderation/bans', { params: { board: boardDir, page: page }});
    },
    addBan(postId, reason, time) {
        return Api.post('moderation/addBan', { reason, time }, { params: { post: postId }});
    },
    removeBan(banId) {
        return Api.get('moderation/removeBan', { params: { ban: banId }});
    },
    banInfo(banId) {
        return Api.get('moderation/banInfo', { params: { ban: banId }});
    },
    appealBan(banId, appeal) {
        return Api.post('moderation/appealBan', { appeal }, { params: { ban: banId }});
    },
    reportPost(postId, reason) {
        return Api.post('moderation/reportPost', { reason }, { params: { post: postId }});
    },
    markApproved(postId, isApproved) {
        return Api.get('moderation/markApproved', { params: { post: postId, isApproved: isApproved }});
    },

    lockThread(threadId, isLock) {
        return Api.get('moderation/lockThread', { params: { thread: threadId, isLock: isLock }});
    },
    pinThread(threadId, isPin) {
        return Api.get('moderation/pinThread', { params: { thread: threadId, isPin: isPin }});
    },
    unpurgeThread(threadId) {
        return Api.get('moderation/unpurgeThread', { params: { thread: threadId }});
    }
}