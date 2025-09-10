<?php

namespace Drupal\Tests\headless_clean\Unit\Plugin\Filter;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\headless_clean\Plugin\Filter\FilterHeadlessSanitize;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for FilterHeadlessSanitize filter.
 *
 * @coversDefaultClass \Drupal\headless_clean\Plugin\Filter\FilterHeadlessSanitize
 * @group headless_clean
 */
class FilterHeadlessSanitizeTest extends UnitTestCase {

  /**
   * The filter plugin under test.
   *
   * @var \Drupal\headless_clean\Plugin\Filter\FilterHeadlessSanitize
   */
  protected $filter;

  /**
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    
    $configuration = [];
    $plugin_id = 'filter_headless_sanitize';
    $plugin_definition = [
      'id' => $plugin_id,
      'title' => 'Headless HTML Sanitizer',
    ];

    $this->filter = new FilterHeadlessSanitize(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $this->loggerFactory
    );
  }

  /**
   * Test that plain text passes through unchanged.
   *
   * @covers ::process
   */
  public function testPlainTextPassthrough(): void {
    $input = 'This is plain text with no HTML.';
    $result = $this->filter->process($input, 'en');
    
    $this->assertInstanceOf(FilterProcessResult::class, $result);
    $this->assertEquals($input, $result->getProcessedText());
  }

  /**
   * Test that empty content passes through unchanged.
   *
   * @covers ::process
   */
  public function testEmptyContentPassthrough(): void {
    $result = $this->filter->process('', 'en');
    
    $this->assertInstanceOf(FilterProcessResult::class, $result);
    $this->assertEquals('', $result->getProcessedText());
  }

  /**
   * Test that allowed tags are preserved.
   *
   * @covers ::process
   */
  public function testAllowedTagsPreserved(): void {
    $input = '<p>Hello <strong>world</strong>!</p>';
    $result = $this->filter->process($input, 'en');
    
    $this->assertStringContainsString('<p>', $result->getProcessedText());
    $this->assertStringContainsString('<strong>', $result->getProcessedText());
    $this->assertStringContainsString('Hello', $result->getProcessedText());
    $this->assertStringContainsString('world', $result->getProcessedText());
  }

  /**
   * Test that disallowed tags are removed in strict mode.
   *
   * @covers ::process
   */
  public function testDisallowedTagsRemovedStrictMode(): void {
    // Configure strict mode (default)
    $this->filter->setConfiguration(['settings' => ['strict_mode' => TRUE]]);
    
    $input = '<div class="container"><p>Content</p><script>alert("xss")</script></div>';
    $result = $this->filter->process($input, 'en');
    $output = $result->getProcessedText();
    
    // Should contain allowed content
    $this->assertStringContainsString('<p>Content</p>', $output);
    
    // Should not contain disallowed tags
    $this->assertStringNotContainsString('<div', $output);
    $this->assertStringNotContainsString('<script', $output);
    $this->assertStringNotContainsString('alert', $output);
  }

  /**
   * Test that disallowed tags are unwrapped in non-strict mode.
   *
   * @covers ::process
   */
  public function testDisallowedTagsUnwrappedNonStrictMode(): void {
    // Configure non-strict mode
    $this->filter->setConfiguration(['settings' => ['strict_mode' => FALSE]]);
    
    $input = '<div><p>Content</p><span>Text</span></div>';
    $result = $this->filter->process($input, 'en');
    $output = $result->getProcessedText();
    
    // Should contain allowed tags and text from unwrapped tags
    $this->assertStringContainsString('<p>Content</p>', $output);
    $this->assertStringContainsString('Text', $output);
    
    // Should not contain disallowed tags
    $this->assertStringNotContainsString('<div', $output);
    $this->assertStringNotContainsString('<span', $output);
  }

  /**
   * Test that dangerous attributes are always removed.
   *
   * @covers ::process
   */
  public function testDangerousAttributesRemoved(): void {
    $input = '<p onclick="alert(\'xss\')" style="color: red;">Content</p>';
    $result = $this->filter->process($input, 'en');
    $output = $result->getProcessedText();
    
    // Should contain the tag and content
    $this->assertStringContainsString('<p>Content</p>', $output);
    
    // Should not contain dangerous attributes
    $this->assertStringNotContainsString('onclick', $output);
    $this->assertStringNotContainsString('style', $output);
  }

  /**
   * Test that allowed attributes are preserved.
   *
   * @covers ::process
   */
  public function testAllowedAttributesPreserved(): void {
    $input = '<a href="https://example.com">Link</a>';
    $result = $this->filter->process($input, 'en');
    $output = $result->getProcessedText();
    
    $this->assertStringContainsString('href="https://example.com"', $output);
    $this->assertStringContainsString('Link', $output);
  }

