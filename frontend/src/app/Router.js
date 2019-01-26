import Router from 'vue-router'

import NotFound from '../components/NotFound.vue'
import Login from '../components/Login.vue'
import Board from '../components/Board.vue'
import Thread from '../components/Thread.vue'
import Profile from '../components/Profile.vue'

import ImLayout from '../components/im/Layout.vue'
import ImIndex from '../components/im/Index.vue'
import ImInit from '../components/im/Init.vue'
import ImStart from '../components/im/Start.vue'
import ImDialog from '../components/im/Dialog.vue'

import AdminDashboard from '../components/admin/Dashboard.vue'
import AdminBoardList from '../components/admin/BoardList.vue'
import AdminBoardEdit from '../components/admin/BoardEdit.vue'
import AdminModerators from '../components/admin/Moderators.vue'
import AdminGlobals from '../components/admin/Globals.vue'

import ModeratorDashboard from '../components/mod/Dashboard.vue'
import ModeratorLogs from '../components/mod/Logs.vue'
import ModeratorReports from '../components/mod/Reports.vue'
import ModeratorFeed from '../components/mod/Feed.vue'
import ModeratorBans from '../components/mod/Bans.vue'
import ModeratorBoardStats from '../components/mod/BoardStats.vue'

import Loop from '../components/Loop.vue'

import Session from '../services/Session'
import BusEvents from '../app/BusEvents'

const isEmojiUrlSupported = window
    && window.navigator.userAgent.match(/Chrom|Edge|Firefox/)
    && window.navigator.userAgent.match(/Windows NT 10|OS X 10_1[0-9]|Android [7-9]/);

