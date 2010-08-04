include $(utildir)/PubRule

pages_fdl = $(patsubst %.php,$(pubdir)/$(applib)/%.php,$(wildcard Class*.php))

$(pubdir)/$(applib):
	mkdir -p $@

$(pubdir)/$(applib)/%.php: %.php $(pubdir)/$(applib)
	ln -sf ../$(appname)/$< $@

publish: $(pubdir)/$(applib) $(pages_fdl)