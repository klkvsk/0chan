'use strict';

const dns           = require('dns');
const axios         = require('axios');
const dnsServer     = require('./dns-server');

function dnsLookup(host) {
    return new Promise(
        (resolve, reject) => {
            dns.lookup(host, { family: 4, all: true }, (error, addresses) => {
                if (error) return reject(error);
                resolve( addresses.map(i => i.address) )
            });
        }
    )
}


class Peer {
    constructor(ip) {
        this.ip = ip;
        this.stat = null;
        this.checkTimeout = null;
        this.checkFailures = 0;
        this.check();
    }

    check() {
        const self = this;
        axios.get('http://' + self.ip + ':228/stat')
            .then(
                response => {
                    if (!response.data.ok) {
                        throw response.data.error || response.data;
                    }
                    console.log(response.data.stat);
                    self.stat = response.data.stat;
                    self.checkTimeout = setTimeout(() => self.check(), 30000);
                    self.checkFailures = 0;
                }
            )
            .catch(
                error => {
                    console.error(error);
                    self.stat = null;
                    if (++self.checkFailures < 3) {
                        setTimeout(() => self.check(), 2000);
                    } else {
                        console.error('stopping checks for ' + self.ip + ' after ' + self.checkFailures + ' failures');
                    }
                }
            )
    }

    dispose() {
        if (this.checkTimeout) {
            clearTimeout(this.checkTimeout);
        }
    }
}

class PeerCollection {
    constructor() {
        /** @type {Peer[]} */
        this.list = [];
    }
    add(ip) {
        if (this.has(ip)) {
            console.log('already have peer ' + ip);
            return;
        }
        this.list.push(new Peer(ip));
    }
    remove(ip) {
        const index = this.getIndex(ip);
        if (index) {
            this.list[index].dispose();
            this.list.splice(index, 1);
        }
    }
    getIndex(ip) {
        for (let id in this.list) if (this.list.hasOwnProperty(id)) {
            if (this.list[id].ip == ip) {
                return id;
            }
        }
        return null;
    }
    has(ip) {
        return this.getIndex(ip) !== null;
    }
    getPeerIps() {
        return this.list.map(peer => peer.ip);
    }
}

let discoveryInterval;
let dnsUpdateInterval;

class PeerManager {
    constructor(nameDiscover, nameSelfInt, nameSelfExt) {
        this.nameDiscover = nameDiscover;
        this.nameSelfInt = nameSelfInt;
        this.nameSelfExt = nameSelfExt;
        this.nameSelfIp = null;
        this.peers = new PeerCollection();
    }
    init() {
        discoveryInterval = setInterval(() => this.discover(), 3000);
        dnsUpdateInterval = setInterval(() => this.dnsUpdate(), 3000);
        dnsServer.init();
        this.discover();
    }
    stop() {
        clearInterval(discoveryInterval);
        dnsServer.stop();
    }
    report() {
        return this.peers.list.map(peer => peer.stat);
    }
    dnsUpdate() {
        const hosts = {
            [this.nameSelfExt]: this.nameSelfIp
        };
        for (let peer of this.peers.list) {
            if (peer.stat) {
                hosts[peer.stat.nameExt] = peer.ip;
            }
        }
        dnsServer.hosts = hosts;
    }
    discover() {
        return Promise
            .all([
                dnsLookup(this.nameDiscover),
                dnsLookup(this.nameSelfInt)
            ])
            .then(([ everyone, [self] ]) => {
                this.nameSelfIp = self;
                const freshPeerIps = everyone.filter(ip => ip != self);
                for (let existingPeerIp of this.peers.getPeerIps()) {
                    if (freshPeerIps.indexOf(existingPeerIp) == -1) {
                        console.log('peer disappeared: ' + existingPeerIp);
                        this.peers.remove(existingPeerIp);
                    }
                }
                for (let freshPeerIp of freshPeerIps) {
                    if (!this.peers.has(freshPeerIp)) {
                        console.log('new peer found: ' + freshPeerIp);
                        this.peers.add(freshPeerIp);
                    }
                }
            });
    }
}

module.exports = PeerManager;