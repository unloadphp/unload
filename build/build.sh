#!/bin/bash

composer install --no-dev --classmap-authoritative --optimize-autoloader

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
LATEST_TAG=$( git describe --tags --abbrev=0 )

$SCRIPT_DIR/../unload app:build --build-version=$LATEST_TAG

echo "Creating static binaries..."

cat $SCRIPT_DIR/linux.sfx $SCRIPT_DIR/../builds/unload > $SCRIPT_DIR/../builds/unload-$LATEST_TAG-linux
cat $SCRIPT_DIR/macos.sfx $SCRIPT_DIR/../builds/unload > $SCRIPT_DIR/../builds/unload-$LATEST_TAG-macos

echo "Build complete!"
