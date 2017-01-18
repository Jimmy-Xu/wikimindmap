#!/bin/bash

docker rm -fv mywikimindmap

docker run -d --name mywikimindmap -e http_proxy=$http_proxy -e https_proxy=$https_proxy  \
  -p 80:80 -p 9090:9090 -v $PWD/public:/var/www/html/ xjimmyshcn/wikimindmap 

#docker run -d --name mywikimindmap -e http_proxy=$http_proxy -e https_proxy=$https_proxy  -p 80:80 xjimmyshcn/wikimindmap

