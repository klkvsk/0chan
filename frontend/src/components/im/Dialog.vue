<template>
    <div v-if="ready" class="dialog panel panel-default">
        <div class="dialog-header panel-heading">
            <span class="dialog-header-buttons">
                <button v-if="dialogId" class="btn btn-link" @click="deleteDialog">
                    <i class="fa fa-fw fa-trash"></i>
                </button>
                <router-link tag="button" class="btn btn-link" :to="{name:'im'}">
                    <i class="fa fa-fw fa-times"></i>
                </router-link>
            </span>
            <div>
                <span class="im-address">{{to.address}}</span>
                {{to.name}}
                <i class="fa fa-arrows-h" style="padding: 0 10px"></i>
                {{as.name}}
                <span class="im-address">{{as.address}}</span>
            </div>
        </div>
        <div class="dialog-body panel-body" ref="scrollable" id="im-scrollable">
            <div v-if="messages.length == 0" class="empty-page">
                <h4>нет сообщений</h4>
            </div>
            <div v-else>
                <div class="text-center vspace" v-if="hasBefore">
                    <VisibilityTracker @enter="fetchMessagesBefore" :area="20" :box="'#im-scrollable'" ref="before">
                        <button class="btn btn-default" @click.prevent="fetchMessagesBefore" :disabled="fetchingBefore">
                        <span v-if="fetchingBefore">
                            <i class="fa fa-spinner fa-spin fa-fw"></i> подгружаем более ранние сообщения
                        </span>
                        <span v-if="!fetchingBefore">
                            <i class="fa fa-ellipsis-h"></i> подгрузить более ранние сообщения
                        </span>
                        </button>
                    </VisibilityTracker>
                </div>

                <div v-for="(message, index) in messages">
                    <div v-if="index > 0" class="im-separator"></div>
                    <div :class="{
                                'im-message': true,
                                'message-unread': message.isUnread,
                                [message.isIncoming ? 'message-incoming' : 'message-outcoming']: true,
                            }">
                        <span class="message-name"><b>{{message.from.name}}:</b>
                        </span>
                        <span class="message-date pull-right">{{message.date | timestamp}}</span>
                        <div class="message-text"  v-html="message.html"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dialog-footer panel-footer">
            <form @submit.prevent="sendMessage" @keyup.ctrl.enter="sendMessage">
                <div class="input-group">
                    <textarea maxlength="2000" v-model="message" class="form-control" ref="textarea" :disabled="sending"></textarea>
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary" :disabled="sending">
                            <i class="fa fa-send"></i>
                        </button>
                    </span>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import $ from 'jquery';
    import Dialog from '../../services/Dialog'
    import BusEvents from '../../app/BusEvents'
    import Updater from '../../app/Updater'
    import VisibilityTracker from '../VisibilityTracker.vue'

    let updater;

    export default {
        props: [
            'toAddress', 'asAddress'
        ],
        components: {
            VisibilityTracker
        },
        data() {
            return {
                ready: false,
                dialogId: null,
                to: null,
                as: null,
                messages: [],
                message: '',
                sending: false,
                fetchingBefore: false,
                hasBefore: false,
            }
        },
        computed: {
            lastMessageId() {
                return this.messages.length > 0
                    ? this.messages[this.messages.length - 1].id
                    : undefined
            },
            firstMessageId() {
                return this.messages.length > 0
                    ? this.messages[0].id
                    : undefined
            }
        },
        created() {
            updater = new Updater(() => this.update(), 3000);
        },
        beforeDestroy() {
            updater.cancelNext();
        },
        watch: {
            $route() {
                this.ready = false;
                updater.checkLater();
            }
        },
        methods: {
            fetch() {
                return Dialog.get(this.asAddress, this.toAddress).then(
                    response => {
                        if (response.data.ok) {
                            this.dialogId = response.data.id;
                            this.to = response.data.to;
                            this.as = response.data.as;
                            this.messages = response.data.messages;
                            this.hasBefore = response.data.hasBefore;
                            this.ready = true;
                            this.$nextTick(() => {
                                this.$refs.textarea.focus();
                                this.scrollToBottom();
                                this.checkReadMessages();
                                updater.checkLater();
                            });
                        }
                    }
                );
            },
            fetchMessagesBefore() {
                if (!this.ready) return;
                this.fetchingBefore = true;
                return Dialog.get(this.asAddress, this.toAddress, null, this.firstMessageId).then(
                    response => {
                        if (response.data.ok) {
                            const oldScrollHeight = this.$refs.scrollable.scrollHeight;
                            this.messages = response.data.messages.concat(this.messages);
                            this.hasBefore = response.data.hasBefore;
                            this.$nextTick(() => {
                                const newScrollHeight = this.$refs.scrollable.scrollHeight;
                                this.$refs.scrollable.scrollTop += newScrollHeight - oldScrollHeight;
                            })
                        }
                        this.fetchingBefore = false;
                    }
                );
            },
            update() {
                if (!this.ready) return;
                return Dialog.get(this.asAddress, this.toAddress, this.lastMessageId).then(
                    response => {
                        if (response.data.ok && this.ready) {
                            if (!response.data.messages.length) return;
                            this.messages = this.messages.concat(response.data.messages);
                            this.checkReadMessages();
                            this.$nextTick(() => this.scrollToBottom(true));
                            for (let message of this.messages) {
                                this.$bus.emit(BusEvents.NEW_MESSAGE, response.data.id, message);
                            }
                        }
                    }
                );
            },
            checkReadMessages() {
                for (let message of this.messages) {
                    if (message.isUnread) {
                        setTimeout(() => message.isUnread = false, 500);
                    }
                }
            },
            sendMessage() {
                if (!this.message) return;
                this.sending = true;

                Dialog.send(this.as.address, this.to.address, this.message).then(
                    response => {
                        if (response.data.ok) {
                            this.message = '';
                            this.sending = false;
                            this.update();
                            this.$nextTick(() => {
                                this.$refs.textarea.focus();
                                this.scrollToBottom(true)
                            });
                        } else if (response.data.reason) {
                            this.sending = false;
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.reason)
                        }
                    }
                )
            },
            scrollToBottom(slow) {
                $(this.$refs.scrollable).animate({ scrollTop: this.$refs.scrollable.scrollHeight }, slow ? 200 : 0)
            },
            deleteDialog() {
                if (!confirm('Удалить этот диалог?')) return;

                this.sending = true;
                return Dialog.deleteDialog(this.dialogId).then(
                    response => {
                        this.$router.push({ name: 'im' })
                    }
                )
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import '~assets/styles/_vars';

    .dialog {
        height: 100%;

        position: relative;

        $dialogs-header-height: 40px;
        $dialogs-footer-height: 80px;

        .dialog-header {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: $dialogs-header-height;

            .dialog-header-buttons {
                position: absolute;
                top: 2px;
                right: 0;
            }
        }

        .dialog-body {
            position: absolute;
            top: $dialogs-header-height;
            right: 0;
            left: 0;
            bottom: $dialogs-footer-height;
            overflow: auto;
        }

        .dialog-footer {
            position: absolute;
            bottom: 0;
            right: 0;
            left: 0;
            height: $dialogs-footer-height;
            textarea, .btn {
                height: 60px;
            }
        }

        .im-separator {
            border-top: 1px solid $color-white-dk;
        }
        .im-message {
            line-height: 1.2em;
            transition: background 3s;
            padding: 10px 4px;
            background: white;
        }
        .message-incoming .message-name {
            color: $color-green-dk;
        }
        .message-outcoming .message-name {
            color: $color-grey;
        }
        .message-unread {
            background: $color-green-bg;
        }
        .message-name {
            white-space: nowrap;
            vertical-align: top;
        }
        .message-text {
            vertical-align: top;
            width: 100%;
        }
        .message-date {
            white-space: nowrap;
            vertical-align: top;
            color: $color-grey-lt;
            font-family: $font-family-monospace;
            font-size: 0.8em;
        }

        [disabled] {
            cursor: progress !important;
        }

    }
</style>