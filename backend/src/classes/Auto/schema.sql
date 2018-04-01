CREATE SEQUENCE "user_id";
CREATE TABLE "user" (
    "id" BIGINT NOT NULL default nextval('user_id'),
    "create_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "login" CHARACTER VARYING(255) NOT NULL,
    "password" CHARACTER VARYING(64) NOT NULL,
    "role_id" BIGINT NOT NULL,
    "show_nsfw" BOOLEAN NOT NULL DEFAULT FALSE,
    "tree_view" BOOLEAN NOT NULL DEFAULT FALSE,
    "view_deleted" BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "ban_id";
CREATE TABLE "ban" (
    "id" BIGINT NOT NULL default nextval('ban_id'),
    "user_id" BIGINT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "ip_hash" CHARACTER VARYING(64) NULL,
    "board_id" BIGINT NOT NULL REFERENCES "board"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "banned_by_id" BIGINT NOT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "banned_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "banned_till" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "unbanned_by_id" BIGINT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "unbanned_at" TIMESTAMP WITHOUT TIME ZONE NULL,
    "post_id" BIGINT NULL REFERENCES "post"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "reason" TEXT NOT NULL,
    "appeal" TEXT NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "user_identity_id";
CREATE TABLE "user_identity" (
    "id" BIGINT NOT NULL default nextval('user_identity_id'),
    "user_id" BIGINT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "address" CHARACTER VARYING(32) NOT NULL,
    "name" CHARACTER VARYING(32) NOT NULL,
    "deleted" BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "favourite_board_id";
CREATE TABLE "favourite_board" (
    "id" BIGINT NOT NULL default nextval('favourite_board_id'),
    "create_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "user_id" BIGINT NOT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "board_id" BIGINT NOT NULL REFERENCES "board"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "board_moderator_id";
CREATE TABLE "board_moderator" (
    "id" BIGINT NOT NULL default nextval('board_moderator_id'),
    "created_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "user_id" BIGINT NOT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "board_id" BIGINT NOT NULL REFERENCES "board"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "initiator_id" BIGINT NOT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "board_id";
CREATE TABLE "board" (
    "id" BIGINT NOT NULL default nextval('board_id'),
    "dir" CHARACTER VARYING(16) NOT NULL,
    "name" CHARACTER VARYING(255) NOT NULL,
    "description" TEXT NULL,
    "hidden" BOOLEAN NOT NULL DEFAULT FALSE,
    "nsfw" BOOLEAN NOT NULL DEFAULT FALSE,
    "block_ru" BOOLEAN NOT NULL DEFAULT FALSE,
    "create_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "owner_id" BIGINT NOT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "deleted" BOOLEAN NOT NULL DEFAULT FALSE,
    "deleted_at" TIMESTAMP WITHOUT TIME ZONE NULL,
    "popularity" INTEGER NULL DEFAULT '0',
    "bump_limit" INTEGER NOT NULL DEFAULT '500',
    "thread_limit" INTEGER NOT NULL DEFAULT '100',
    PRIMARY KEY("id")
);


CREATE SEQUENCE "thread_id";
CREATE TABLE "thread" (
    "id" BIGINT NOT NULL default nextval('thread_id'),
    "board_id" BIGINT NOT NULL REFERENCES "board"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "create_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "update_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "purged_at" TIMESTAMP WITHOUT TIME ZONE NULL,
    "deleted" BOOLEAN NOT NULL DEFAULT FALSE,
    "deleted_at" TIMESTAMP WITHOUT TIME ZONE NULL,
    "sticky" BOOLEAN NOT NULL DEFAULT FALSE,
    "locked" BOOLEAN NOT NULL DEFAULT FALSE,
    "bump_limit_reached" BOOLEAN NOT NULL DEFAULT FALSE,
    "popularity" INTEGER NULL DEFAULT '0',
    PRIMARY KEY("id")
);


CREATE SEQUENCE "post_id";
CREATE TABLE "post" (
    "id" BIGINT NOT NULL default nextval('post_id'),
    "thread_id" BIGINT NOT NULL REFERENCES "thread"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "parent_id" BIGINT NULL REFERENCES "post"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "user_id" BIGINT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "ip_hash" CHARACTER VARYING(64) NULL,
    "create_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "deleted" BOOLEAN NOT NULL DEFAULT FALSE,
    "message" CHARACTER VARYING(9001) NULL,
    "approved" BOOLEAN NOT NULL DEFAULT FALSE,
    "banned" BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "post_reference_id";
CREATE TABLE "post_reference" (
    "id" BIGINT NOT NULL default nextval('post_reference_id'),
    "referenced_by_id" BIGINT NOT NULL REFERENCES "post"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "references_to_id" BIGINT NOT NULL REFERENCES "post"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "attachment_id";
CREATE TABLE "attachment" (
    "id" BIGINT NOT NULL default nextval('attachment_id'),
    "create_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "post_id" BIGINT NULL REFERENCES "post"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "publish_token" CHARACTER VARYING(32) NOT NULL,
    "published" BOOLEAN NOT NULL DEFAULT FALSE,
    "deleted" BOOLEAN NOT NULL DEFAULT FALSE,
    "deleted_at" TIMESTAMP WITHOUT TIME ZONE NULL,
    "nsfw" BOOLEAN NULL DEFAULT FALSE,
    "embed_id" BIGINT NULL REFERENCES "attachment_embed"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "attachment_image_id";
CREATE TABLE "attachment_image" (
    "id" BIGINT NOT NULL default nextval('attachment_image_id'),
    "role_id" BIGINT NULL,
    "attachment_id" BIGINT NOT NULL REFERENCES "attachment"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "server" CHARACTER VARYING(64) NULL,
    "filename" CHARACTER VARYING(64) NOT NULL,
    "file_size" INTEGER NOT NULL,
    "width" INTEGER NOT NULL,
    "height" INTEGER NOT NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "storage_trash_id";
CREATE TABLE "storage_trash" (
    "id" BIGINT NOT NULL default nextval('storage_trash_id'),
    "server" CHARACTER VARYING(64) NOT NULL,
    "filename" CHARACTER VARYING(64) NOT NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "attachment_embed_id";
CREATE TABLE "attachment_embed" (
    "id" BIGINT NOT NULL default nextval('attachment_embed_id'),
    "service_id" BIGINT NOT NULL,
    "embed_id" CHARACTER VARYING(255) NOT NULL,
    "title" CHARACTER VARYING(255) NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "post_report_id";
CREATE TABLE "post_report" (
    "id" BIGINT NOT NULL default nextval('post_report_id'),
    "post_id" BIGINT NOT NULL REFERENCES "post"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "reason" CHARACTER VARYING(1000) NOT NULL,
    "reported_by_id" BIGINT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "reported_by_ip_hash" CHARACTER VARYING(64) NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "dialog_id";
CREATE TABLE "dialog" (
    "id" BIGINT NOT NULL default nextval('dialog_id'),
    "as_id" BIGINT NOT NULL REFERENCES "user_identity"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "with_id" BIGINT NOT NULL REFERENCES "user_identity"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "created_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "updated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "dialog_message_id";
CREATE TABLE "dialog_message" (
    "id" BIGINT NOT NULL default nextval('dialog_message_id'),
    "from_id" BIGINT NOT NULL REFERENCES "user_identity"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "to_id" BIGINT NOT NULL REFERENCES "user_identity"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "text" CHARACTER VARYING(2000) NOT NULL,
    "read" BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "board_stats_hourly_id";
CREATE TABLE "board_stats_hourly" (
    "id" BIGINT NOT NULL default nextval('board_stats_hourly_id'),
    "board_id" BIGINT NOT NULL,
    "threads_active" INTEGER NULL DEFAULT '0',
    "threads_new" INTEGER NULL DEFAULT '0',
    "posts" INTEGER NULL DEFAULT '0',
    "unique_posters" INTEGER NULL DEFAULT '0',
    "hour" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "board_stats_daily_id";
CREATE TABLE "board_stats_daily" (
    "id" BIGINT NOT NULL default nextval('board_stats_daily_id'),
    "board_id" BIGINT NOT NULL,
    "threads_active" INTEGER NULL DEFAULT '0',
    "threads_new" INTEGER NULL DEFAULT '0',
    "posts" INTEGER NULL DEFAULT '0',
    "unique_posters" INTEGER NULL DEFAULT '0',
    "date" DATE NOT NULL,
    PRIMARY KEY("id")
);


CREATE SEQUENCE "moderator_log_id";
CREATE TABLE "moderator_log" (
    "id" BIGINT NOT NULL default nextval('moderator_log_id'),
    "board_id" BIGINT NOT NULL REFERENCES "board"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "event_date" TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    "event_user_id" BIGINT NOT NULL REFERENCES "user"("id") ON DELETE RESTRICT ON UPDATE CASCADE,
    "event_type_id" BIGINT NOT NULL,
    "message" CHARACTER VARYING(1000) NULL,
    "ban_id" BIGINT NULL,
    "thread_id" INTEGER NULL,
    "thread_title" CHARACTER VARYING(255) NULL,
    "post_id" INTEGER NULL,
    "attachment_id" INTEGER NULL,
    "user_id" BIGINT NULL,
    "property_name" CHARACTER VARYING(255) NULL,
    "old_value" CHARACTER VARYING(255) NULL,
    "new_value" CHARACTER VARYING(255) NULL,
    PRIMARY KEY("id")
);


CREATE TABLE "user_thread" (
    "thread_id" BIGINT NOT NULL,
    "user_id" BIGINT NOT NULL,
    UNIQUE("thread_id", "user_id")
);


CREATE TABLE "dialog_dialog_message" (
    "dialog_message_id" BIGINT NOT NULL,
    "dialog_id" BIGINT NOT NULL,
    UNIQUE("dialog_message_id", "dialog_id")
);


CREATE TABLE "dialog_message_dialog" (
    "dialog_id" BIGINT NOT NULL,
    "dialog_message_id" BIGINT NOT NULL,
    UNIQUE("dialog_id", "dialog_message_id")
);
