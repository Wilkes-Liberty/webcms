#!/bin/bash

# Export Interface Translations for Deployment
# Usage: ./scripts/export-interface-translations.sh

echo "Exporting interface translations..."

# Create translations directory if it doesn't exist
mkdir -p translations/interface

# Export only customized interface translations (not configuration)
echo "Exporting Spanish interface translations..."
ddev drush locale:export es --types=customized > translations/interface/es-interface.po

echo "Exporting Russian interface translations..."
ddev drush locale:export ru --types=customized > translations/interface/ru-interface.po

# Check file sizes
echo "Export completed:"
ls -lh translations/interface/

echo ""
echo "To deploy these translations to another environment:"
echo "1. Copy the translations/interface/ directory to the target environment"
echo "2. Run: ddev drush locale:import es translations/interface/es-interface.po --type=customized --override=all"
echo "3. Run: ddev drush locale:import ru translations/interface/ru-interface.po --type=customized --override=all"
echo "4. Run: ddev drush cr"
