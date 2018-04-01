<template>
    <span>
        <slot></slot>
    </span>
</template>

<script>
    import $ from 'jquery';

    export default {
        props: {
            area: { type: Number, default: 0 },
            box: { required: false }
        },
        data () {
            return {
                visible: false,
                boxEl: null,
            }
        },
        mounted() {
            this.boxEl = this.box || window;
            $(this.boxEl).on('scroll resize', this.check);
            this.check();
        },
        beforeDestroy() {
            $(this.boxEl).off('scroll resize', this.check);
        },
        methods: {
            check() {
                const $el = $(this.$el);
                const windowTop = $(this.boxEl).scrollTop();
                const windowBottom = windowTop + $(this.boxEl).height();
                const elementTop = this.boxEl === window ? $el.offset().top : $el.scrollTop();
                const elementBottom = elementTop + $el.height();

                const visible = (windowTop < elementBottom + this.area)
                             && (windowBottom > elementTop - this.area);

                if (visible && !this.visible) {
                    this.$emit('enter');
                }
                if (!visible && this.visible) {
                    this.$emit('leave');
                }

                this.visible = visible;
            },
        },

    }
</script>
