wikimindmap
========================================

Search wikipedia and show result as freemind.

# run env
- Chrome 50
- Flash player 23

# usage
```
//build docker image
$ export http_proxy=http://x.x.x.x:8118; export https_proxy=http://x.x.x.x:8118
$ ./build.sh

//start docker container
$ ./run.sh

//open in chrome
http://<ip>/public/viewmap.php
```

# convert 繁体中文到简体中文

## prepare
```
$ wget https://releases.wikimedia.org/mediawiki/1.26/mediawiki-1.26.2.tar.gz
$ tar xzvf mediawiki-1.26.2.tar.gz

$ cd public
$ wget https://github.com/tszming/mediawiki-zhconverter/raw/master/mediawiki-zhconverter.inc.php
```

## use MediaWikiZhConverter::convert()
```
// vi public/getpages.php, add the following lines:
define("MEDIAWIKI_PATH", "/var/www/html/mediawiki-1.26.2");
require_once "mediawiki-zhconverter.inc.php";

$contents = MediaWikiZhConverter::convert($contents,"zh-cn","utf-8");
```


# compile visorFreemind.swf

## compile swf with mtasc(has issue)
```
$ sudo apt-get install -y mtasc
$ cd actionscript/MindMapBrowser
$ mv Source visorFreeMind

// comment the following lines:
 - trace()
 - Flashout.

$ mtasc visorFreeMind/Main.as -swf visorFreemind.swf -header 800:600:24 -version 8 -v
```

## compile swf with FlashDevelop 3.0.6 RTM
```
http://www.flashdevelop.org/community/viewtopic.php?t=5669
```

# other

- https://github.com/mphasize/noder
- https://github.com/ubunatic/mm2wiki
- https://github.com/ingee/freemind-webapp
