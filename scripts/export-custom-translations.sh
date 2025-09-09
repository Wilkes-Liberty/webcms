#!/bin/bash

# Export Only Custom Translation Overrides
# This exports only the specific strings we've customized, dramatically reducing file size

echo "Exporting custom translation overrides..."

# Create translations directory if it doesn't exist
mkdir -p translations/overrides

# Define our custom field translation strings
CUSTOM_STRINGS=(
    "Show On-Page TOC"
    "Show the table of contents on this page"
    "Visibility"
    "Control the visibility of this content"
    "Campaign"
    "Associate this content with a marketing campaign"
    "Template/Layout"
    "Select the template or layout for this content"
    "Related Content"
    "Select related content to display with this item"
    "Parent Page"
    "Select the parent page for this content"
    "Personas"
    "Select the target personas for this content"
    "Primary Industry"
    "Select the primary industry for this content"
    "Technologies"
    "Select the technologies related to this content"
)

# Function to create a minimal PO file with only our custom strings
create_custom_po() {
    local lang=$1
    local output_file=$2
    
    echo "Creating $output_file..."
    
    # Write PO file header
    cat > "$output_file" << EOF
# Custom translation overrides for $lang
# Generated for Wilkes & Liberty
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\\n"
"Language: $lang\\n"

EOF
    
    # Export each custom string
    for string in "${CUSTOM_STRINGS[@]}"; do
        # Get translation from database
        translation=$(ddev drush sqlq "SELECT t.translation FROM locales_source s JOIN locales_target t ON s.lid = t.lid WHERE t.language = '$lang' AND s.source = '$string'" 2>/dev/null || echo "")
        
        if [ -n "$translation" ]; then
            # Escape quotes in the strings
            escaped_source=$(echo "$string" | sed 's/"/\\"/g')
            escaped_translation=$(echo "$translation" | sed 's/"/\\"/g')
            
            # Add to PO file
            cat >> "$output_file" << EOF
msgid "$escaped_source"
msgstr "$escaped_translation"

EOF
        fi
    done
}

# Create custom translation files for each language
create_custom_po "es" "translations/overrides/es-custom.po"
create_custom_po "ru" "translations/overrides/ru-custom.po"

# Show file sizes
echo "Export completed:"
ls -lh translations/overrides/

echo ""
echo "Custom translation overrides exported successfully!"
echo "These files contain only the specific field translations we've customized."
echo ""
echo "To deploy these custom translations to another environment:"
echo "1. Copy the translations/overrides/ directory to the target environment"
echo "2. Run: ./scripts/import-custom-translations.sh"
