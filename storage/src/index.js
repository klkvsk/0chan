'use strict';

const fs            = require('fs');
const path          = require('path');
const base64url     = require('base64url');
const express       = require('express');
const multer        = require('multer');
const sharp         = require('sharp');

const SIZES = [ 0, /*600,*/ 400, 200, 100 ];
const PATH_STORAGE = '/storage';
const MAX_DIMENSION_SIZE = 12000;
const USE_AES = false;

const NAME_DISCOVER = process.env.DISCOVER || 'tasks.storage';
const NAME_SELF_INT = process.env.HOSTNAME;
const NAME_SELF_EXT = fs.readFileSync(PATH_STORAGE + '/id', 'ascii').trim();

const Peering  = require('./lib/peering');
const DiskInfo = require('./lib/diskinfo');
const peering       = new Peering(NAME_DISCOVER, NAME_SELF_INT, NAME_SELF_EXT);
const diskinfo      = new DiskInfo(PATH_STORAGE);
diskinfo.init();

function report() {
    return {
        nameInt: NAME_SELF_INT,
        nameExt: NAME_SELF_EXT,
        disk: diskinfo.state,
    }
}

const app = express();
const upload = multer({
    storage: multer.memoryStorage(),
});

app.get('/', (req, res) => {
    res.send({
        ok: true
    })
});

app.get('/stat', (req, res) => {
    res.send({
        ok: true,
        stat: report()
    })
});

app.get('/stats', (req, res) => {
    res.send({
        ok: true,
        self: report(),
        peers: peering.report()
    });
});

app.post('/image', upload.single('file'), (req, res) => {
    if (!req.file) {
        res.send({ ok: false, error: 'no file' });
        return;
    }

    sharp(req.file.buffer).metadata()
        .then((meta) => {
            // check image format
            if ([ 'jpeg', 'png', 'gif', 'webp' ].indexOf(meta.format) == -1) {
                throw 'Формат не поддерживается: ' + meta.format;
            }

            // check image dimensions
            if (meta.width > MAX_DIMENSION_SIZE || meta.height > MAX_DIMENSION_SIZE) {
                throw 'Слишком большой размер, макс.: ' + MAX_DIMENSION_SIZE + 'x' + MAX_DIMENSION_SIZE;
            }

            return processImage(req.file.buffer, SIZES, makeFilename())
        })
        .then(result => res.send({ ok: true, result }))
        .catch(error => res.send({ ok: false, error: error.toString() }))
});

app.get('*', (req, res) => {
    const aesKey = req.query.key ? new Buffer(base64url.toBase64(req.query.key), 'base64') : null;
    const name = path.basename(req.path);
    const filePath = [ PATH_STORAGE, name.substr(0, 2), name.substr(2, 2), name.substr(4, 2), name ].join(path.sep);

    let readStream = fs.createReadStream(filePath);
    readStream.on('open', () => {
        if (aesKey) {
            const decrypt = crypto.createDecipher('aes-256-ctr', aesKey);
            readStream = readStream.pipe(decrypt)
        }
        readStream.pipe(res);
    });
    readStream.on('error', err => res.send({ error: err }) );
});

app.delete('*', (req, res) => {
    const name = path.basename(req.path);
    const filePath = [ PATH_STORAGE, name.substr(0, 2), name.substr(2, 2), name.substr(4, 2), name ].join(path.sep);
    fs.unlink(filePath, (err) => {
        if (err && err.code == 'ENOENT') err = null; // already deleted, still ok
        res.send({ ok: !err, error: err || undefined })
    });
});

const server = app.listen(228, () => {
    console.log('Server started');
    peering.init();
});


process.on('SIGINT', () => {
    diskinfo.stop();
    peering.stop();
    server.close();
});

function processImage(buffer, sizes, filename) {
    const [ size, ...next ] = sizes;
    const sharpImage = sharp(buffer);

    return Promise.resolve(sharpImage.metadata())
        .then((metadataOriginal) => {
            if (size > 0) {
                // do resize
                return sharpImage
                    .resize(size, size)
                    .max()
                    .withoutEnlargement(true)
                    .background({r: 255, g: 255, b: 255, alpha: 1})
                    .flatten()
                    .toFormat('jpeg')
                    .toBuffer();
            } else {
                if (metadataOriginal.format === 'gif') {
                    // ignore any processing for gifs
                    return buffer;
                }
                // to cut exifs, rarjpegs, and such
                return sharpImage.toBuffer();
            }
        })
        .then(
            buf => Promise.all([
                buf,
                sharp(buf).metadata(),
                next.length ? processImage(buf, next, filename) : undefined
            ])
        )
        .then(([ buf, metadataProcessed, next ]) => {
            const fileExt = metadataProcessed.format === 'jpeg' ? 'jpg' : metadataProcessed.format;
            const fullFilename = filename + (size ? '-' + size : '') + '.' + fileExt;
            return saveBuffer(buf, fullFilename)
                .then((savedFileAesKey) => Object.assign(
                    {
                        [size]: {
                            name:   fullFilename,
                            aeskey: savedFileAesKey,
                            format: metadataProcessed.format,
                            width:  metadataProcessed.width,
                            height: metadataProcessed.height,
                            size:   buf.length
                        },
                    },
                    next)
                );
        })
}

function saveBuffer(buf, filename) {
    let promise = Promise.resolve();

    let aesKey;
    if (USE_AES) {
        promise = promise.then(() => new Promise((resolve, reject) => {
            crypto.randomBytes(64, (err, buf) => {
                if (err) {
                    reject(err);
                } else {
                    aesKey = buf;
                    resolve();
                }
            })
        }));
    }

    // create path
    const path = [ PATH_STORAGE, filename.substr(0, 2), filename.substr(2, 2), filename.substr(4, 2) ];
    for (let i = 1; i <= path.length; i++) {
        const subpath = path.slice(0, i).join('/');
        promise = promise.then(() => new Promise(resolve => {
            //console.log('creating ' + subpath);
            fs.mkdir(subpath, resolve);
        }));
    }

    if (USE_AES) {
        promise = promise.then(() => {
            const cipher = crypto.createCipher('aes-256-ctr', aesKey);
            return Buffer.concat([ cipher.update(buf), cipher.final() ]);
        });
    } else {
        promise = promise.then( () => buf );
    }

    promise = promise.then((buffer) => {
        fs.writeFile(
            path.join('/') + '/' + filename,
            buffer,
            (err) => new Promise((resolve, reject) => err ? reject(err) : resolve())
        );
    });

    return promise.then(() => USE_AES ? base64url.encode(aesKey) : null);
}

function makeFilename() {
    const d = new Date();
    const z = v => ((""+v).length-1?"":"0")+v;
    let filename = z(d.getYear()-100) + z(d.getMonth()+1) + z(d.getDate());
    for (let i = 0; i < 12; i++) {
        filename += (Math.floor(Math.random() * 26) + 10).toString(36);
    }
    return filename;
}
