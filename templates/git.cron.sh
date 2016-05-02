#!/usr/bin/env bash
HOSTNAME=$( hostname )
GIT=$( which git )

# Commit changes from prod server back to git
if [[ -n $(git status -s) ]]; then
    $GIT add .
    $GIT commit -m "[ci skip] Added changes from $HOSTNAME"
    $GIT push origin master
fi
