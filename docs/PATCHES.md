# Entity Redirect Module - Null Label Fix

## Issue Description

The Entity Redirect module throws a `TypeError` when trying to create or edit entity types (such as Paragraph types) that don't have a label set yet. This occurs in the `entity_redirect_form_alter()` function when `$entity->label()` returns `null`, which then gets passed to `Html::escape()` via the translatable string mechanism.

## Error Message

```
TypeError: Drupal\Component\Utility\Html::escape(): Argument #1 ($text) must be of type string, null given, called in /var/www/html/web/core/lib/Drupal/Component/Render/FormattableMarkup.php on line 238
```

## Root Cause

In `entity_redirect.module`, lines 85 and 91 use `$entity->label()` directly in translatable strings without checking if the label is null:

```php
'created' => t('Created %entity_label', ['%entity_label' => $entity->label()]),
```

When creating new entities, the label is often null until the entity is saved.

## Solution

The fix adds a null coalescing operator to provide a fallback value when the entity label is null:

```php
'created' => t('Created %entity_label', ['%entity_label' => $entity->label() ?: t('Entity')]),
```

This ensures that if `$entity->label()` returns null, the generic term "Entity" will be used instead.

## Affected Versions

- Entity Redirect 8.x-2.3 (tested)
- Likely affects other versions in the 8.x-2.x series

## Contribution Steps

To contribute this fix back to the Drupal community:

1. **Check for existing issue**: Search the [Entity Redirect issue queue](https://www.drupal.org/project/issues/entity_redirect) for similar reports.

2. **Create a new issue** if none exists:
   - Title: "TypeError when entity label is null during form rendering"
   - Component: Code
   - Category: Bug report
   - Priority: Normal
   - Version: 8.x-2.3

3. **Upload the patch**: Use the file `entity_redirect-null-entity-label-fix.patch` from this directory.

4. **Provide issue summary**: Include the error message, steps to reproduce, and explanation of the fix.

## Steps to Reproduce

1. Enable Entity Redirect module
2. Enable Paragraphs module
3. Go to Structure > Paragraph types > Add paragraph type
4. Try to create a new paragraph type
5. Observe the TypeError

## Testing

After applying the patch:
1. You should be able to create new paragraph types without errors
2. The existing functionality should remain unchanged
3. The form should display "Created Entity" instead of trying to use a null label

## Files Modified

- `entity_redirect.module` (lines 85 and 91)

## Patch File

The patch is available in this directory as `entity_redirect-null-entity-label-fix.patch`.
