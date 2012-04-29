#!/bin/bash
# set -o nounset # exit on use of an uninitialised variable, same as -u
# set -o errexit # exit on all and any errors, same as -e


DIR="$( cd "$( dirname "$0" )" && pwd )"	# Current script directory
SUBJECT="directory_list_theming.conf"
APACHE_CONF_DIR="/etc/apache2/sites-available"

sudo ln -s "$DIR/$SUBJECT" "$APACHE_CONF_DIR/$SUBJECT";

#@TODO: Take parameters so we can also install to other directories instead of only doing the core install to apache conf
#  create symlink to the `Directory_Listing_Theme` directory from  the directory we want to add the functionality to
# append/include the `directory_list_theming.conf` file  to the Apache config file the directory belongs to
# sudo /etc/init.d/apache2 reload
