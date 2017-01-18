FROM eboraas/apache-php

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y php5-curl &&\
    apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y php5-xdebug &&\
    apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y --force-yes vim &&\
    apt-get clean && rm -rf /var/lib/apt/lists/*

EXPOSE 80
EXPOSE 443
EXPOSE 9090

ADD public /var/www/html/
RUN sed -i 's/DirectoryIndex.*/DirectoryIndex viewmap.php/g' /etc/apache2/mods-enabled/dir.conf
RUN sed -i 's/DirectoryIndex.*/DirectoryIndex viewmap.php/g' /etc/apache2/mods-available/dir.conf
