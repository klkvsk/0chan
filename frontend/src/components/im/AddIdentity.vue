<template>
    <div class="panel panel-default identity-add-form">
        <div class="panel-heading">
            <b>Создание личности</b>
        </div>
        <div class="panel-body">
            <p>
                Для начала использования личных переписок, нужно создать личность.
            </p>
            <p>
                Будет сгенерирован случайный уникальный ID, который затем можно указывать в качестве адреса,
                не выдавая свой логин. Личностей можно сделать несколько и переключаться между ними.
                В каждой из них будет свой список диалогов.
            </p>
            <p>
                У личности также должно быть имя. Можно задать любой псевдоним, он не обязан быть уникальным.
                Имя служит для удобства, оно видно собеседникам и помогает не запутаться в контактах.
            </p>
            <form class="form" @submit.prevent="onSubmit">
                <div class="input-group vspace">
                    <input type="text" class="form-control" v-model="name" required placeholder="Имя" maxlength="32" :disabled="isSubmitting" />
                    <span class="input-group-btn">
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                        <i class="fa fa-plus"></i>
                        <span class="hidden-xs hidden-sm">Создать</span>
                    </button>
                    </span>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import BusEvents from '../../app/BusEvents'
    import Dialogs from '../../services/Dialog'

    export default {
        data() {
            return {
                name: '',
                isSubmitting: false
            }
        },
        methods: {
            onSubmit() {
                if (!this.name) return;

                this.isSubmitting = true;
                return Dialogs.addIdentity(this.name).then(
                    response => {
                        this.isSubmitting = false;
                        if (response.data.ok) {
                            this.$emit('close');
                            this.$bus.emit(BusEvents.REFRESH_CONTENT);
                        }
                    }
                )
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    .identity-add-form {
        text-align: justify;
        display: inline-block;
        max-width: 500px;
    }
</style>