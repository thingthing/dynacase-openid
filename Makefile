PACKAGE = dynacase-openid
VERSION = 0.0.2
utildir=/home/nicolas/anakeen/devtools/
pubdir = /usr/share/what
srcdir = .
appname = OPENID
applib = WHAT
export pubdir utildir appname applib

TAR = tar
GZIP_ENV = --best

SUBDIR = Class OPENID Action Services

pages_not_xml = info.xml

include $(utildir)/PubRule

DISTFILES += $(SUBDIR) \
            RELEASE VERSION 

$(pubdir)/$(appname): 
	mkdir -p $(pubdir)/$(appname)

$(pubdir)/$(appname)/.htaccess: htaccess $(pubdir)/$(appname)
	cp $< $@

publish: $(pubdir)/$(appname)/.htaccess
