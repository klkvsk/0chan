FROM ubuntu:16.04

RUN apt-get update && apt-get install -y wget curl git
RUN curl -sL https://deb.nodesource.com/setup_7.x | bash -
RUN apt-get install -y nodejs build-essential python supervisor nginx nginx-extras
RUN npm -g i pm2 nodemon

RUN mkdir /app
WORKDIR /app
ADD ./package.json ./
RUN npm install

ADD ./config  ./config
ADD ./src     ./src
ADD ./init.sh ./init.sh

EXPOSE 80
EXPOSE 228
EXPOSE 53

CMD bash init.sh