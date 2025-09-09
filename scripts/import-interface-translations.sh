#!/bin/bash

# Import Interface Translations During Deployment
# Usage: ./scripts/import-interface-translations.sh

echo "Importing interface translations..."

# Check if translation files exist
if [ ! -f "translations/interface/es-interface.po" ]; then
    echo "Warning: Spanish interface translation file not found at translations/interface/es-interface.po"
else
    echo "Importing Spanish interface translations..."
    drush locale:import es translations/interface/es-interface.po --type=customized --override=all
fi

if [ ! -f "translations/interface/ru-interface.po" ]; then
    echo "Warning: Russian interface translation file not found at translations/interface/ru-interface.po"
else
    echo "Importing Russian interface translations..."
    drush locale:import ru translations/interface/ru-interface.po --type=customized --override=all
fi

echo "Clearing cache..."
drush cr

echo "Interface translations import completed!"
