#! /bin/bash
set -e

MW_BRANCH=$1

git clone --depth 1 --branch ${MW_BRANCH} https://github.com/wikimedia/mediawiki.git mediawiki

cd mediawiki

composer install
php maintenance/install.php --dbtype sqlite --dbuser root --dbname mw --dbpath $(pwd) --pass AdminPassword WikiName AdminUser

echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgShowDBErrorBacktrace = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php

echo 'wfLoadSkin( "Medik" );' >> LocalSettings.php
