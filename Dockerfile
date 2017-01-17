FROM eboraas/apache-php

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y php5-curl &&\
    apt-get clean && rm -rf /var/lib/apt/lists/*

ADD public /var/www/html/
RUN sed -i 's/DirectoryIndex.*/DirectoryIndex index.php/g' /etc/apache2/mods-enabled/dir.conf
RUN sed -i 's/DirectoryIndex.*/DirectoryIndex index.php/g' /etc/apache2/mods-available/dir.conf
