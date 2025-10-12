#!/bin/bash

# This script outputs the content of all files modified during the vibecoding session.
# It can be piped to a file for review, e.g., ./scripts/dump_session_files.sh > session_review.txt

# Define the base path of the project to avoid repetition.
BASE_PATH="/home/briarmoss/Documents/boston-ai-project"

# An array of all file paths relative to the base path.
FILES=(
    "app/Console/Commands/GenerateFieldDictionary.php"
    "app/Http/Controllers/AiAssistantController.php"
    "app/Models/EverettCrimeData.php"
    "database/migrations/2025_06_11_000000_create_everett_crime_data_table.php"
    "database/seeders/CrimeDataSeeder.php"
    "database/seeders/EverettCrimeDataSeeder.php"
    "database/seeders/incident_type_groups.json"
    "database/seeders/offense_code_groups.json"
)

# Loop through the files array.
for file in "${FILES[@]}"; do
    full_path="$BASE_PATH/$file"
    
    # Check if the file exists before trying to output it.
    if [ -f "$full_path" ]; then
        echo "===================================================================="
        echo "FILE: $full_path"
        echo "===================================================================="
        cat "$full_path"
        echo ""
        echo ""
    else
        echo "===================================================================="
        echo "FILE NOT FOUND: $full_path"
        echo "===================================================================="
        echo ""
        echo ""
    fi
done
