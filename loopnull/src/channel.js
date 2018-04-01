const HISTORY_SIZE  = 20;
const NUM_BANDS     = 18;
const SAMPLE_SIZE   = 256;

export default {
    history: [],
    inertia: [],
    current: [],

    input(spectrum, numSamples, offset) {
        let bands = [], count = [];

        // fill
        for (let i = offset; i < offset + numSamples; i++) {
            let n = i - offset;
            const bandNum = Math.floor(NUM_BANDS * Math.abs(Math.pow(n/(numSamples), 2)));
            // const bandNum = Math.round(i / 2);

            if (!bands[bandNum]) {
                bands[bandNum] = 0;
                count[bandNum] = 0;
            }

            let sample = Math.min(Math.abs(spectrum[i] / SAMPLE_SIZE), 1);
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
        for (let i = 0; i < NUM_BANDS; i++) {
            if (count[i] > 0) {
                bands[i] /= count[i];
            } else {
                bands[i] = 0;
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

    getJumps() {
        const average = [];
        const jumping = [];

        for (let i = 0; i < this.history.length; i++) {
            for (let band = 0; band < NUM_BANDS; band++) {
                if (!average[band]) {
                    average[band] = 0;
                }
                average[band] += this.history[i][band];
            }
        }

        for (let band = 0; band < NUM_BANDS; band++) {
            if (average[band] > 0) {
                average[band] /= this.history.length;
            } else {
                average[band] = 0;
            }

            if (this.inertia[band] > 0.05) {
                this.inertia[band] *= 0.9;
            } else {
                this.inertia[band] = 0;
            }

            if (this.current[band] > average[band] * 1.2) {
                // this.inertia[band] = jumping[band] = average[band];
                jumping[band] = 0.1;

            } else {
                // jumping[band] = this.inertia[band];
                jumping[band] = 0;
            }

        }

        return jumping;
    }

}