const diskusage = require('diskusage');

let diskCheckInterval;

class DiskManager {
    constructor(path) {
        this.path = path;
        this.state = {};
    }
    init() {
        diskCheckInterval = setInterval(() => this.check(), 30000);
        this.check();
    }
    stop() {
        clearInterval(diskCheckInterval);
    }
    check() {
        diskusage.check(this.path, (err, diskinfo) => {
            if (err) {
                console.error(err);
            } else {
                this.state = diskinfo;
            }
        });
    }
}

module.exports = DiskManager;