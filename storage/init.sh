#!/usr/bin/env bash
sed -i 's/__SALT__/'"${SALT}"'/' /app/config/nginx.conf
supervisord -c /app/config/supervisord.conf
