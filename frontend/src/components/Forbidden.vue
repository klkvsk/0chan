<template>
    <div>
        <Headline>
        </Headline>
        <div class="empty-page">
            <h1>4Ø3!</h1>
            <p>
                {{ reason }}
            </p>
        </div>
    </div>
</template>

<script>
    import Headline from './Headline.vue'
    export default {
        props: { reason: { type: String, default: 'Доступ закрыт' } },
        components: { Headline }
    }
</script>