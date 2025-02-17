MODULE := $(notdir $(CURDIR))
VERSION := $(shell php -r 'echo parse_ini_file("config/module.ini")["version"];')
ZIP := ${MODULE}-${VERSION}.zip

dist: ${ZIP}

${ZIP}:
	git archive -o $@ --prefix=${MODULE}/ v${VERSION}

.PHONY: ${ZIP}
