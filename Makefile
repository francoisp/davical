#!/usr/bin/make -f
#

package := davical
majorversion := $(shell sed -n 's:\([0-9\.]*\)[-a-f0-9-]*:\1:p' VERSION)
gitrev := 0
version := $(majorversion)
issnapshot := 0
snapshot : gitrev = $(shell git rev-parse --short HEAD)
snapshot : version = $(majorversion)-git$(gitrev)
snapshot : issnapshot = 1

.PHONY: nodocs
nodocs: htdocs/always.php built-locale

.PHONY: all
all: htdocs/always.php built-docs built-locale

built-docs: docs/phpdoc.ini docs/Doxyfile inc/*.php docs/translation.rst
	doxygen docs/Doxyfile || apigen generate --quiet --title=DAViCal --todo --tree --deprecated -s inc --exclude caldav-client.php -d docs/api || phpdoc -c docs/phpdoc.ini || echo "NOTICE: Failed to build API docs"
	rst2pdf docs/translation.rst || echo "NOTICE: Failed to build ReST docs"
	touch $@

built-locale: po/*.po
	for LOCALE in `ls po/*.po | cut -f2 -d/ | cut -f1 -d.` ; do \
	    [ "$${LOCALE}" = "en" ] && continue; \
	    mkdir -p locale/$${LOCALE}/LC_MESSAGES; \
	    msgfmt po/$${LOCALE}.po -o locale/$${LOCALE}/LC_MESSAGES/davical.mo; \
	done
	touch $@

#
# Insert the current version number into always.php
#
htdocs/always.php: inc/always.php.in scripts/build-always.sh VERSION dba/davical.sql
	scripts/build-always.sh <$< >$@

#
# recreate translations
#
.PHONY: translations
translations:
	scripts/po/rebuild-translations.sh

#
# Build a release .tar.gz file in the directory above us
#
.PHONY: release
release: built-docs VERSION
	-ln -s . $(package)-$(version)
	sed 's:@@VERSION@@:$(majorversion):' davical.spec.in | \
	sed 's:@@ISSNAPSHOT@@:$(issnapshot):' | \
	sed 's:@@GITREV@@:$(gitrev):' > davical.spec
	echo "git ls-files |grep -v '.git'|sed -e s:^:$(package)-$(version)/:"
	tar czf ../$(package)-$(version).tar.gz \
	    --no-recursion --dereference $(package)-$(version) \
	    $(shell git ls-files |grep -v '.git'|sed -e s:^:$(package)-$(version)/:) \
	    $(shell find $(package)-$(version)/docs/api/ ! -name "phpdoc.ini" ) \
	    davical.spec
	rm $(package)-$(version)

.PHONY: snapshot
snapshot: release

.PHONY: test
test:
	@for PHP in htdocs/*.php inc/*.php; do php -l $${PHP} | grep -v 'No syntax errors detected' >> test-syntax; done; \
	    if [ -s test-syntax ]; then \
	    	cat test-syntax >&2; \
		rm test-syntax; \
		exit 1; \
	    else \
		rm test-syntax; \
		exit 0; \
	   fi



.PHONY: clean
clean:
	rm -f built-docs built-locale
	rm -rf docs/api locale
	-rm -rf testing/tests/*/diffs testing/tests/*/results testing/tests/*/initial.dbdump
	-find . -name "*~" -delete
	rm -f docs/translation.pdf
	rm -f davical.spec
