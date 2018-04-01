<template>
    <div>
        <ul class="pagination" v-if="page && totalPages">
            <li v-if="!noArrows" :class="{ disabled: current == 1 }">
                <a href="#" @click.prevent="current > 1 && current--">
                    <i class="fa fa-angle-double-left"></i>
                </a>
            </li>
        </ul>
        <span v-for="(block, i) in pageBlocks">
            <span style="width: 10px; display: inline-block"> </span>
            <ul class="pagination">
                <li v-for="n in block" :class="{ active: current == n }">
                    <a href="#" @click.prevent="current = n">{{n}}</a>
                </li>
            </ul>
        </span>
        <span style="width: 10px; display: inline-block"> </span>
        <ul class="pagination">
            <li v-if="!noArrows" :class="{ disabled: current == totalPages }">
                <a href="#" @click.prevent="current < totalPages && current++">
                    <i class="fa fa-angle-double-right"></i>
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
    const SPREAD = 7;

    export default {
        props: ['page', 'totalPages', 'noArrows'],
        data() {
            return {
                current: parseInt(this.page)
            }
        },
        watch: {
            current(value) {
                this.$emit('change', value);
            }
        },
        computed: {
            pageBlocks() {
                const blocks = [];
                if (this.current - SPREAD > 1) {
                    blocks.push([1]);
                }
                const mainBlock = [];
                for (let i = Math.max(1, this.current - SPREAD); i <= Math.min(this.totalPages, this.current + SPREAD); i++) {
                    mainBlock.push(i);
                }
                blocks.push(mainBlock);

                if (this.current + SPREAD < this.totalPages) {
                    blocks.push([this.totalPages]);
                }
                return blocks;
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss"></style>