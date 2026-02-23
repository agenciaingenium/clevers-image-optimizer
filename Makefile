SHELL := /bin/bash

PLUGIN_SLUG ?= clevers-image-optimizer
PLUGIN_MAIN ?= clevers-image-optimizer.php
DIST_DIR ?= dist
SVN_OUTPUT ?= svn-dist
SVN_TAG ?=

.PHONY: help release-zip prepare-svn prepare-svn-no-tag

help:
	@echo "Targets disponibles:"
	@echo "  make release-zip            Genera ZIP de release en $(DIST_DIR)/"
	@echo "  make prepare-svn            Prepara estructura SVN en $(SVN_OUTPUT)/ (incluye tag)"
	@echo "  make prepare-svn-no-tag     Prepara solo trunk/ en $(SVN_OUTPUT)/"
	@echo ""
	@echo "Variables opcionales:"
	@echo "  DIST_DIR=dist-final"
	@echo "  SVN_OUTPUT=svn-dist-final"
	@echo "  SVN_TAG=0.3.0"
	@echo "  PLUGIN_SLUG=$(PLUGIN_SLUG)"

release-zip:
	@PLUGIN_SLUG="$(PLUGIN_SLUG)" PLUGIN_MAIN="$(PLUGIN_MAIN)" DIST_DIR="$(DIST_DIR)" ./bin/build-release.sh

prepare-svn:
	@if [[ -n "$(SVN_TAG)" ]]; then \
		./bin/prepare-svn.sh --output "$(SVN_OUTPUT)" --tag "$(SVN_TAG)"; \
	else \
		./bin/prepare-svn.sh --output "$(SVN_OUTPUT)"; \
	fi

prepare-svn-no-tag:
	@./bin/prepare-svn.sh --output "$(SVN_OUTPUT)" --no-tag
