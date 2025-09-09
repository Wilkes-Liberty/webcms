<?php

namespace Drupal\headless_clean\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for headless CMS HTML sanitization.
 *
 * This filter ensures that only explicitly allowed HTML elements and attributes
 * are retained in text content, making it suitable for headless/API usage where
 * clean, predictable HTML structure is essential.
 */
#[Filter(
  id: "filter_headless_sanitize",
  title: new TranslatableMarkup("Headless HTML Sanitizer"),
  description: new TranslatableMarkup("Removes all HTML elements and attributes except those explicitly allowed. Designed for headless CMS use cases where clean, minimal HTML is required."),
  type: FilterInterface::TYPE_HTML_RESTRICTOR,
  weight: 10
)]
class FilterHeadlessSanitize extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allowed_tags' => 'p h2 h3 h4 ul ol li blockquote hr strong em code pre a',
      'allowed_attributes' => 'href',
      'strict_mode' => TRUE,
      'log_sanitization' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['allowed_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed HTML tags'),
      '#default_value' => $this->settings['allowed_tags'],
      '#description' => $this->t('Space-separated list of allowed HTML tags without angle brackets. Example: p h2 h3 ul ol li a strong em'),
      '#maxlength' => 512,
      '#required' => TRUE,
    ];

    $form['allowed_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed attributes'),
      '#default_value' => $this->settings['allowed_attributes'],
      '#description' => $this->t('Space-separated list of allowed HTML attributes. These apply to any allowed tag. Example: href title alt'),
      '#maxlength' => 512,
    ];

    $form['strict_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict mode'),
      '#default_value' => $this->settings['strict_mode'],
      '#description' => $this->t('When enabled, disallowed tags are completely removed. When disabled, disallowed tags are converted to plain text.'),
    ];

    $form['log_sanitization'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log sanitization actions'),
      '#default_value' => $this->settings['log_sanitization'],
      '#description' => $this->t('Log when content is sanitized for debugging purposes. Recommended for development only.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Return early if text is empty or contains no HTML.
    if (empty($text) || strip_tags($text) === $text) {
      return new FilterProcessResult($text);
    }

    $original_text = $text;
    $sanitized_text = $this->sanitizeHtml($text);
    
    // Log sanitization if enabled and changes were made.
    if ($this->settings['log_sanitization'] && $original_text !== $sanitized_text) {
      $this->loggerFactory->get('headless_clean')
        ->info('HTML content sanitized. Original length: @original, Sanitized length: @sanitized', [
          '@original' => strlen($original_text),
          '@sanitized' => strlen($sanitized_text),
        ]);
    }

    return new FilterProcessResult($sanitized_text);
  }

  /**
   * Sanitizes HTML content according to the configured whitelist.
   *
   * @param string $text
   *   The HTML content to sanitize.
   *
   * @return string
   *   The sanitized HTML content.
   */
  protected function sanitizeHtml(string $text): string {
    $allowed_tags = $this->parseAllowedTags();
    $allowed_attributes = $this->parseAllowedAttributes();

    try {
      // Load HTML into DOMDocument for manipulation.
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      // Process all elements in the document.
      $this->processElements($dom, $xpath, $allowed_tags, $allowed_attributes);

      // Serialize back to HTML.
      $serialized = Html::serialize($dom);
      
      return trim($serialized);
    }
    catch (\Exception $e) {
      // Log error and return original text as fallback.
      $this->loggerFactory->get('headless_clean')
        ->error('Error sanitizing HTML: @error', ['@error' => $e->getMessage()]);
      
      return $text;
    }
  }

  /**
   * Process all elements in the DOM, removing disallowed tags and attributes.
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   XPath object for querying.
   * @param array $allowed_tags
   *   Array of allowed tag names.
   * @param array $allowed_attributes
   *   Array of allowed attribute names.
   */
  protected function processElements(\DOMDocument $dom, \DOMXPath $xpath, array $allowed_tags, array $allowed_attributes): void {
    // Get all elements from the body (exclude html, head, body tags).
    $elements = $xpath->query('//body//*');
    
    // Convert to array to avoid issues with removing elements during iteration.
    $elements_array = [];
    foreach ($elements as $element) {
      $elements_array[] = $element;
    }

    foreach ($elements_array as $element) {
      $tag_name = strtolower($element->tagName);
      
      if (!in_array($tag_name, $allowed_tags, TRUE)) {
        $this->handleDisallowedTag($element);
      }
      else {
        $this->sanitizeElementAttributes($element, $allowed_attributes);
      }
    }
  }

  /**
   * Handle a disallowed HTML tag.
   *
   * @param \DOMElement $element
   *   The disallowed element.
   */
  protected function handleDisallowedTag(\DOMElement $element): void {
    if ($this->settings['strict_mode']) {
      // Remove the element completely.
      if ($element->parentNode) {
        $element->parentNode->removeChild($element);
      }
    }
    else {
      // Convert to text content (unwrap the tag but keep content).
      $this->unwrapElement($element);
    }
  }

  /**
   * Unwrap an element, keeping its text content but removing the tag.
   *
   * @param \DOMElement $element
   *   The element to unwrap.
   */
  protected function unwrapElement(\DOMElement $element): void {
    $parent = $element->parentNode;
    if (!$parent) {
      return;
    }

    // Move all child nodes to the parent.
    while ($element->firstChild) {
      $parent->insertBefore($element->firstChild, $element);
    }
    
    // Remove the element itself.
    $parent->removeChild($element);
  }

  /**
   * Remove disallowed attributes from an element.
   *
   * @param \DOMElement $element
   *   The element to process.
   * @param array $allowed_attributes
   *   Array of allowed attribute names.
   */
  protected function sanitizeElementAttributes(\DOMElement $element, array $allowed_attributes): void {
    // Get all attributes to avoid modification during iteration.
    $attributes_to_remove = [];
    
    foreach ($element->attributes as $attribute) {
      $attr_name = strtolower($attribute->name);
      
      // Remove dangerous attributes regardless of whitelist.
      if ($this->isDangerousAttribute($attr_name, $attribute->value)) {
        $attributes_to_remove[] = $attr_name;
        continue;
      }
      
      // Remove if not in allowed attributes list.
      if (!in_array($attr_name, $allowed_attributes, TRUE)) {
        $attributes_to_remove[] = $attr_name;
      }
    }
    
    // Remove the disallowed attributes.
    foreach ($attributes_to_remove as $attr_name) {
      $element->removeAttribute($attr_name);
    }
  }

  /**
   * Check if an attribute is potentially dangerous.
   *
   * @param string $name
   *   The attribute name.
   * @param string $value
   *   The attribute value.
   *
   * @return bool
   *   TRUE if the attribute is dangerous, FALSE otherwise.
   */
  protected function isDangerousAttribute(string $name, string $value): bool {
    // Block event handlers.
    if (str_starts_with($name, 'on')) {
      return TRUE;
    }
    
    // Block style attributes (inline CSS).
    if ($name === 'style') {
      return TRUE;
    }
    
    // Block JavaScript URLs.
    if (in_array($name, ['href', 'src'], TRUE)) {
      $value = strtolower(trim($value));
      if (str_starts_with($value, 'javascript:') || str_starts_with($value, 'data:')) {
        return TRUE;
      }
    }
    
    return FALSE;
  }

  /**
   * Parse the allowed tags setting into an array.
   *
   * @return array
   *   Array of allowed tag names.
   */
  protected function parseAllowedTags(): array {
    $tags = preg_split('/\s+/', trim($this->settings['allowed_tags']), -1, PREG_SPLIT_NO_EMPTY);
    return array_map('strtolower', $tags);
  }

  /**
   * Parse the allowed attributes setting into an array.
   *
   * @return array
   *   Array of allowed attribute names.
   */
  protected function parseAllowedAttributes(): array {
    $attributes = preg_split('/\s+/', trim($this->settings['allowed_attributes']), -1, PREG_SPLIT_NO_EMPTY);
    return array_map('strtolower', $attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $allowed_tags = $this->parseAllowedTags();
    $allowed_attributes = $this->parseAllowedAttributes();
    
    if ($long) {
      $tag_list = '<' . implode('>, <', $allowed_tags) . '>';
      $attr_list = !empty($allowed_attributes) ? implode(', ', $allowed_attributes) : $this->t('none');
      
      return $this->t('<p><strong>Headless HTML Sanitizer:</strong></p><ul><li>Allowed tags: @tags</li><li>Allowed attributes: @attributes</li><li>All other HTML elements and attributes will be @action.</li></ul>', [
        '@tags' => $tag_list,
        '@attributes' => $attr_list,
        '@action' => $this->settings['strict_mode'] ? $this->t('removed') : $this->t('converted to plain text'),
      ]);
    }
    
    return $this->t('Only specific HTML tags and attributes are allowed: @tags', [
      '@tags' => '<' . implode('>, <', array_slice($allowed_tags, 0, 5)) . '>...',
    ]);
  }

}
