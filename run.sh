#!/bin/bash

if [ ! -f $DB_PATH ]; then
    php bin/console doctrine:database:create
fi
php bin/console doctrine:schema:update --force
php bin/console server:run 0.0.0.0:8080
