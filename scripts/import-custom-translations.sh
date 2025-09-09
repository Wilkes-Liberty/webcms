#!/bin/bash

# Import Only Custom Translation Overrides
# This imports only the specific strings we've customized

echo "Importing custom translation overrides..."

# Check if translation files exist and import them
if [ ! -f "translations/overrides/es-custom.po" ]; then
    echo "Warning: Spanish custom translation file not found at translations/overrides/es-custom.po"
else
    echo "Importing Spanish custom translations..."
    ddev drush locale:import es translations/overrides/es-custom.po --type=customized --override=all
fi

if [ ! -f "translations/overrides/ru-custom.po" ]; then
    echo "Warning: Russian custom translation file not found at translations/overrides/ru-custom.po"
else
    echo "Importing Russian custom translations..."
    ddev drush locale:import ru translations/overrides/ru-custom.po --type=customized --override=all
fi

echo "Clearing cache..."
ddev drush cr

echo ""
echo "Custom translation overrides import completed!"
echo "Only the specific field translations have been updated."
