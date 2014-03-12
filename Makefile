
all: exiftool composer test install

exiftool:
	exiftool -ver

composer:
	composer -v install --no-dev && \
	COMPOSER_VENDOR_DIR="vendor-dev" composer -v install --dev

test:
	./tests/run.sh

install:
	./build-phar.php
