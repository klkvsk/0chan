FROM ubuntu:16.04

RUN apt-get update && apt-get install -y nginx nginx-extras

ADD deploy/nginx.conf /nginx.conf
ADD dist /dist

EXPOSE 80

CMD [ "nginx", "-c", "/nginx.conf" ]