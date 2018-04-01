<template>
    <div>
        <Headline>
            <span slot="title">Сообщения</span>
        </Headline>
        <div class="row vspace2">
            <div class="text-center">
                <AddIdentity />
            </div>
        </div>
    </div>
</template>

<script>
    import Headline  from '../Headline.vue'
    import AddIdentity from './AddIdentity.vue'
    import Dialog from '../../services/Dialog'

    export default {
        components: {
            Headline,
            AddIdentity
        },
        methods: {
            fetch() {
                return Dialog.identities().then(
                    response => {
                        if (response.data.identities.length) {
                            this.$router.push({name: 'im'});
                        }
                    }
                )
            }
        }
    }
</script>