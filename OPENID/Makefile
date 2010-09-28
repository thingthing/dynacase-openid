appname = $(shell basename `pwd` | tr [a-z] [A-Z])

export appname

include $(utildir)/PubRule

SUBDIR = Images

DISTFILES = Makefile  $(appname).app $(appname)_init.php.in
