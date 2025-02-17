#!/bin/sh

cd "$(dirname -- "$(dirname -- "$(readlink -f -- "$0")")")/documentation"
pip install -r requirements.txt
languages="en fr"
for language in $languages; do
    make -e BUILDDIR=_build/$language SPHINXOPTS="-D language=$language" clean html
done