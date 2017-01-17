#!/bin/bash

docker run --name mywikimindmap -e http_proxy=$http_proxy -e https_proxy=$https_proxy  -p 80:80 -p 443:443 -v $PWD:/var/www/html/ -d xjimmyshcn/wikimindmap

