<template>
    <div class="form-horizontal">
        <div v-for="field in form" class="form-group" :class="{ 'has-error': field.error }">
            <label class="control-label col-md-8">{{field.title}}</label>
            <div class="col-md-12">
                <span v-if="field.type == 'string' && field.max">
                    <input
                            class="form-control"
                            type="text"
                            v-model="data[field.name]"
                            :required="field.required"
                            :minlength="field.min"
                            :maxlength="field.max"
                            :pattern="field.pattern"
                            :placeholder="field.description"
                    />
                    <div v-if="field.description" class="text-muted">({{field.description}})</div>
                </span>


                <span v-if="field.type == 'int'">
                    <input
                            class="form-control"
                            type="number"
                            v-model="data[field.name]"
                            :required="field.required"
                            :min="field.min"
                            :max="field.max"
                    />
                    <div v-if="field.description" class="text-muted">({{field.description}})</div>
                </span>


                <span v-if="field.type == 'string' && !field.max">
                    <textarea
                            class="form-control"
                            v-model="data[field.name]"
                    ></textarea>
                    <div v-if="field.description" class="text-muted">({{field.description}})</div>
                </span>

                <div class="checkbox" v-if="field.type == 'bool'">
                    <label>
                        <input type="checkbox" v-model="data[field.name]">
                        {{field.description}}
                    </label>
                </div>

                <div v-if="field.error" class="help-block">* {{field.error}}</div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [ 'form', 'data' ],
    }
</script>