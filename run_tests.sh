#!/bin/bash

DRIVER=$1;

echo "Starting PHPUNIT tests"
export DB_DRIVER=$DRIVER

./vendor/bin/phpunit --testsuite Default --stop-on-fail
