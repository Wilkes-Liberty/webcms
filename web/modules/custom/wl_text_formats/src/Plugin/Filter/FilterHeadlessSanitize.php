<?php

namespace Drupal\wl_text_formats\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\filter\Plugin\FilterInterface as FilterInterfaceAlias;

/**
 * Headless HTML sanitizer filter for clean output.
 */
#[Filter(
  id: "filter_headless_sanitize",
  title: new TranslatableMarkup("Headless HTML Sanitizer"),
  description: new TranslatableMarkup("Sanitizes HTML for headless frontend consumption"),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
  weight: 10
)]
class FilterHeadlessSanitize extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->settings + [
      'allowed_tags' => 'p h2 h3 h4 ul ol li blockquote hr strong em code pre a',
      'allowed_attributes' => 'href',
      'strict_mode' => TRUE,
      'log_sanitization' => FALSE,
    ];
    
    $form['allowed_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML tags'),
      '#default_value' => $settings['allowed_tags'],
      '#description' => $this->t('Space-separated list of allowed HTML tags without angle brackets.'),
      '#maxlength' => 512,
      '#required' => TRUE,
    ];

    $form['allowed_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed attributes'),
      '#default_value' => $settings['allowed_attributes'],
      '#description' => $this->t('Space-separated list of allowed HTML attributes.'),
      '#maxlength' => 512,
    ];

    $form['strict_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict mode'),
      '#default_value' => $settings['strict_mode'],
      '#description' => $this->t('When enabled, disallowed tags are removed. When disabled, they are converted to text.'),
    ];

    $form['log_sanitization'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log sanitization actions'),
      '#default_value' => $settings['log_sanitization'],
      '#description' => $this->t('Log when content is sanitized.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (empty($text) || strip_tags($text) === $text) {
      return new FilterProcessResult($text);
    }

    $settings = $this->settings + [
      'allowed_tags' => 'p h2 h3 h4 ul ol li blockquote hr strong em code pre a',
      'allowed_attributes' => 'href',
      'strict_mode' => TRUE,
      'log_sanitization' => FALSE,
    ];

    $allowed_tags = array_filter(array_map('trim', explode(' ', $settings['allowed_tags'])));
    $allowed_attributes = array_filter(array_map('trim', explode(' ', $settings['allowed_attributes'])));

    try {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $elements = $xpath->query('//body//*');
      
      $elements_array = [];
      foreach ($elements as $element) {
        $elements_array[] = $element;
      }

      foreach ($elements_array as $element) {
        $tag_name = strtolower($element->tagName);
        
        if (!in_array($tag_name, $allowed_tags, TRUE)) {
          if ($settings['strict_mode']) {
            if ($element->parentNode) {
              $element->parentNode->removeChild($element);
            }
          } else {
            $parent = $element->parentNode;
            if ($parent) {
              while ($element->firstChild) {
                $parent->insertBefore($element->firstChild, $element);
              }
              $parent->removeChild($element);
            }
          }
        } else {
          // Remove disallowed attributes
          $attributes_to_remove = [];
          foreach ($element->attributes as $attribute) {
            $attr_name = strtolower($attribute->name);
            if (!in_array($attr_name, $allowed_attributes, TRUE)) {
              $attributes_to_remove[] = $attr_name;
            }
          }
          foreach ($attributes_to_remove as $attr_name) {
            $element->removeAttribute($attr_name);
          }
        }
      }

      $serialized = Html::serialize($dom);
      return new FilterProcessResult(trim($serialized));
    }
    catch (\Exception $e) {
      return new FilterProcessResult($text);
    }
  }

}
