
all: hooks exiftool composer test install

hooks:
	test ! -d .git || cp .git-pre-commit .git/hooks/pre-commit && chmod +x .git/hooks/pre-commit

exiftool:
	exiftool -ver

composer:
	composer -v install --no-dev && \
	COMPOSER_VENDOR_DIR="vendor-dev" composer -v install --dev

test:
	./tests/run.sh

install:
	./build-phar.php
