# ============================================
# $Id: Makefile,v 1.3 2003/09/17 15:33:04 eric Exp $
# ============================================

# get the new appname (basename in uppercase)
appname = $(shell basename `pwd` | tr [a-z] [A-Z])

export appname

include $(utildir)/PubRule
publish:
	mkdir -p $(pubdir)/$(appname)/Layout
	cp -f *.xml $(pubdir)/$(appname)/Layout
	cp -f *.php $(pubdir)/$(appname)
