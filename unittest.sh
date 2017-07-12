#!/bin/bash

if [ -z "$TEST_DB_PASS" ]; then
    echo "Need to set TEST_DB_PASS"
    exit 1
fi

composer update
vendor/bin/phpunit src/test --coverage-html=coverage
