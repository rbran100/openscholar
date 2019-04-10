<?php

namespace Drupal\Tests\cp_appearance\ExistingSite;

/**
 * AppearanceHelper service test.
 *
 * @group kernel
 * @group other
 * @coversDefaultClass \Drupal\cp_appearance\AppearanceHelper
 */
class AppearanceHelperTest extends TestBase {

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->themeHandler = $this->container->get('theme_handler');
  }

  /**
   * @covers ::getThemes
   * @covers ::addScreenshotInfo
   * @covers ::addOperations
   * @covers ::addNotes
   */
  public function test(): void {
    /** @var \Drupal\Core\Config\Config $theme_config_mut */
    $theme_config_mut = $this->configFactory->getEditable('system.theme');
    $theme_config_mut->set('default', 'hwpi_classic')->save();

    /** @var \Drupal\Core\Extension\Extension[] $themes */
    $themes = $this->appearanceHelper->getThemes();

    $this->assertFalse(isset($themes['stark']));
    $this->assertFalse(isset($themes['seven']));
    $this->assertFalse(isset($themes['os_base']));
    $this->assertFalse(isset($themes['bootstrap']));

    $this->assertTrue(isset($themes['hwpi_classic']));

    // Test presence of custom properties.
    $active_theme = $themes['hwpi_classic'];

    $this->assertTrue(property_exists($active_theme, 'is_default'));
    $this->assertTrue(property_exists($active_theme, 'is_admin'));
    $this->assertTrue(property_exists($active_theme, 'screenshot'));
    $this->assertTrue(property_exists($active_theme, 'operations'));
    $this->assertTrue(property_exists($active_theme, 'notes'));

    // Test screenshot info.
    $screenshot_info = $active_theme->screenshot;

    $this->assertNotNull($screenshot_info);
    $this->assertTrue(isset($screenshot_info['uri']));
    $this->assertTrue(isset($screenshot_info['alt']));
    $this->assertTrue(isset($screenshot_info['title']));
    $this->assertTrue(isset($screenshot_info['attributes']));

    // Test operations.
    $inactive_theme = $themes['hwpi_college'];

    $this->assertCount(1, $inactive_theme->operations);
    $operations = $inactive_theme->operations[0];
    $this->assertTrue(isset($operations['title']));
    $this->assertTrue(isset($operations['url']));
    $this->assertTrue(isset($operations['attributes']));

    $this->assertCount(0, $active_theme->operations);

    // Test notes.
    $this->assertCount(0, $inactive_theme->notes);

    $this->assertCount(1, $active_theme->notes);
    $notes = $active_theme->notes[0];
    $this->assertEquals('current theme', $notes);
  }

}
