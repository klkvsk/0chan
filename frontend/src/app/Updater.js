import $ from 'jquery';

export default class Updater {
    callback = null;
    interval = 60000;

    nextTimeout = null;
    lastCheck = null;

    constructor (callback, interval) {
        this.callback = callback;
        this.interval = interval || this.interval;
        //
        // $(window).blur(() => {
        //     this.cancelNext();
        // });
        // $(window).focus(() => {
        //     const diff = new Date() - this.lastCheck;
        //     if (diff < this.interval) {
        //         this.checkLater(this.interval - diff);
        //     } else {
        //         this.checkNow()
        //     }
        // });
    }

    checkNow(throwError) {
        return Promise.resolve(this.callback())
            .then(() => {
                this.lastCheck = new Date();
                this.checkLater();
                return null;
            })
            .catch((e) => {
                if (throwError) {
                    this.cancelNext();
                    throw e;
                } else {
                    console.error(e)
                }
            });
    }

    checkLater(waitMs) {
        if (!waitMs) {
            waitMs = this.interval;
            if (typeof document.hasFocus === 'function' && !document.hasFocus()) {
                waitMs *= 2;
            }
        }
        this.cancelNext();
        return new Promise(resolve => {
            this.nextTimeout = setTimeout(() => resolve(this.checkNow()), waitMs);
        })
    }

    cancelNext() {
        if (this.nextTimeout) {
            clearTimeout(this.nextTimeout)
        }
    }
}