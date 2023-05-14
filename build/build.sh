#!/bin/bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
LATEST_TAG=$( git describe --tags --abbrev=0 )

$SCRIPT_DIR/../unload app:build --build-version=$LATEST_TAG

echo "Creating static binaries..."

mkdir -p $SCRIPT_DIR/../builds/linux
mkdir -p $SCRIPT_DIR/../builds/macos

cat $SCRIPT_DIR/linux.sfx $SCRIPT_DIR/../builds/unload > $SCRIPT_DIR/../builds/linux/unload
cat $SCRIPT_DIR/macos.sfx $SCRIPT_DIR/../builds/unload > $SCRIPT_DIR/../builds/macos/unload

echo "Build complete!"