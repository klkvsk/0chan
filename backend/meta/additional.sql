ALTER TABLE user_identity ADD CONSTRAINT user_identity_token_key UNIQUE(address);

CREATE INDEX board_popularity ON board (popularity DESC NULLS LAST);