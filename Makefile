PACKAGE = freedom-openid
VERSION = 0.0.0
utildir=/home/thing-_n/anakeen/devtools/
pubdir = /var/www/freedom3
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
