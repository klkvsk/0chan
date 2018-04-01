let zIndex = 1;
let pixels = [];

const PX_W = 12;
const PX_H = 18;

import channel from './channel'

function initPixels() {
    const light = document.getElementsByTagName('b');
    for (let i in light) if (light.hasOwnProperty(i)) {
        light[i].className = 'idle';
    }

    const dark = document.getElementsByTagName('i');
    for (let i in dark) if (dark.hasOwnProperty(i)) {
        dark[i].className = 'idle';
    }

    const rows = document.getElementsByClassName('row');
    for (let i in rows) if (rows.hasOwnProperty(i)) {
        pixels.push(rows[i].children);
    }

    pixels = pixels.reverse();
}
initPixels();

function jump(x, y) {
    const px = pixels[y][x];
    px.className = '';
    px.style.zIndex = zIndex++;
    if (zIndex > 10e5) zIndex = 0;
    px.parentElement.style.zIndex = zIndex++;
    setTimeout(
        function () { px.className = 'idle' }, 50
    )
}


const canvas = document.getElementsByTagName('canvas')[0];
const canvasCtx = canvas ? canvas.getContext('2d') : null;
const canvasH   = canvas ? canvas.height : 0;
const canvasW   = canvas ? canvas.width  : 0;


const audio = document.getElementById('loop');
const audioCtx = new AudioContext();
const audioSrc = audioCtx.createMediaElementSource(audio);
const analyser = audioCtx.createAnalyser();
// we have to connect the MediaElementSource with the analyser
audioSrc.connect(analyser);
audioSrc.connect(audioCtx.destination);
// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
analyser.fftSize = 2048;
analyser.smoothingTimeConstant = 0.5;
// analyser.maxDecibels = -20;
analyser.minDecibels = -70;

// frequencyBinCount tells you how many values you'll receive from the analyser
const frequencyData = new Uint8Array(analyser.frequencyBinCount);

audio.loop = true;
audio.play();

function loop(fn) {
    requestAnimationFrame(() => fn() && loop(fn))
}
loop(function() {
    analyser.getByteFrequencyData(frequencyData);
    const off = 0;
    const len = 36;

    if (canvasCtx) {
        // draw spectrum
        canvasCtx.clearRect( 0, 0, canvasW, canvasH );
        canvasCtx.lineWidth   = 1;
        canvasCtx.strokeStyle = "white";
        let max = 0;
        for ( let i = off; i < off + len; i++) {
            canvasCtx.beginPath();
            canvasCtx.moveTo( 2 + i * 3, canvasH / 2 );
            canvasCtx.lineTo( 2 + i * 3, canvasH / 2 - frequencyData[ i ] / 2.56 / 2);
            canvasCtx.stroke();

            if (frequencyData[i] > 40) max = i;
        }
    }

    const bands = channel.input(frequencyData, len, off);
    if (canvasCtx) {
        canvasCtx.lineWidth   = 10;
        for ( let i = 0; i < bands.length; i++) {
            canvasCtx.beginPath();
            canvasCtx.moveTo( 6 + i * 13, canvasH / 2 + 1);
            canvasCtx.lineTo( 6 + i * 13, canvasH / 2 + 1 + bands[i]  * 40);
            canvasCtx.stroke();
        }
    }

    const jumps = channel.getJumps();

    if (canvasCtx) {
        canvasCtx.lineWidth   = 10;
        for ( let i = 0; i < bands.length; i++) {
            canvasCtx.beginPath();
            canvasCtx.moveTo( 6 + i * 13, canvasH);
            canvasCtx.lineTo( 6 + i * 13, canvasH - jumps[i]  * 40);
            canvasCtx.stroke();
        }
    }


    for (let i = 0; i < PX_H; i++) {
        if (!jumps[i]) continue;
        jump(Math.floor(Math.random() * PX_W), i);
        jump(Math.floor(Math.random() * PX_W), i);
    }

    return true;
});



