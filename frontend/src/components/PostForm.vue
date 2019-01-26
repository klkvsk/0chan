<template>
    <div class="reply-form block">
        <div class="dropzone" v-show="isDragging" @drop="onDragDrop">
            <span class="li_stack"></span>
        </div>
        <div class="reply-form-uploads post-attachments" v-if="uploads.length">
            <PostAttachment v-for="u in uploads" :key="u.id" :attachment="u" :moderatable="true"
                            @delete="deleteAttachment"
            ></PostAttachment>
        </div>

        <div class="reply-form-message">
            <div v-if="replyTo">
                &gt;&gt;{{replyTo.id}}
            </div>
            <textarea maxlength="9001" v-model="form.message" @paste="onPaste" @keyup.ctrl.enter="send()" ref="textarea"></textarea>
        </div>
        <div style="margin-top: 5px; text-align: left">
            <button @click="send()" class="btn btn-xs btn-primary" :disabled="isSending || (this.uploading > 0) || (!form.message && !uploads.length)">
                <span v-if="isSending"><i class="fa fa-spinner fa-spin fa-fw"></i> Отправка...</span>
                <span v-if="!isSending"><i class="fa fa-send"></i> Отправить</span>
            </button>
            <span class="reply-form-limit-counter" title="Длина сообщения / Макс. длина">
                <span>{{form.message.length}}</span>/{{maxLength}}
            </span>
            <div class="pull-right attachment-btns">
                <span class="reply-form-limit-counter" title="Приложено / Макс. файлов">
                    <span>{{uploads.length}}</span>/{{maxUploads}}
                </span>
                <span v-if="uploading > 0">
                    <i class="fa fa-spinner fa-spin fa-fw"></i> Загрузка ({{uploading}})...
                </span>
                <input @change="onFileSelected" type="file" hidden multiple="multiple" accept="image/*" />
                <button class="btn btn-xs btn-default" @click="selectFile" :disabled="uploads.length >= maxUploads">
                    <i class="fa fa-file-image-o"></i> Прикрепить
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from 'jquery'
    import DragEvents2 from 'dragevents2';
    import Vue from 'vue'
    import UI from '../app/UI'
    import BusEvents from '../app/BusEvents'
    import Api from '../services/Api'
    import Thread from '../services/Thread'
    import Attachment from '../services/Attachment'
    import PostAttachment from './PostAttachment'
    import BanInfo from '../components/BanInfo.vue'

    export default {
        components: {
            PostAttachment
        },
        props: [ 'reply-to', 'board' ],
        data() {
            return {
                maxLength: 9001,
                maxUploads: 8,
                form: {
                    board: null,
                    thread: null,
                    parent: null,
                    message: '',
                },
                files: [],
                embeds: [],
                uploads: [],
                uploading: 0,
                isSending: false,
                isDragging: false,
            };
        },
        watch: {
            files: () => {
                this.upload(this.files);
            },
            'form.message'() {
                this.$nextTick(() => {
                    const textarea = this.$refs.textarea;
                    textarea.style.cssText = `height:auto`;
                    textarea.style.cssText = `height: ${textarea.scrollHeight}px;`;
                })
            }
        },
        mounted() {
            new DragEvents2(this.$el);
            this.$el.addEventListener('dragenter2', this.onDragEnter);
            this.$el.addEventListener('dragleave2', this.onDragLeave);
            $('.dropzone', this.$el).on('dragenter dragover', (e) => e.preventDefault());
        },
        methods: {
            onDragEnter(e) {
                e.preventDefault();
                e.stopPropagation();
                this.isDragging = true;
            },
            onDragLeave(e) {
                e.preventDefault();
                e.stopPropagation();
                setTimeout(() => this.isDragging = false, 200);
            },
            onDragDrop(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e && e.dataTransfer && e.dataTransfer.items) {
                    this.importFileItems(e.dataTransfer.items);
                }
                this.isDragging = false;
            },
            onPaste(e) {
                if (e && e.clipboardData && e.clipboardData.items) {
                    this.importFileItems(e.clipboardData.items);
                    this.importEmbedUrls(e.clipboardData.getData('Text'));
                }
            },
            importFileItems(items) {
                const files = [];
                for (let i = 0; i < items.length; i++) if (items.hasOwnProperty(i)) {
                    const item = items[i];
                    if (item.kind === 'file' && item.type.match(/^image\//)) {
                        files.push(item.getAsFile());
                    }
                }
                if (files.length) {
                    this.upload(files);
                }
            },
            importEmbedUrls(text) {
                const embedMatchers = [
                    /youtube\.com\/watch\?v=([a-z0-9_-]+)/ig,
                    /youtu\.be\/([a-z0-9_-]+)/ig,
                    /vimeo\.com\/([0-9]+)/ig,
                    /(soundcloud.com\/[a-z0-9\-_\/]+)/ig,
                    /coub\.com\/view\/([a-z0-9]+)/ig,
                    /ted\.com\/talks\/([a-z0-9_-]+)/ig,
                ];

                const embeds = [];
                for (let matcher of embedMatchers) {
                    let match;
                    while (match = matcher.exec(text)) {
                        const url = match[0];
                        if (embeds.indexOf(url) == -1 && this.embeds.indexOf(url) == -1) {
                            embeds.push(url);
                        }
                    }
                }

                let n = 0;
                for (let embed of embeds) {
                    if (++n + this.uploads.length > this.maxUploads) break;

                    this.uploading++;
                    Attachment.embed(embed).then(response => {
                        this.uploading--;
                        if (response.data.ok) {
                            let upload = response.data.attachment;
                            this.uploads.push(upload);
                        } else if (response.data.reason) {
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.reason);
                        }
                    });
                }

                this.embeds = this.embeds.concat(embeds);

            },
            focus() {
                $('textarea', this.$el).focus();
            },
            selectFile(event) {
                $(event.target).closest('.attachment-btns').find('input[type=file]').click();
            },
            send (captcha) {
                this.form.images = this.uploads.map(upload => upload.token);
                this.form.captcha = captcha || null;
                let promise;
                if (this.replyTo) {
                    promise = Thread.reply(this.replyTo.id, this.form);
                } else if (this.board) {
                    promise = Thread.create(this.board.dir, this.form);
                }

                this.isSending = true;

                return promise
                    .then(response => {
                        this.isSending = false;
                        const result = response.data;
                        if (result.ok) {
                            this.$emit('submit', result.post)
                        } else if (result.reason) {
                            switch (result.reason) {
                                case 'form':
                                    this.$bus.emit(BusEvents.ALERT_ERROR, 'Ошибки заполнения формы: ' + response.data.errors.join(', '));
                                    break;

                                case 'ban':
                                    this.$bus.emit(BusEvents.SHOW_MODAL, BanInfo, { ban: response.data.ban, appealable: true  }) ;
                                    break;

                                case 'deleted':
                                    this.$bus.emit(BusEvents.ALERT_ERROR, 'Указанный пост был удален, на него больше нельзя ответить') ;
                                    break;

                                case 'locked':
                                    this.$bus.emit(BusEvents.ALERT_ERROR, 'Тред был закрыт, в него нельзя больше писать') ;
                                    break;

                                case 'blockRu':
                                    this.$bus.emit(BusEvents.ALERT_ERROR, 'На этой доске нельзя постить из РФ') ;
                                    break;

                                case 'gimme_image':
                                    this.$bus.emit(BusEvents.ALERT_ERROR, 'Налепите пикчу...') ;
                            }
                        } else {
                            throw new Error(result);
                        }
                    });
            },

            onFileSelected(event) {
                let files = event.target.files;
                this.upload(files);
                event.target.value = null;
            },

            upload(files) {
                for (let n = 0; n < files.length; n++) {
                    // check if limit is reached or will be reached with previously started uploads finish
                    if (n + this.uploading + this.uploads.length > this.maxUploads) {
                        break;
                    }
                    const file = files[n];
                    this.uploading++;
                    Attachment.upload(file).then(response => {
                        this.uploading--;
                        if (response.data.ok) {
                            let upload = response.data.attachment;
                            this.uploads.push(upload);
                        } else if (response.data.reason) {
                            this.$bus.emit(BusEvents.ALERT_ERROR, response.data.reason);
                        }
                    });
                }
            },
            deleteAttachment(attachmentId) {
                this.uploads = this.uploads.filter(attachment => attachment.id !== attachmentId);
            },
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .reply-form {
        max-width: 100%;
        clear: both;
        position: relative;

        .dropzone {
            position: absolute;
            top: -4px;
            bottom: -4px;
            left: -4px;
            right: -4px;
            border: 2px dashed $color-grey;
            background: #fff;
            opacity: 0.5;
            text-align: center;
            span {
                font-size: 64px;
                line-height: 96px;
            }
        }

        .reply-form-message {
            margin: 0;
            border: 1px solid #ccc;
            padding: 2px;
            background: #fff;

            textarea {
                min-height: 2em;
                min-width: 500px;
                width: 100%;
                margin: 0;
                border: 0;
                padding: 0;
                outline: 0;
                display: block;

                box-sizing: content-box;
                resize: none;
            }
        }

        .reply-form-limit-counter {
            color: $color-grey-lt;
            font-size: 80%;

            span {
                color: $color-grey;
            }
        }
    }

    @media (max-width: $screen-xs-max) {
        .reply-form {
            width: 95%;
        }
        .reply-form-message {
            textarea {
                min-width: inherit !important;
            }
        }
    }
</style>