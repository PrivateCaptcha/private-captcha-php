PC_API_KEY ?=
COMPOSER ?= $(shell which composer 2>/dev/null || echo composer)
PHP ?= $(shell which php 2>/dev/null || echo php)

# Run all tests
test:
	@env PC_API_KEY=$(PC_API_KEY) $(COMPOSER) run-script test

# Run tests with coverage
test-coverage:
	@env PC_API_KEY=$(PC_API_KEY) XDEBUG_MODE=coverage $(COMPOSER) run-script test:coverage

# Install dependencies
install:
	$(COMPOSER) install --no-dev --optimize-autoloader

# Install development dependencies
install-dev:
	$(COMPOSER) install

# Format code
format:
	$(COMPOSER) run-script format

# Lint code
lint:
	$(COMPOSER) run-script lint

# Static analysis
analyze:
	$(COMPOSER) run-script analyze

# Clean up build artifacts
clean:
	rm -rf vendor/
	rm -rf build/
	rm -rf coverage/
	rm -f .phpunit.result.cache

# Validate composer.json and check for security issues
build: clean
	$(COMPOSER) validate --strict
	$(COMPOSER) audit

# Update dependencies
update:
	$(COMPOSER) update

# Show outdated packages
outdated:
	$(COMPOSER) outdated --direct