  /**
   * Test that JavaScript URLs are blocked.
   *
   * @covers ::process
   */
  public function testJavaScriptUrlsBlocked(): void {
    $input = '<a href="javascript:alert(\'xss\')">Link</a>';
    $result = $this->filter->process($input, 'en');
    $output = $result->getProcessedText();
    
    // Should contain the link text but no href
    $this->assertStringContainsString('Link', $output);
    $this->assertStringNotContainsString('javascript:', $output);
  }

  /**
   * Test parsing of allowed tags configuration.
   *
   * @covers ::parseAllowedTags
   */
  public function testParseAllowedTags(): void {
    $reflection = new \ReflectionClass($this->filter);
    $method = $reflection->getMethod('parseAllowedTags');
    $method->setAccessible(TRUE);

    // Test default configuration
    $tags = $method->invokeArgs($this->filter, []);
    $expected = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'hr', 'strong', 'em', 'code', 'pre', 'a'];
    $this->assertEquals($expected, $tags);
  }

  /**
   * Test parsing of allowed attributes configuration.
   *
   * @covers ::parseAllowedAttributes
   */
  public function testParseAllowedAttributes(): void {
    $reflection = new \ReflectionClass($this->filter);
    $method = $reflection->getMethod('parseAllowedAttributes');
    $method->setAccessible(TRUE);

    // Test default configuration
    $attributes = $method->invokeArgs($this->filter, []);
    $expected = ['href'];
    $this->assertEquals($expected, $attributes);
  }

  /**
   * Test dangerous attribute detection.
   *
   * @covers ::isDangerousAttribute
   * @dataProvider dangerousAttributeProvider
   */
  public function testIsDangerousAttribute(string $name, string $value, bool $expected): void {
    $reflection = new \ReflectionClass($this->filter);
    $method = $reflection->getMethod('isDangerousAttribute');
    $method->setAccessible(TRUE);

    $result = $method->invokeArgs($this->filter, [$name, $value]);
    $this->assertEquals($expected, $result, "Attribute '$name' with value '$value' should be " . ($expected ? 'dangerous' : 'safe'));
  }

  /**
   * Data provider for dangerous attribute tests.
   *
   * @return array
   *   Test cases with attribute name, value, and expected result.
   */
  public function dangerousAttributeProvider(): array {
    return [
      // Event handlers should be dangerous
      ['onclick', 'alert("xss")', TRUE],
      ['onload', 'malicious()', TRUE],
      ['onmouseover', 'track()', TRUE],
      
      // Style attribute should be dangerous
      ['style', 'color: red;', TRUE],
      
      // JavaScript URLs should be dangerous
      ['href', 'javascript:alert("xss")', TRUE],
      ['src', 'javascript:void(0)', TRUE],
      ['href', 'data:text/html,<script>alert("xss")</script>', TRUE],
      
      // Safe attributes should not be dangerous
      ['href', 'https://example.com', FALSE],
      ['title', 'Safe title text', FALSE],
      ['alt', 'Alternative text', FALSE],
      ['class', 'css-class', FALSE],
    ];
  }

  /**
   * Test complex HTML sanitization.
   *
   * @covers ::process
   */
  public function testComplexHtmlSanitization(): void {
    $input = '
      <article class="post" data-id="123">
        <header>
          <h2 style="color: red;">Article Title</h2>
          <div class="meta">
            <span>Author</span> | <time>2024-01-01</time>
          </div>
        </header>
        <div class="content">
          <p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text.</p>
          <ul class="list">
            <li>Item 1</li>
            <li>Item 2</li>
          </ul>
          <blockquote cite="source">Quote text</blockquote>
          <script>alert("malicious");</script>
        </div>
      </article>
    ';

    $result = $this->filter->process($input, 'en');
    $output = $result->getProcessedText();

    // Should contain allowed tags
    $this->assertStringContainsString('<h2>Article Title</h2>', $output);
    $this->assertStringContainsString('<p>', $output);
    $this->assertStringContainsString('<strong>bold</strong>', $output);
    $this->assertStringContainsString('<em>italic</em>', $output);
    $this->assertStringContainsString('<ul>', $output);
    $this->assertStringContainsString('<li>Item 1</li>', $output);
    $this->assertStringContainsString('<blockquote>Quote text</blockquote>', $output);

    // Should not contain disallowed elements
    $this->assertStringNotContainsString('<article', $output);
    $this->assertStringNotContainsString('<header', $output);
    $this->assertStringNotContainsString('<div', $output);
    $this->assertStringNotContainsString('<span', $output);
    $this->assertStringNotContainsString('<time', $output);
    $this->assertStringNotContainsString('<script', $output);

    // Should not contain disallowed attributes
    $this->assertStringNotContainsString('class=', $output);
    $this->assertStringNotContainsString('data-id=', $output);
    $this->assertStringNotContainsString('style=', $output);
    $this->assertStringNotContainsString('cite=', $output);
  }

}
