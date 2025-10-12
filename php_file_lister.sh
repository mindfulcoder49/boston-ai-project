#!/bin/bash

# This script lists all relevant PHP files in a Laravel project,
# ensuring that only custom application code is included and
# none of the Laravel core code is present.

# Define the directories to search
APP_DIR="app"
ROUTES_DIR="routes"
CONFIG_DIR="config"
DATABASE_DIR="database"
RESOURCES_DIR="resources"
TESTS_DIR="tests"

# Find and output the content of PHP files in the specified directories
echo "Outputting content of custom PHP files in the Laravel project:"
find "$APP_DIR" "$ROUTES_DIR" "$CONFIG_DIR" "$DATABASE_DIR" "$RESOURCES_DIR" "$TESTS_DIR" -name "*.php" -exec sh -c '
    for file do
        echo "########## START OF FILE: $file ##########"
        cat "$file"
        echo "########## END OF FILE: $file ##########"
        echo ""
    done
' sh {} +