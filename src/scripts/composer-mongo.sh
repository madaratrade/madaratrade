#!/usr/bin/env bash
set -euo pipefail

# Ensure the script is run as root
if [ "${EUID:-$(id -u)}" -ne 0 ]; then
  echo "Please run this script as root (sudo)."
  exit 1
fi

PROJECT_DIR="/root/madaratrade/src"
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR"

echo "==> Detecting active PHP version"
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
echo "PHP version detected: $PHP_VERSION"

echo "==> Configuring the compiled mongodb extension"
# Get the official PHP extension directory
EXT_DIR="$(php-config --extension-dir)"

if [ ! -f "$EXT_DIR/mongodb.so" ]; then
  echo "mongodb.so not found in $EXT_DIR. Installing via PECL..."
  apt-get update
  apt-get install -y php${PHP_VERSION}-dev php-pear build-essential pkg-config libssl-dev libcurl4-openssl-dev
  yes '' | pecl install mongodb
fi

# Enable the extension via mods-available
mkdir -p "/etc/php/$PHP_VERSION/mods-available"
echo "extension=mongodb.so" > "/etc/php/$PHP_VERSION/mods-available/mongodb.ini"

# Enable for CLI, FPM, and Apache
if command -v phpenmod >/dev/null 2>&1; then
  phpenmod mongodb
else
  echo "phpenmod not found, symlinking manually..."
  for sapi in cli fpm apache2; do
    if [ -d "/etc/php/$PHP_VERSION/$sapi/conf.d" ]; then
      ln -sf "/etc/php/$PHP_VERSION/mods-available/mongodb.ini" "/etc/php/$PHP_VERSION/$sapi/conf.d/20-mongodb.ini"
    fi
  done
fi

# Restart web services to apply changes
systemctl restart php${PHP_VERSION}-fpm 2>/dev/null || true
systemctl restart apache2 2>/dev/null || true

# Verify that PHP now successfully loads the module
echo "==> Verifying module activation"
if php -m | grep -qx mongodb; then
  echo "Success: mongodb extension is loaded!"
else
  echo "Error: PHP failed to load the mongodb extension."
  exit 1
fi

echo "==> Installing or updating Composer"
if ! command -v composer >/dev/null 2>&1; then
  EXPECTED_CHECKSUM="$(curl -fsSL https://composer.github.io/installer.sig)"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

  if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    echo "Composer installer checksum mismatch"
    rm -f composer-setup.php
    exit 1
  fi

  php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
  rm -f composer-setup.php
  echo "Composer installed successfully."
else
  echo "Composer is already available."
fi

echo "==> Checking for composer.json"
if [ ! -f "composer.json" ]; then
  cat > composer.json <<'EOF'
{
  "name": "madaratrade/project",
  "type": "project",
  "require": {}
}
EOF
  echo "Created fresh composer.json"
fi

echo "==> Requiring mongodb/mongodb library via Composer"
# Set environment variable to allow running Composer as root without warnings
export COMPOSER_ALLOW_SUPERUSER=1
composer require mongodb/mongodb --no-interaction

echo "==> Process complete! Installed version:"
composer show mongodb/mongodb | grep -E "versions|name"
