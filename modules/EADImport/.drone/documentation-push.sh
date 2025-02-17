#!/bin/sh

unset GIT_AUTHOR_NAME
unset GIT_AUTHOR_EMAIL
unset GIT_AUTHOR_DATE
unset GIT_COMMITTER_NAME
unset GIT_COMMITTER_EMAIL
unset GIT_COMMITTER_DATE

git config --global user.email "drone@biblibre.com"
git config --global user.name "Drone CI"

mkdir -p ~/.ssh
printenv GH_DEPLOY_KEY > ~/.ssh/deploy_key
chmod 600 ~/.ssh/deploy_key
cat > ~/.ssh/config << 'CONFIG'
Host github.com
User git
IdentityFile ~/.ssh/deploy_key
StrictHostKeyChecking accept-new
CONFIG

cd "$(mktemp -d)"
git clone --branch gh-pages git@github.com:biblibre/omeka-s-module-EADImport.git .

languages="en fr"
for language in $languages; do
    rm -rf $language
    cp -r "$DRONE_WORKSPACE/documentation/_build/$language/html" $language
    git add $language
done
git commit -m "Drone build: $DRONE_BUILD_NUMBER" -m "Triggered-by: $DRONE_COMMIT_SHA"

git push origin gh-pages