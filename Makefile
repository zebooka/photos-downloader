
all: exiftool composer tests install

exiftool:
	exiftool -ver

composer:
	composer -v install --no-dev && \
	COMPOSER_VENDOR_DIR="vendor-dev" composer -v install --dev

tests:
	./tests/run.sh

install:
	./build-phar.php
