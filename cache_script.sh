#!/bin/bash
sudo -u www-data php app/console cache:clear --env=dev --no-debug --no-warmup
sudo rm -r app/cache/dev/*
sudo -u www-data php app/console cache:warmup --env=dev
sudo chmod 777 -R app/cache
