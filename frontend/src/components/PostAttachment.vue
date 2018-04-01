<template>
    <figure class="post-img" :class="{ 'post-img-nsfw': showNsfwBlur, 'post-img-deleted': attachment.isDeleted }">
        <div class="post-img-labels">
            <span class="post-img-label post-img-nsfw-label" v-if="showNsfwLabel">NSFW</span>
            <span class="post-img-label post-img-gif-label" v-if="isGif">GIF</span>
            <span class="post-img-label post-img-deleted-label" v-if="attachment.isDeleted">DELETED</span>
        </div>
        <div class="post-img-buttons">
            <span v-if="moderatable" class="post-img-button" @click.stop="onMarkNsfwClick">
                <i v-if="!isMarkingNsfw" class="fa fa-eye-slash fa-fw"></i>
                <i v-if="isMarkingNsfw" class="fa fa-spinner fa-spin fa-fw"></i>
            </span>

            <span v-if="moderatable" class="post-img-button" @click.stop="onDeleteClick">
                <i v-if="!isDeleting" class="fa fa-trash fa-fw"></i>
                <i v-if="isDeleting" class="fa fa-spinner fa-spin fa-fw"></i>
            </span>

            <span v-if="attachment.embed && !thumbnail" class="post-img-button" @click.stop="onThumbnailClick">
                <i class="fa fa-times"></i>
            </span>
        </div>

        <span v-if="!attachment.embed">
            <figcaption>
                <span class="pull-left">
                    {{attachment.images.original.width}}&times;{{attachment.images.original.height}},
                    {{attachment.images.original.size_kb}}Кб
                </span>
            </figcaption>
            <a :href="attachment.images.original.url" target="_blank">
            <img
                    @click.prevent="onThumbnailClick"
                    :src="actualImage.url"
                    :srcset="retinaThumb"
                    :class="{'post-img-thumbnail': thumbnail, 'post-img-full': !thumbnail}"
                    :style="{width: actualImage.width + 'px', height: actualImage.height + 'px'}"
                    ref="image"
            />
            </a>
        </span>

        <div v-if="attachment.embed" class="post-embed">
            <span class="post-embed-overlay" v-if="thumbnail" @click="onThumbnailClick">
                <span v-if="thumbnail && attachment.embed.title">{{attachment.embed.title}}</span>

                <img class="post-embed-play-btn"
                     src="~assets/images/yt-play.png"
                     v-if="attachment.embed.service == 'youtube'"
                />
                <img class="post-embed-play-btn"
                     src="~assets/images/soundcloud-play.png"
                     v-if="attachment.embed.service == 'soundcloud'"
                     style="right: 10px; bottom: 10px;"
                />
                <img class="post-embed-play-btn"
                     src="~assets/images/vimeo-play.png"
                     v-if="attachment.embed.service == 'vimeo'"
                     style="right: 5px; bottom: 5px;"
                />
                <img class="post-embed-play-btn"
                     src="~assets/images/coub-play.png"
                     v-if="attachment.embed.service == 'coub'"
                     style="right: 8px; bottom: 5px;"
                />
                <img class="post-embed-play-btn"
                     src="~assets/images/ted-play.png"
                     v-if="attachment.embed.service == 'ted'"
                />
            </span>

            <img
                    v-if="thumbnail"
                    :src="actualImage.url"
                    :srcset="retinaThumb"
                    class="post-img-thumbnail"
                    :style="{width: actualImage.width + 'px', height: actualImage.height + 'px'}"
            />
            <span v-if="!thumbnail" v-html="attachment.embed.html"></span>
        </div>

    </figure>
</template>

