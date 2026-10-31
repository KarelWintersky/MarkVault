#!/usr/bin/make
# SHELL = bash
PACKAGE_NAME = markvault
INSTALL_DIR = markvault
PATH_PROJECT = $(DESTDIR)/opt/$(INSTALL_DIR)
PATH_PUBLIC = $(PATH_PROJECT)/public
DEVELOPER_EMAIL = "karel@wintersky.ru"
DEVELOPER_NAME = "Karel Wintersky"

.PHONY: help install update build setup_env sync_db dchv dchr clear_smarty_cache clear_nginx_cache clear_redis_cache

help:
	@perl -e '$(HELP_ACTION)' $(MAKEFILE_LIST)

local:
	@php -S 127.0.0.1:8080 index.php

phar:		##@build Build PHAR
	@echo Building PHAR
	bash build_phar.sh

update:		##@build Update project from GIT
	@echo Updating project from GIT
	git pull --no-rebase

version:
	@VERSION_DIR=vendor; \
	GIT_TAG=$$(git describe --tags --always 2>/dev/null || echo "0.0.0"); \
	GIT_HASH=$$(git rev-parse --short HEAD 2>/dev/null || echo "unknown"); \
	GIT_SUBJECT=$$(git log --oneline --format=%B -n 1 HEAD 2>/dev/null | head -n 1 || echo "no commit"); \
	GIT_DATE=$$(git log --oneline --format="%at" -n 1 HEAD 2>/dev/null | xargs -I{} date -d @{} +%Y-%m-%d 2>/dev/null || echo "unknown"); \
	VERSION_JSON=$$(jq -n \
		--arg tag     "$${GIT_TAG}" \
		--arg hash    "$${GIT_HASH}" \
		--arg subject "$${GIT_SUBJECT}" \
		--arg date    "$${GIT_DATE}" \
		'{tag: $$tag, hash: $$hash, subject: $$subject, date: $$date'}); \
	echo "$${VERSION_JSON}" > $${VERSION_DIR}/_version.json;

# ------------------------------------------------
# Add the following 'help' target to your makefile, add help text after each target name starting with '\#\#'
# A category can be added with @category
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)
HELP_ACTION = \
	%help; while(<>) { push @{$$help{$$2 // 'options'}}, [$$1, $$3] if /^([a-zA-Z\-_]+)\s*:.*\#\#(?:@([a-zA-Z\-]+))?\s(.*)$$/ }; \
	print "usage: make [target]\n\n"; for (sort keys %help) { print "${WHITE}$$_:${RESET}\n"; \
	for (@{$$help{$$_}}) { $$sep = " " x (32 - length $$_->[0]); print "  ${YELLOW}$$_->[0]${RESET}$$sep${GREEN}$$_->[1]${RESET}\n"; }; \
	print "\n"; }

# -eof-

