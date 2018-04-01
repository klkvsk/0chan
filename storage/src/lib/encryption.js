const crypto        = require('crypto');


function test() {
    const cleanText = new Buffer('0123456789012345678901234567890123456789');
    const blockSize = 16;

    const cipherText = new Buffer(cleanText.length);

    for (let i = 0; i < cleanText.length; i += blockSize) {
        const cipher = crypto.createCipher('aes-128-ctr', 'key');
        let partCipherText = Buffer.concat([
            cipher.update(cleanText.slice(i, i + blockSize)),
            cipher.final()
        ]);
        partCipherText.copy(cipherText, i);
    }


    const [ rangeMin, rangeMax ] = [ 20, 35 ];
    const [ rangeMinAligned, rangeMaxAligned ] = [
        Math.floor(rangeMin / blockSize) * blockSize,
        Math.ceil (rangeMax / blockSize) * blockSize
    ];
    console.log({ rangeMin, rangeMax, rangeMinAligned, rangeMaxAligned });

    const decipherText = new Buffer(rangeMax - rangeMin);
    let decipherTextOffset = 0;
    for (let i = rangeMinAligned; i < rangeMaxAligned; i += blockSize) {
        const decipher = crypto.createDecipher('aes-128-ctr', 'key');
        let partDecipherText = Buffer.concat([
            decipher.update(cipherText.slice(i, i + blockSize)),
            decipher.final()
        ]);

        const leftOffset  = (i             < rangeMin)  ? rangeMin - rangeMinAligned : 0;
        const rightOffset = (i + blockSize > rangeMax)  ? rangeMaxAligned - rangeMax : 0;
        if (leftOffset || rightOffset) {
            partDecipherText = partDecipherText.slice(leftOffset, blockSize - rightOffset);
        }

        partDecipherText.copy(decipherText, decipherTextOffset);
        decipherTextOffset += blockSize - leftOffset - rightOffset;
    }

    const debug = {
        cleanTextLen:       cleanText.length,
        cleanTextData:      cleanText.toString('ascii'),
        cipherTextLen:      cipherText.length,
        cipherTextData:     cipherText.toString('ascii'),
        decipherTextLen:    decipherText.length,
        decipherTextData:   decipherText.toString('ascii'),
    };
    console.log(debug);

}