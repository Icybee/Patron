composer.phar:
	@echo "Installing composer..."
	@curl -sS https://getcomposer.org/installer | php

vendor: composer.phar
	@php composer.phar install

update: composer.phar
	@php composer.phar update

autoload: vendor
	@php composer.phar dump-autoload

test: vendor
	@phpunit

doc: vendor
	@mkdir -p "docs"

	@apigen \
	--source ./ \
	--destination docs/ --title Patron \
	--exclude "*/tests/*" \
	--exclude "*/composer/*" \
	--template-config /usr/share/php/data/ApiGen/templates/bootstrap/config.neon

clean:
	@rm -fR docs
	@rm -fR vendor
	@rm -f composer.lock
	@rm -f composer.phar