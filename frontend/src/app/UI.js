import $ from 'jquery'
import BusEvents from './BusEvents'
import Raven from 'raven-js'

export default {
    bsod(...messages) {
        if (process.env.NODE_ENV === 'production') {
            let [ sentryError, ...sentryBreadcrumbs ] = messages;
            for (let sentryBreadcrumb of sentryBreadcrumbs) {
                Raven.captureBreadcrumb({ message: sentryBreadcrumb });
            }
            const hiddenErrors = [ `Network Error`, `'tgt'` ];
            let isReportingError = true;
            for (let hiddenError of hiddenErrors) {
                if (sentryError.toString().indexOf(hiddenError) !== -1) {
                    isReportingError = false;
                }
            }
            if (isReportingError) {
                Raven.captureException(sentryError);
            }
        }

        let errorText = messages
            .map((e) => {
                if (e.stack && typeof e.stack.toString === 'function')
                    return e.stack.toString();

                if (typeof e === 'object')
                    return JSON.stringify(e, null, 4);

                if (e && typeof e === 'object' && typeof e.toString === 'function')
                    return e.toString();

                return e;
            })
            .join("\n");

        if (errorText) {
            if (window.app && window.app.$bus) {
                window.app.$bus.emit(BusEvents.ALERT_ERROR, "0ops, divided by zero!\n\n" + errorText, 10000);
            } else {
                window.document.write('<style>pre { font-family: Consolas, monospace; white-space: pre-wrap; background: #2C333D } </style>');
                window.document.write('<pre style="color: #eeeeee; font-weight: bold">0ops, divided by zero!</pre>');
                window.document.write('<pre style="color: #1abc9c">' + errorText + '</pre>');
                window.document.write('<pre style="color: #eeeeee; font-weight: bold">refresh page to try again...</pre>');
                window.document.body.style.backgroundColor = '#2C333D';
            }
        }
    },

    scrollTo(el, offset) {
        const $el = $(el);
        if (!$el.length) return;
        offset = (offset || 0);
        if ($('.headmenu').css('position') === 'fixed') offset -= 40;
        return new Promise(
            resolve => {
                $('html, body').animate({ scrollTop: $(el).offset().top + offset }, 200, resolve)
            });
    },

    setupPostPopup(vue, callback) {
        let popupTimeout = null;
        let popupPostId = null;
        $(vue.$el).on('click mouseenter mouseleave', 'a', (e) => {
            const $a = $(e.target);
            const postId = $a.data('post');
            if (!postId) return;

            let post = $a.data('postObject');

            if (e.type == 'mouseenter') {
                clearTimeout(popupTimeout);
                if (popupPostId != postId) {
                    callback(null);
                    popupTimeout = setTimeout(() => {
                        popupPostId = postId;
                        Promise.resolve(post || vue.loadPost(postId)).then(
                            post => {
                                if (post) {
                                    $a.data('postObject', post);
                                    if (popupPostId != post.id) {
                                        return; // we're late, another post was requested to pop up
                                    }
                                    callback(post);
                                    vue.$nextTick(() => {
                                        const $popup = $('.post-popup', vue.$el);
                                        const position = $a.position();
                                        position.top += $a.height();
                                        $popup.css(position);
                                        $popup.on('mouseleave mouseenter', (e) => $a.trigger(e.type));
                                    })
                                }
                            }
                        );
                    }, 100)
                }
            }
            if (e.type == 'mouseleave') {
                clearTimeout(popupTimeout);
                if (popupPostId ) {
                    popupTimeout = setTimeout(() => {
                        callback(null);
                        popupPostId = null;
                    }, 100)
                }
            }
            if (e.type == 'click') {
                if (post) {
                    vue.$router.push({
                        name: 'thread', params: { dir: post.boardDir, threadId: post.threadId },
                        hash: post.isOpPost ? '' : '#' + post.id
                    });
                    vue.$bus.emit(BusEvents.HIGHLIGHT_POST, post.id);
                }
            }

            e.stopPropagation();
            return false;
        });
    }
}