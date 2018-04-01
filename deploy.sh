#!/bin/sh
echo '=== Pulling changes ==='
git pull

FRONTEND_CHANGES=`git diff HEAD HEAD~1 --name-only | grep frontend | wc -l`
if [ "$FRONTEND_CHANGES" -ne "0" ]
then
    echo '=== Building frontend ==='
    cd ./frontend
    npm install
    npm run build
    cd ..
fi

echo '=== Building containers ==='
docker-compose build
docker-compose push

echo '=== Starting containers ==='
env $(cat .env | grep ^[A-Z] | xargs)  \
    GIT_HEAD_ID=`git rev-parse HEAD`   \
    docker stack deploy nc -c docker-compose.yml --with-registry-auth