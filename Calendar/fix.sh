#!/bin/bash
sudo rm -rf var/logs/*
sudo rm -rf var/cache/*
sudo chmod -R 777 var
php bin/console cache:clear
php bin/console cache:clear --no-warmup --env=prod
sudo chmod -R 777 var
