<template>
    <div>
        <div class="dialog-list-filter">
            <div class="form-group has-feedback">
                <input class="form-control" v-model="listFilter" type="text" placeholder="Поиск по адресатам" />
                <span :class="listFilter ? 'search-filled glyphicon-remove' : 'glyphicon-search'"
                      @click="listFilter = ''"
                      class="search-clear glyphicon form-control-feedback"></span>
            </div>
        </div>
        <div class="dialog-list-items">
            <div class="list-group" v-if="filteredDialogs.length > 0">
                <router-link class="list-group-item" v-for="dialog in filteredDialogs"
                             :key="dialog.id"
                             :to="{ name: 'im_dialog', params: { toAddress: dialog.with.address, asAddress: dialog.as.address } }">
                    <div v-if="dialog.unreadCount > 0" class="dialog-list-count-unread">
                        +{{dialog.unreadCount}}
                    </div>

                    <div style="font-size: 0.8em">
                        <div>
                            <span class="im-address">{{dialog.with.address}}</span>
                            <b>{{dialog.with.name}}</b>
                        </div>
                        <div>
                            <span class="im-address">{{dialog.as.address}}</span>
                            {{dialog.as.name}}
                        </div>
                    </div>
                    <div class="dialog-last-message-text">
                        {{dialog.lastMessage.text }}
                    </div>
                    <div style="font-size: 0.8em">
                        <div class="color-grey-lt">
                            <span v-if="dialog.lastMessage.from.address==dialog.as.address">отправлено</span>
                            <span v-else>получено</span>
                            {{dialog.lastMessage.date | timeago }}
                        </div>
                    </div>
                </router-link>
            </div>
            <div v-else class="list-group" >
                <div class="list-group-item text-muted">Нет диалогов</div>
            </div>
        </div>
    </div>
</template>

<script>
    import BusEvents from '../../app/BusEvents'
    import Updater from '../../app/Updater'
    import Dialog from '../../services/Dialog'
    import Session from '../../services/Session'

    let updater;

    export default {
        data() {
            return {
                listFilter: null,
                dialogs: [],
                busEvents: {
                    [BusEvents.NEW_MESSAGE]: this.updateLastMessage,
                }
            }
        },
        computed: {
            filteredDialogs() {
                if (!this.listFilter) {
                    return this.dialogs;
                }
                const dialogs = [];
                let searchRe;
                try {
                    searchRe = new RegExp(this.listFilter, 'i');
                } catch (e) {
                    searchRe = new RegExp('');
                }
                for (let dialog of this.dialogs) {
                    const checkList = [
                        dialog.as.address,   dialog.as.name,
                        dialog.with.address, dialog.with.name,
                        dialog.lastMessage.text
                    ];
                    for (let checkProp of checkList) {
                        if (checkProp.match(searchRe)) {
                            dialogs.push(dialog);
                            break;
                        }
                    }
                }
                return dialogs;
            }
        },
        created() {
            updater = new Updater(() => this.fetchDialogs(), 10000);
        },
        beforeDestroy() {
            updater.cancelNext();
        },
        methods: {
            fetch() {
                return updater.checkNow();
            },
            fetchDialogs() {
                return Dialog.list()
                    .then(
                        response => {
                            this.dialogs = response.data.dialogs;
                            this.ready = true;

                            let unreadCount = 0;
                            for (let dialog of this.dialogs) {
                                unreadCount += dialog.unreadCount | 0;
                            }

                            Session.messages = unreadCount;

                            return response;
                        }
                    )
            },
            updateLastMessage(dialogId, message) {
                for (let dialogIdx in this.dialogs) {
                    let dialog = this.dialogs[dialogIdx];
                    if (dialog.id == dialogId) {
                        dialog.lastMessage = message;
                        dialog.updatedAt = message.date;
                        break;
                    }
                }
            },
            setListFilter(search) {
                this.listFilter = search;
            }
        }
    }
</script>