const emojination = {
    '$in': '🚪',
    '$me': '👤',
    '$admin': '👑',
    '$mod': '👁',
    '$fav': '💚',
    '$watched': '🌟',
    '$im': '📧',
    '$404': '😢',
    '$home': '🏠',
};
const emojinate = (route) => {
    let path = route.path.replace(/^\//, '').split('/');
    if (emojination.hasOwnProperty(path[0])) {
        let emoji = emojination[path[0]];
        let nonEmoji = '/' + path.join('/');
        let encEmoji = '/' + encodeURIComponent(emoji) + '/' + path.slice(1).join('/');
        let chrEmoji = '/' + emoji + '/' + path.slice(1).join('/');
        if (isEmojiUrlSupported) {
            route.alias = [ nonEmoji, encEmoji ];
            route.path =  chrEmoji;
        } else {
            route.alias = [ chrEmoji, encEmoji ];
            route.path =  nonEmoji;

        }
    }
    return route;
};

let routes = [
    {
        path: '/$in/:redir?',
        name: 'login',
        props: true,
        component: Login
    },{
        path: '/$me',
        name: 'account',
        component: Profile,
        meta: { auth: true },
    },{
        path: '/$admin',
        name: 'admin',
        component: AdminDashboard,
        props: true,
        meta: { auth: true },
        redirect: to => ({ name: 'admin_boards', params: to.params }),

        children: [
            {
                path: '$list',
                name: 'admin_boards',
                component: AdminBoardList,
                meta: { auth: true },
            },{
                path: '$globals',
                name: 'admin_globals',
                component: AdminGlobals,
                meta: { auth: true },
            },{
                path: '$new',
                name: 'admin_newBoard',
                component: AdminBoardEdit,
                meta: { auth: true },
            },{
                path: ':dir',
                props: true,
                name: 'admin_editBoard',
                component: AdminBoardEdit,
                meta: { auth: true },
            },{
                path: '$mods/:dir',
                props: true,
                name: 'admin_mods',
                component: AdminModerators,
                meta: { auth: true },
            }
        ]
    },{
        path: '/$mod/:dir?',
        name: 'mod',
        component: ModeratorDashboard,
        props: true,
        meta: { auth: true },
        redirect: to => ({ name: 'mod_logs', params: to.params }),
        children: [
            {
                path: 'logs',
                props: true,
                name: 'mod_logs',
                component: ModeratorLogs
            },{
                path: 'reports',
                props: true,
                name: 'mod_reports',
                component: ModeratorReports
            },{
                path: 'feed',
                props: true,
                name: 'mod_feed',
                component: ModeratorFeed
            },{
                path: 'bans/:banId?',
                props: true,
                name: 'mod_bans',
                component: ModeratorBans
            },{
                path: 'stats/',
                props: true,
                name: 'mod_stats',
                component: ModeratorBoardStats
            }
        ]
    },{
        path: '/',
        alias: '/$home',
        name: 'home',
        component: Board,
    },{
        path: '/$fav',
        name: 'favourite',
        component: Board,
        meta: { auth: true },
    },{
        path: '/$watched',
        name: 'watched',
        component: Board,
        meta: { auth: true },
    },{
        path: '/$im/$new',
        props: true,
        name: 'im_init',
        component: ImInit,
        meta: { auth: true },
    },{
        path: '/:address(0x[a-f0-9]+)',
        name: 'im_address',
        redirect: to => ({ name: 'im_start', params: { toAddress: to.params.address } }),
    },{
        path: '/$im',
        component: ImLayout,
        meta: { auth: true },
        children: [
            {
                path: '',
                props: true,
                name: 'im',
                component: ImIndex,
                meta: { auth: true },
            },{
                path: ':toAddress/:asAddress',
                props: true,
                name: 'im_dialog',
                component: ImDialog,
                meta: { auth: true },
            },{
                path: ':toAddress',
                props: true,
                name: 'im_start',
                component: ImStart,
                meta: { auth: true },
            }
        ]
    },{
        path: '/:dir([a-z0-9]+)/:threadId(\\d+)',
        props: true,
        name: 'thread',
        component: Thread,
    },{
        path: '/:dir([a-z0-9]+)',
        name: 'board',
        component: Board,
    },{
        path: '/:dir(_[a-z0-9]+)',
        name: 'old20board_redirect',
        redirect: to => ({ name: 'board', params: { dir: to.params.dir.substr(1) } }),
    },{
        path: '*',
        name: 'notFound',
        component: NotFound,
    },
];

routes = routes.map(emojinate);

const stateCache = {
    historyMax: 20,
    states: {},
    makeId: route => JSON.stringify({
        name:   route.name,
        params: route.params,
        query:  route.query,
    }),
    set(route, state) {
        state._date = new Date();
        const id = this.makeId(route);
        //console.log('set state ' + id + ': ', state);
        this.states[id] = state;

        // push out old states
        const history = [];
        for (let [ id, state ] of Object.entries(this.states)) {
            history.push(state._date);
        }
        if (history.length > this.historyMax) {
            history.sort().reverse();
            const maxDate = history[ this.historyMax - 1 ];
            for (let [ id, state ] of Object.entries(this.states)) {
                if (state._date < maxDate) {
                    //console.log('cleanup state' + id);
                    delete this.states[id];
                }
            }
        }
    },
    get(route) {
        const id = this.makeId(route);
        //console.log('get state ' + id);
        return this.states[id] || null;
    },
    drop(route) {
        const id = this.makeId(route);
        //console.log('drop state ' + id);
        delete this.states[id];
    },
};

const router = new Router({
    mode: 'history',
    routes: routes,
    history: true,
    scrollBehavior (to, from, savedPosition) {
        // use savedPosition to detect if navigation caused by popstate
        // if its not we should clean up cached data -- so controller would fetch fresh
        if (savedPosition === null) {
            stateCache.drop(to);
        }
        return null; // ignore internal vue-router behavior
    }
});

router.saveStateCache = (vm) => {
    stateCache.set(vm.$route, {
        data: Object.assign({}, vm.$data),
        scroll: {
            x: window.pageXOffset,
            y: window.pageYOffset
        }
    });
};
router.restoreStateCache = (vm) => {
    const state = stateCache.get(vm.$route);
    if (!state) {
        return false;
    }
    //console.log('restoring state', state);
    Object.assign(vm, state.data);
    BusEvents.$bus.once(BusEvents.REFRESH_CONTENT_DONE, () => {
        vm.$nextTick(() => {
            // TODO: hook to correct event
            setTimeout(() => window.scrollTo(state.scroll.x, state.scroll.y), 100);
        });
    });
    stateCache.drop(vm.$route);
    return true;
};

router.beforeEach((to, from, next) => {
    if (to.meta && to.meta.auth && !Session.auth) {
        const redir = btoa(JSON.stringify({ name: to.name, params: to.params, query: to.query }));
        next({name: 'login', params: { redir } })
    } else {
        next()
    }
});

router.sameRoute = (a, b) => {
    return (
        JSON.stringify([a.name, a.params || {}, a.query || {}])
        ===
        JSON.stringify([b.name, b.params || {}, b.query || {}])
    );
};

export default router;
