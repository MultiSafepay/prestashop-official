#!/usr/bin/env bash

# Exit if any command fails
set -eo pipefail

RELEASE_VERSION=$1
FILENAME_PREFIX="Plugin_PrestaShop_"
FOLDER_PREFIX="multisafepayofficial"
RELEASE_FOLDER=".dist"

# If tag is not supplied, latest tag is used
if [ -z "$RELEASE_VERSION" ]
then
  RELEASE_VERSION=$(git describe --tags --abbrev=0)
fi

# Remove old folder
rm -rf "$RELEASE_FOLDER"

# Create release
mkdir "$RELEASE_FOLDER"
git archive --format zip -9 --prefix="$FOLDER_PREFIX"/ --output "$RELEASE_FOLDER"/"$FILENAME_PREFIX""$RELEASE_VERSION".zip "$RELEASE_VERSION"

# Unzip to download dependencies
cd "$RELEASE_FOLDER"
unzip "$FILENAME_PREFIX""$RELEASE_VERSION".zip

# Remove the archive zip file
rm "$FILENAME_PREFIX""$RELEASE_VERSION".zip

# Remove all the .gitkeep files
rm "$FOLDER_PREFIX"/*/.gitkeep

# Install composer with dev dependencies
composer install --working-dir="$FOLDER_PREFIX"

# Generate index.php file in all directories according with PrestaShop security guidelines.
"$FOLDER_PREFIX"/vendor/bin/autoindex --exclude="$FOLDER_PREFIX"/vendor/amphp,"$FOLDER_PREFIX"/vendor/doctrine,"$FOLDER_PREFIX"/vendor/friendsofphp,"$FOLDER_PREFIX"/vendor/gitonomy,"$FOLDER_PREFIX"/vendor/monolog,"$FOLDER_PREFIX"/vendor/myclabs,"$FOLDER_PREFIX"/vendor/nikic,"$FOLDER_PREFIX"/vendor/ondram,"$FOLDER_PREFIX"/vendor/opis,"$FOLDER_PREFIX"/vendor/phar-io,"$FOLDER_PREFIX"/vendor/php-cs-fixer,"$FOLDER_PREFIX"/vendor/phpdocumentor,"$FOLDER_PREFIX"/vendor/phpro,"$FOLDER_PREFIX"/vendor/phpspec,"$FOLDER_PREFIX"/vendor/phpstan,"$FOLDER_PREFIX"/vendor/phpunit,"$FOLDER_PREFIX"/vendor/prestashop,"$FOLDER_PREFIX"/vendor/sebastian,"$FOLDER_PREFIX"/vendor/seld,"$FOLDER_PREFIX"/vendor/squizlabs,"$FOLDER_PREFIX"/vendor/theseer,"$FOLDER_PREFIX"/vendor/webmozart

# Update composer dependencies to uninstall dev dependencies
composer update --no-dev --working-dir="$FOLDER_PREFIX"

# Zip everything
zip -9 -r "$FILENAME_PREFIX""$RELEASE_VERSION".zip "$FOLDER_PREFIX" -x "$FOLDER_PREFIX""/composer.json" -x "$FOLDER_PREFIX""/composer.lock" -x "$FOLDER_PREFIX""/.wordpress-org/*" -x "$FOLDER_PREFIX""/.distignore"
