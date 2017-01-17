FROM eboraas/apache-php

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y php5-curl &&\
    apt-get clean && rm -rf /var/lib/apt/lists/*

