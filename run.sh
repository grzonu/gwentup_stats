#!/bin/bash

php bin/console doctrine:schema:update --force
php bin/console server:run 0.0.0.0:$PORT
