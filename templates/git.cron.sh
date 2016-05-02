#!/usr/bin/env bash
HOSTNAME=$( hostname )

# Commit changes from prod server back to git
if [[ -n $(git status -s) ]]; then
    git add .
    git commit -m "[ci skip] Added changes from $HOSTNAME"
    git push origin master
fi
