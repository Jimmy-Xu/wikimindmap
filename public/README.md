Debug wikimindmap with Intellij IDEA
=====================================


# dependency

- OS: Windows 10
- PHP: 7.1
  - install to c:\php
  - download and copy xdebug to c:\php\ext
- Intellij IDEA 2016.1.2
  - install php plugin


# config php.ini
```
extension=php_curl.dll
extension=php_mbstring.dll


[xdebug]
xdebug.remote_enable = 1
xdebug.remote_connect_back = 1
xdebug.remote_port = 9090
xdebug.remote_handler = "dbgp"
zend_extension = "c:\php\ext\php_xdebug.dll"
```

