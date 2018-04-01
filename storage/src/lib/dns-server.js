'use strict';

const dnsd = require('dnsd');

module.exports = {
    server: null,
    hosts: {},
    init() {
        this.server = dnsd.createServer(
            (req, res) => {
                let query = req.question[0].name;
                while (query.length) {
                    if (this.hosts[query]) {
                        res.end(this.hosts[query]);
                    }
                    query = query.split('.').slice(0, -1).join('.');
                }
                res.end();
            }
        );
        this.server.listen(53);
    },
    stop() {
        if (this.server) {
            this.server.socket.close();
            this.server = null;
        }
    }
};
