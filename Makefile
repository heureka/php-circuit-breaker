.PHONY: test build update_translations

build: vendor

vendor:
	composer install

test: vendor
	$(CURDIR)/vendor/bin/phpunit $(CURDIR)/tests

clean:
	rm -rf $(CURDIR)/vendor
	rm -f $(CURDIR)/composer.lock
