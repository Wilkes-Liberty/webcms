<?php

namespace Drupal\Tests\wl_api\Unit\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\Form\GraphqlChecksForm;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use ReflectionClass;

/**
 * @coversDefaultClass \Drupal\wl_api\Form\GraphqlChecksForm
 * @group wl_api
 */
class GraphqlChecksFormTest extends UnitTestCase {

  /**
   * Helper to read a private property via reflection.
   */
  private function getPrivate(object $obj, string $name) {
    $ref = new ReflectionClass($obj);
    $prop = $ref->getProperty($name);
    $prop->setAccessible(TRUE);
    return $prop->getValue($obj);
  }

  /**
   * Helper to invoke a protected method via reflection.
   */
  private function invokeProtected(object $obj, string $method, array $args = []) {
    $ref = new ReflectionClass($obj);
    $m = $ref->getMethod($method);
    $m->setAccessible(TRUE);
    return $m->invokeArgs($obj, $args);
  }

  /**
   * Ensures the constructor stores injected services.
   *
   * @covers ::__construct
   */
  public function testConstructorInjection(): void {
    $http = $this->createMock(ClientInterface::class);
    $state = $this->createMock(StateInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $df = $this->createMock(DateFormatterInterface::class);

    $form = new GraphqlChecksForm($http, $state, $time, $df);

    $this->assertSame($http, $this->getPrivate($form, 'httpClient'));
    $this->assertSame($state, $this->getPrivate($form, 'state'));
    $this->assertSame($time, $this->getPrivate($form, 'time'));
    $this->assertSame($df, $this->getPrivate($form, 'dateFormatter'));
  }

  /**
   * Ensures create() pulls the correct services from the container.
   *
   * @covers ::create
   */
  public function testCreateUsesContainerServices(): void {
    $http = $this->createMock(ClientInterface::class);
    $state = $this->createMock(StateInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $df = $this->createMock(DateFormatterInterface::class);

    $container = new ContainerBuilder();
    $container->set('http_client', $http);
    $container->set('state', $state);
    $container->set('datetime.time', $time);
    $container->set('date.formatter', $df);

    $form = GraphqlChecksForm::create($container);

    $this->assertSame($http, $this->getPrivate($form, 'httpClient'));
    $this->assertSame($state, $this->getPrivate($form, 'state'));
    $this->assertSame($time, $this->getPrivate($form, 'time'));
    $this->assertSame($df, $this->getPrivate($form, 'dateFormatter'));
  }

  /**
   * runQuery should POST with httpClient and persist in state on success.
   *
   * @covers ::runQuery
   */
  public function testRunQuerySuccessUsesHttpClientAndState(): void {
    $http = $this->createMock(ClientInterface::class);
    $state = $this->createMock(StateInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $df = $this->createMock(DateFormatterInterface::class);

    $time->method('getRequestTime')->willReturn(1234567890);

    $stream = $this->createMock(StreamInterface::class);
    $stream->method('__toString')->willReturn('OK BODY');

    $resp = $this->createMock(ResponseInterface::class);
    $resp->method('getStatusCode')->willReturn(200);
    $resp->method('getBody')->willReturn($stream);

    $http->expects($this->once())
      ->method('post')
      ->with('https://example.test/graphql', [
        'json' => ['query' => '{ ping }'],
        'timeout' => 15,
      ])
      ->willReturn($resp);

    $state->expects($this->once())
      ->method('set')
      ->with(
        'wl_api.check.my_label',
        $this->callback(function ($rec) {
          $this->assertIsArray($rec);
          $this->assertArrayHasKey('t', $rec);
          $this->assertArrayHasKey('ok', $rec);
          $this->assertArrayHasKey('code', $rec);
          $this->assertArrayHasKey('body', $rec);
          $this->assertSame(TRUE, $rec['ok']);
          $this->assertSame(200, $rec['code']);
          $this->assertSame('OK BODY', $rec['body']);
          $this->assertSame(1234567890, $rec['t']);
          return TRUE;
        })
      );

    $form = new GraphqlChecksForm($http, $state, $time, $df);
    $this->invokeProtected($form, 'runQuery', ['https://example.test/graphql', 'My Label', '{ ping }']);
  }

  /**
   * renderResult should pull from state and format using date formatter.
   *
   * @covers ::renderResult
   */
  public function testRenderResultUsesStateAndDateFormatter(): void {
    $http = $this->createMock(ClientInterface::class);
    $state = $this->createMock(StateInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $df = $this->createMock(DateFormatterInterface::class);

    $state->method('get')->willReturn([
      't' => 42,
      'ok' => FALSE,
      'code' => 503,
      'body' => 'Body with <b>HTML</b>',
    ]);

    $df->method('format')->with(42, 'short')->willReturn('Jan 1, 1970');

    $form = new GraphqlChecksForm($http, $state, $time, $df);

    $out = $this->invokeProtected($form, 'renderResult', ['Test']);

    $this->assertIsString($out);
    $this->assertStringContainsString('Jan 1, 1970', $out);
    $this->assertStringContainsString('FAIL', $out);
    $this->assertStringContainsString('HTTP 503', $out);
    // Ensure body is escaped.
    $this->assertStringContainsString('&lt;b&gt;HTML&lt;/b&gt;', $out);
  }
}
