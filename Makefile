# Automatically creates a fresh build for distribution with other modules or
# for getting started with Spikkl without composer
spikkl-php-client.zip: php-scoper.phar
	rm -rf build/*

	# Install all dependencies, than prefix everything with scoper. Finally, dump autoloader
	# again to update the autoloader with the new class names.
	composer install --no-dev --no-scripts --no-suggest
	php php-scoper.phar add-prefix --force
	composer dump-autoload --working-dir build --classmap-authoritative

	# Move the autoload files. We have to use the scoper one to load the aliasses
	# but we want to load the normal filename. Flip them around.
	mv build/vendor/autoload.php build/vendor/composer-autoload.php
	sed -i -e 's/autoload.php/composer-autoload.php/g' build/vendor/scoper-autoload.php
	mv build/vendor/scoper-autoload.php build/vendor/autoload.php

	# Create a zip file with all build files.
	cd build; zip -r ../spikkl-php-client.zip src examples vendor composer.json LICENSE README.md

php-scoper.phar:
	rm -f php-scoper.phar
	rm -f php-scoper.phar.pubkey

	wget -q https://github.com/humbug/php-scoper/releases/download/0.9.2/php-scoper.phar
	wget -q https://github.com/humbug/php-scoper/releases/download/0.9.2/php-scoper.phar.pubkey