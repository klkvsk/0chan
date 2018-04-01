docker volume create local-registry
docker run -d -p 5000:5000 --restart=always --name registry \
  -v local-registry:/var/lib/registry \
  registry:2