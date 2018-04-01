const settings = {
    store: {
        hiddenPosts: [],
        threadList: true,
    },
    setHiddenPost(postId, isHidden) {
        if (isHidden) {
            this.store.hiddenPosts.push(postId);
        } else {
            this.store.hiddenPosts = this.store.hiddenPosts.filter(id => id != postId);
        }
        save();
    },
    isHiddenPost(postId) {
        return this.store.hiddenPosts.indexOf(postId) !== -1;
    },
    setThreadListVisibility(isVisible) {
        this.store.threadList = isVisible;
        save();
    },
    isThreadListVisible() {
        return this.store.threadList;
    }

};

function save() {
    window.localStorage.setItem('userSettings', JSON.stringify(settings.store));
}

function load() {
    let stored = window.localStorage.getItem('userSettings');
    if (stored) {
        settings.store = JSON.parse(stored);
    }
}

load();
export default settings