<script>
    import $ from 'jquery'
    import Vue from 'vue';
    import UI from '../app/UI';
    import Session from '../services/Session'

    import ApiAttachment from '../services/Attachment';
    import ApiModeration from '../services/Moderation';

    export default {
        props: [ 'attachment', 'moderatable', 'noScroll' ],
        data() {
            return {
                thumbnail: true,
                isDeleting: false,
                isMarkingNsfw: false,
            }
        },
        computed: {
            actualImage() {
                return this.attachment.images[ this.thumbnail ? 'thumb_200px' : 'original'];
            },
            retinaThumb() {
                return this.thumbnail ? this.attachment.images['thumb_400px'].url + ' 2x' : null
            },
            showNsfwLabel() {
                if (!this.attachment.isNsfw) return false;
                if (this.moderatable) return true;
                if (Session.settings.showNsfw) return false;
                return this.thumbnail;
            },
            showNsfwBlur() {
                if (!this.attachment.isNsfw) return false;
                if (Session.settings.showNsfw) return false;
                return this.thumbnail;
            },
            isGif() {
                return this.attachment.images.original.name.substr(-3) == 'gif';
            }
        },
        methods: {
            onThumbnailClick(event, isThumbnail) {
                this.thumbnail = !this.thumbnail;
                this.$nextTick(() => {
                    const $img = $(this.$refs.image);

                    if (!isThumbnail && !this.attachment.embed) {
                        const maxSize = {
                            width: window.innerWidth - $img.offset().left - 40,
                            height: window.innerHeight - 20
                        };

                        const imgSize = {
                            width:  this.actualImage.width,
                            height: this.actualImage.height
                        };

                        const factor = Math.min(
                            maxSize.height / imgSize.height,
                            maxSize.width / imgSize.width
                        );

                        if (factor < 1) {
                            $img.width(imgSize.width * factor);
                            $img.height(imgSize.height * factor);
                        } else {
                            $img.width(imgSize.width);
                            $img.height(imgSize.height);
                        }
                    }
                    if (!this.noScroll) {
                        UI.scrollTo($img, -30);
                    }
                    this.$emit('opened', !this.thumbnail)
                });
            },
            onDeleteClick() {
                if (this.isDeleting) return;
                this.isDeleting = true;

                let apiCall;
                if (this.attachment.isPublished) {
                    if (this.attachment.isDeleted) {
                        apiCall = ApiModeration.restoreAttachment(this.attachment.id);
                    } else {
                        apiCall = ApiModeration.deleteAttachment(this.attachment.id);
                    }
                } else {
                    apiCall = ApiAttachment.cancel(this.attachment.id, this.attachment.token);
                }

                if (apiCall) {
                    apiCall.then(
                        response => {
                            if (response.data.ok) {
                                this.attachment.isDeleted = response.data.isDeleted;
                                this.$emit('delete', this.attachment.id, this.attachment.isDeleted);
                            }
                            this.isDeleting = false;
                        }
                    );
                }
            },
            onMarkNsfwClick() {
                if (this.isMarkingNsfw) return;
                this.isMarkingNsfw = true;

                let apiCall;
                if (this.attachment.isPublished) {
                    apiCall = ApiModeration.markNsfwAttachment(this.attachment.id, !this.attachment.isNsfw);
                } else {
                    apiCall = ApiAttachment.markNsfw(this.attachment.id, this.attachment.token, !this.attachment.isNsfw);
                }

                if (apiCall) {
                    apiCall.then(
                        response => {
                            if (response.data.ok) {
                                this.attachment.isNsfw = response.data.isNsfw;
                                this.$emit('markNsfw', this.attachment.id, this.attachment.isNsfw);
                            }
                            this.isMarkingNsfw = false;
                        }
                    )
                }
            }
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import '~assets/styles/_vars';

    .post-img {
        /*float: left;*/
        display: inline-block;
        margin-right: 5px;
        margin-bottom: 5px;
        margin-top: 5px;
        position: relative;
        overflow: hidden;

        a {
            min-width: 60px;
            min-height: 60px;
            display: inline-block;
        }

        figcaption {
            transition: margin-bottom 0.2s, opacity 0.2s;
            font-size: 70%;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            line-height: 1.2em;
            margin-bottom: -2.6em;
            padding: 0.1em;
            opacity: 0;
        }

        .post-img-buttons {
            position: absolute;
            top: 0;
            right: 0;
            margin-top: -18px;
            line-height: 18px;
            transition: margin-top 0.2s, opacity 0.2s;
            background: white;
            z-index: 1;

            .post-img-button {
                cursor: pointer;
                display: inline-block;
                text-align: center;
                width: 18px;
                height: 18px;
            }
        }

        .post-embed {
            position: relative;
            .post-embed-overlay {
                padding: 2px 5px;
                color: $color-white-dk;
                text-shadow: 1px 1px 1px $color-black, -1px 1px 1px $color-black, 1px -1px 1px $color-black, -1px -1px 1px $color-black;
                position: absolute;
                bottom: 0; right: 0; top: 0; left: 0;
            }
            .post-embed-play-btn {
                position: absolute;
                bottom: 0; right: 0;
                z-index: 5;
                cursor: pointer;
            }
        }

        &:hover {
            figcaption {
                margin-bottom: 0;
                opacity: 0.8;
            }
            .post-img-buttons {
                opacity: 0.8;
                margin-top: 0;
                i:hover {
                    color: $color-green;
                }
            }
        }

        .post-img-labels {
            position: absolute;
            left: 1px;
            top: 1px;
            font-weight: bold;
            font-size: 0.8em;
            .post-img-label {
                padding: 0 1px;
                line-height: 1em;
                background: rgba(255,255,255,0.25);
                border-radius: 2px;
                font-family: $font-family-monospace;
                z-index: 2;
            }
            .post-img-nsfw-label {
                color: $color-red;
                border: 1px solid $color-red;
            }
            .post-img-deleted-label {
                color: $color-red;
                border: 1px solid $color-red;
            }
            .post-img-gif-label {
                color: $color-green;
                border: 1px solid $color-green;
            }
        }

        &.post-img-nsfw {
            .post-img-thumbnail {
                opacity: 0.2;
                filter: blur(4px) grayscale(50%);
            }

            .post-embed-overlay {
                // dont cross NSFW badge
                padding-top: 1.5em;
            }
        }

        &.post-img-deleted {
            .post-img-thumbnail {
                opacity: 0.3;
            }
        }
    }
</style>