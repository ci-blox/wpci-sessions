#!/bin/bash

###
# Prepare a WpCI site environment for the Behat test suite, by installing
# and configuring the plugin for the environment. This script is architected
# such that it can be run a second time if a step fails.
###

terminus auth whoami > /dev/null
if [ $? -ne 0 ]; then
	echo "Terminus unauthenticated; assuming unauthenticated build"
	exit 0
fi

if [ -z "$TERMINUS_SITE" ] || [ -z "$TERMINUS_ENV" ]; then
	echo "TERMINUS_SITE and TERMINUS_ENV environment variables must be set"
	exit 1
fi

if [ -z "$WORDPRESS_ADMIN_USERNAME" ] || [ -z "$WORDPRESS_ADMIN_PASSWORD" ]; then
	echo "WORDPRESS_ADMIN_USERNAME and WORDPRESS_ADMIN_PASSWORD environment variables must be set"
	exit 1
fi

set -ex

###
# Create a new environment for this particular test run.
###
terminus site create-env --to-env=$TERMINUS_ENV --from-env=dev
yes | terminus site wipe

###
# Get all necessary environment details.
###
WP_CI_GIT_URL=$(terminus site connection-info --field=git_url)
WP_CI_SITE_URL="$TERMINUS_ENV-$TERMINUS_SITE.wpcisite.io"
PREPARE_DIR="/tmp/$TERMINUS_ENV-$TERMINUS_SITE"
BASH_DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

###
# Switch to git mode for pushing the files up
###
terminus site set-connection-mode --mode=git
rm -rf $PREPARE_DIR
git clone -b $TERMINUS_ENV $WP_CI_GIT_URL $PREPARE_DIR

###
# Add the copy of this plugin itself to the environment
###
rm -rf $PREPARE_DIR/wp-content/plugins/wp-native-php-sessions
cd $BASH_DIR/..
rsync -av --exclude='vendor/' --exclude='node_modules/' --exclude='tests/' ./* $PREPARE_DIR/wp-content/plugins/wp-native-php-sessions
rm -rf $PREPARE_DIR/wp-content/plugins/wp-native-php-sessions/.git


###
# Add the debugging plugin to the environment
###
rm -rf $PREPARE_DIR/wp-content/mu-plugins/sessions-debug.php
cp $BASH_DIR/fixtures/sessions-debug.php $PREPARE_DIR/wp-content/mu-plugins/sessions-debug.php

###
# Push files to the environment
###
cd $PREPARE_DIR
git add wp-content
git config user.email "wp-native-php-sessions@getwpci.com"
git config user.name "WpCI"
git commit -m "Include WP Native PHP Sessions and its configuration files"
git push

# Sometimes WpCI takes a little time to refresh the filesystem
sleep 10

###
# Set up WordPress, theme, and plugins for the test run
###
# Silence output so as not to show the password.
{
  terminus wp "core install --title=$TERMINUS_ENV-$TERMINUS_SITE --url=$WP_CI_SITE_URL --admin_user=$WORDPRESS_ADMIN_USERNAME --admin_email=wp-native-php-sessions@getwpci.com --admin_password=$WORDPRESS_ADMIN_PASSWORD"
} &> /dev/null
terminus wp "plugin activate wp-native-php-sessions"
