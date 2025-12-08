<?php

namespace Drupal\Tests\wl_api\Unit\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\Form\PathRevalidateForm;
use Drupal\wl_api\Service\FrontendManager;
use Drupal\wl_api\Service\Revalidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use ReflectionClass;

/**
 * @coversDefaultClass \Drupal\wl_api\Form\PathRevalidateForm
 * @group wl_api
 */
class PathRevalidateFormTest extends UnitTestCase {

  private function getPrivate(object $obj, string $name) {
    $ref = new ReflectionClass($obj);
    $prop = $ref->getProperty($name);
    $prop->setAccessible(TRUE);
    return $prop->getValue($obj);
  }

  /**
   * Ensures the constructor stores injected services.
   *
   * @covers ::__construct
   */
  public function testConstructorInjection(): void {
    $fm = $this->createMock(FrontendManager::class);
    $rv = $this->createMock(Revalidator::class);

    $form = new PathRevalidateForm($fm, $rv);

    $this->assertSame($fm, $this->getPrivate($form, 'frontends'));
    $this->assertSame($rv, $this->getPrivate($form, 'revalidator'));
  }

  /**
   * buildForm should populate frontend options from FrontendManager.
   *
   * @covers ::buildForm
   */
  public function testBuildFormUsesFrontendManager(): void {
    $fm = $this->createMock(FrontendManager::class);
    $rv = $this->createMock(Revalidator::class);

    $fm->method('listFrontends')->willReturn([
      'fe1' => ['label' => 'Frontend One'],
      'fe2' => [], // no label => fallback to ID
    ]);

    // Provide translation + messenger services expected by FormBase.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('messenger', $this->createMock(MessengerInterface::class));
    \Drupal::setContainer($container);

    $formObj = new PathRevalidateForm($fm, $rv);
    $form = $formObj->buildForm([], new FormState());

    $this->assertArrayHasKey('frontend', $form);
    $this->assertSame([
      'fe1' => 'Frontend One',
      'fe2' => 'fe2',
    ], $form['frontend']['#options']);
  }

  /**
   * submitForm should post via Revalidator using values and FrontendManager data.
   *
   * @covers ::submitForm
   */
  public function testSubmitFormUsesRevalidator(): void {
    $fm = $this->createMock(FrontendManager::class);
    $rv = $this->createMock(Revalidator::class);

    $fm->method('listFrontends')->willReturn([
      'siteA' => [
        'label' => 'Site A',
        'path_revalidate_webhook' => 'https://example.test/revalidate-path',
        'secret' => 'SECRET_KEY',
      ],
    ]);
    $fm->method('resolveSecret')->with('SECRET_KEY')->willReturn('RESOLVED_SECRET');

    $rv->expects($this->once())
      ->method('post')
      ->with(
        'https://example.test/revalidate-path',
        'RESOLVED_SECRET',
        ['path' => '/about', 'domain' => 'path'],
        $this->callback(function (array $meta) {
          $this->assertSame('siteA', $meta['frontend'] ?? null);
          $this->assertSame('path', $meta['domain'] ?? null);
          $this->assertSame('/about', $meta['scope'] ?? null);
          $this->assertSame('revalidate', $meta['action'] ?? null);
          return TRUE;
        })
      );

    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())->method('addStatus');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('messenger', $messenger);
    \Drupal::setContainer($container);

    $formObj = new PathRevalidateForm($fm, $rv);

    $form_state = new FormState();
    $form_state->setValues([
      'frontend' => 'siteA',
      'path' => '/about',
    ]);

    $dummy_form = [];
    $formObj->submitForm($dummy_form, $form_state);

    // If no exceptions were thrown and messenger was called, behavior is correct.
    $this->assertTrue(TRUE);
  }
}
