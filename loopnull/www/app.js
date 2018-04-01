/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 1);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});
var HISTORY_SIZE = 20;
var NUM_BANDS = 18;
var SAMPLE_SIZE = 256;

exports.default = {
    history: [],
    inertia: [],
    current: [],

    input: function input(spectrum, numSamples, offset) {
        var bands = [],
            count = [];

        // fill
        for (var i = offset; i < offset + numSamples; i++) {
            var n = i - offset;
            var bandNum = Math.floor(NUM_BANDS * Math.abs(Math.pow(n / numSamples, 2)));
            // const bandNum = Math.round(i / 2);

            if (!bands[bandNum]) {
                bands[bandNum] = 0;
                count[bandNum] = 0;
            }

            var sample = Math.min(Math.abs(spectrum[i] / SAMPLE_SIZE), 1);
            if (sample < 0.5) {
                sample = 0;
            }
            if (sample > 1) {
                console.log("sample overflow: #" + i + " " + sample);
            }

            bands[bandNum] += sample;
            count[bandNum] += 1;
        }

        // normalize
        for (var _i = 0; _i < NUM_BANDS; _i++) {
            if (count[_i] > 0) {
                bands[_i] /= count[_i];
            } else {
                bands[_i] = 0;
            }
        }

        if (this.current) {
            this.history.push(this.current);
        }

        while (this.history.length > HISTORY_SIZE) {
            this.history.shift();
        }
        console.log(this.history.length);
        this.current = bands;

        return bands;
    },
    getJumps: function getJumps() {
        var average = [];
        var jumping = [];

        for (var i = 0; i < this.history.length; i++) {
            for (var band = 0; band < NUM_BANDS; band++) {
                if (!average[band]) {
                    average[band] = 0;
                }
                average[band] += this.history[i][band];
            }
        }

        for (var _band = 0; _band < NUM_BANDS; _band++) {
            if (average[_band] > 0) {
                average[_band] /= this.history.length;
            } else {
                average[_band] = 0;
            }

            if (this.inertia[_band] > 0.05) {
                this.inertia[_band] *= 0.9;
            } else {
                this.inertia[_band] = 0;
            }

            if (this.current[_band] > average[_band] * 1.2) {
                // this.inertia[band] = jumping[band] = average[band];
                jumping[_band] = 0.1;
            } else {
                // jumping[band] = this.inertia[band];
                jumping[_band] = 0;
            }
        }

        return jumping;
    }
};

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _channel = __webpack_require__(0);

var _channel2 = _interopRequireDefault(_channel);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var zIndex = 1;
var pixels = [];

var PX_W = 12;
var PX_H = 18;

function initPixels() {
    var light = document.getElementsByTagName('b');
    for (var i in light) {
        if (light.hasOwnProperty(i)) {
            light[i].className = 'idle';
        }
    }var dark = document.getElementsByTagName('i');
    for (var _i in dark) {
        if (dark.hasOwnProperty(_i)) {
            dark[_i].className = 'idle';
        }
    }var rows = document.getElementsByClassName('row');
    for (var _i2 in rows) {
        if (rows.hasOwnProperty(_i2)) {
            pixels.push(rows[_i2].children);
        }
    }pixels = pixels.reverse();
}
initPixels();

function jump(x, y) {
    var px = pixels[y][x];
    px.className = '';
    px.style.zIndex = zIndex++;
    if (zIndex > 10e5) zIndex = 0;
    px.parentElement.style.zIndex = zIndex++;
    setTimeout(function () {
        px.className = 'idle';
    }, 50);
}

var canvas = document.getElementsByTagName('canvas')[0];
var canvasCtx = canvas ? canvas.getContext('2d') : null;
var canvasH = canvas ? canvas.height : 0;
var canvasW = canvas ? canvas.width : 0;

var audio = document.getElementById('loop');
var audioCtx = new AudioContext();
var audioSrc = audioCtx.createMediaElementSource(audio);
var analyser = audioCtx.createAnalyser();
// we have to connect the MediaElementSource with the analyser
audioSrc.connect(analyser);
audioSrc.connect(audioCtx.destination);
// we could configure the analyser: e.g. analyser.fftSize (for further infos read the spec)
analyser.fftSize = 2048;
analyser.smoothingTimeConstant = 0.5;
// analyser.maxDecibels = -20;
analyser.minDecibels = -70;

// frequencyBinCount tells you how many values you'll receive from the analyser
var frequencyData = new Uint8Array(analyser.frequencyBinCount);

audio.loop = true;
audio.play();

function loop(fn) {
    requestAnimationFrame(function () {
        return fn() && loop(fn);
    });
}
loop(function () {
    analyser.getByteFrequencyData(frequencyData);
    var off = 0;
    var len = 36;

    if (canvasCtx) {
        // draw spectrum
        canvasCtx.clearRect(0, 0, canvasW, canvasH);
        canvasCtx.lineWidth = 1;
        canvasCtx.strokeStyle = "white";
        var max = 0;
        for (var i = off; i < off + len; i++) {
            canvasCtx.beginPath();
            canvasCtx.moveTo(2 + i * 3, canvasH / 2);
            canvasCtx.lineTo(2 + i * 3, canvasH / 2 - frequencyData[i] / 2.56 / 2);
            canvasCtx.stroke();

            if (frequencyData[i] > 40) max = i;
        }
    }

    var bands = _channel2.default.input(frequencyData, len, off);
    if (canvasCtx) {
        canvasCtx.lineWidth = 10;
        for (var _i3 = 0; _i3 < bands.length; _i3++) {
            canvasCtx.beginPath();
            canvasCtx.moveTo(6 + _i3 * 13, canvasH / 2 + 1);
            canvasCtx.lineTo(6 + _i3 * 13, canvasH / 2 + 1 + bands[_i3] * 40);
            canvasCtx.stroke();
        }
    }

    var jumps = _channel2.default.getJumps();

    if (canvasCtx) {
        canvasCtx.lineWidth = 10;
        for (var _i4 = 0; _i4 < bands.length; _i4++) {
            canvasCtx.beginPath();
            canvasCtx.moveTo(6 + _i4 * 13, canvasH);
            canvasCtx.lineTo(6 + _i4 * 13, canvasH - jumps[_i4] * 40);
            canvasCtx.stroke();
        }
    }

    for (var _i5 = 0; _i5 < PX_H; _i5++) {
        if (!jumps[_i5]) continue;
        jump(Math.floor(Math.random() * PX_W), _i5);
        jump(Math.floor(Math.random() * PX_W), _i5);
    }

    return true;
});

/***/ })
/******/ ]);