<?php

namespace Drupal\Tests\cp_appearance\ExistingSiteJavascript;

use Drupal\cp_appearance\Entity\CustomTheme;

/**
 * Tests custom theme creation via UI.
 *
 * @group functional-javascript
 * @group cp-appearance
 */
class CustomThemeFunctionalTest extends CpAppearanceExistingSiteJavascriptTestBase {

  /**
   * Tests custom theme save.
   *
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::save
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::redirectOnSave
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSave(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);

    // Tests.
    $this->visitViaVsite('cp/appearance/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', 'Cyberpunk');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; }');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World")');
    $this->getSession()->getPage()->pressButton('Save');
    $this->getSession()->getPage()->pressButton('Confirm');

    $this->assertContains("{$this->groupAlias}/cp/appearance", $this->getSession()->getCurrentUrl());

    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    /** @var \Drupal\cp_appearance\Entity\CustomThemeInterface $custom_theme */
    $custom_theme = CustomTheme::load(CustomTheme::CUSTOM_THEME_ID_PREFIX . 'cyberpunk');
    $this->assertNotNull($custom_theme);
    $this->assertEquals('Cyberpunk', $custom_theme->label());
    $this->assertEquals('clean', $custom_theme->getBaseTheme());

    $style_file = 'file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id() . '/' . CustomTheme::CUSTOM_THEMES_STYLE_LOCATION;
    $styles = file_get_contents($style_file);
    $this->assertFileExists('file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id() . '/' . CustomTheme::CUSTOM_THEMES_STYLE_LOCATION);
    $this->assertEquals('body { color: black; }', $styles);

    $script_file = 'file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id() . '/' . CustomTheme::CUSTOM_THEMES_SCRIPT_LOCATION;
    $scripts = file_get_contents($script_file);
    $this->assertFileExists($script_file);
    $this->assertEquals('alert("Hello World")', $scripts);

    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $this->assertTrue($theme_handler->themeExists($custom_theme->id()));

    // Clean up.
    $custom_theme->delete();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * Tests whether the custom theme is installable.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testInstallable(): void {
    // Setup.
    $custom_theme = $this->createCustomTheme([], '', 'test');

    $admin = $this->createUser([
      'administer themes',
    ], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->visit('/admin/appearance');

    $this->assertSession()->pageTextContains($custom_theme->label());

    /** @var \Behat\Mink\Element\NodeElement|null $set_default_theme_link */
    $set_default_theme_link = $this->getSession()->getPage()->find('css', "[href*=\"/admin/appearance/default?theme={$custom_theme->id()}\"]");
    $this->assertNotNull($set_default_theme_link);
    $set_default_theme_link->click();

    // Tests.
    $this->visit('/');
    $this->assertSession()->responseContains("/themes/custom_themes/{$custom_theme->id()}/style.css");
    $this->assertSession()->responseContains("/themes/custom_themes/{$custom_theme->id()}/script.js");
  }

  /**
   * Tests custom theme save and set default.
   *
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::save
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::redirectOnSave
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSaveDefault(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);

    $this->visitViaVsite('cp/appearance/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', 'Cyberpunk 2077');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; }');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World")');
    $this->getSession()->getPage()->pressButton('Save and set as default theme');
    $this->getSession()->getPage()->pressButton('Confirm');

    $this->assertContains("{$this->groupAlias}/cp/appearance", $this->getSession()->getCurrentUrl());

    // Tests.
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    /** @var \Drupal\cp_appearance\Entity\CustomThemeInterface $custom_theme */
    $custom_theme = CustomTheme::load(CustomTheme::CUSTOM_THEME_ID_PREFIX . 'cyberpunk_2077');
    $this->assertNotNull($custom_theme);

    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $this->assertTrue($theme_handler->themeExists($custom_theme->id()));

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $this->assertEquals($custom_theme->id(), $config_factory->get('system.theme')->get('default'));

    // Cleanup.
    $custom_theme->delete();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::exists
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testMachineNameValidation(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);

    // Tests.
    $this->visitViaVsite('cp/appearance/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', 'Cp Appearance Test 1');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; }');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World")');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests custom theme edit.
   *
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::save
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::redirectOnSave
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUpdate(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
    $custom_theme_label = strtolower($this->randomMachineName());

    $this->visitViaVsite('cp/appearance/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', $custom_theme_label);
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; }');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World")');
    $this->getSession()->getPage()->pressButton('Save');
    $this->getSession()->getPage()->pressButton('Confirm');

    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    $custom_theme = CustomTheme::load(CustomTheme::CUSTOM_THEME_ID_PREFIX . $custom_theme_label);

    // Tests.
    $this->visitViaVsite('cp/appearance', $this->group);

    $edit_link = $this->getSession()->getPage()->find('css', "[href='{$this->groupAlias}/cp/appearance/custom-themes/{$custom_theme->id()}/edit']");
    $this->assertNotNull($edit_link);
    $edit_link->click();

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('css', '.admin-link');
    $this->assertEquals('body { color: black; }', $this->getSession()->getPage()->findField('styles')->getValue());
    $this->assertEquals('alert("Hello World")', $this->getSession()->getPage()->findField('scripts')->getValue());

    $this->getSession()->getPage()->fillField('Custom Theme Name', 'Cyberpunk');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; font-family: Sans-Serif; };');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World"); test');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertContains('cp/appearance', $this->getSession()->getCurrentUrl());
    $this->assertSession()->pageTextContains('Cyberpunk');

    $style_file = 'file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id() . '/' . CustomTheme::CUSTOM_THEMES_STYLE_LOCATION;
    $styles = file_get_contents($style_file);
    $this->assertFileExists('file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id() . '/' . CustomTheme::CUSTOM_THEMES_STYLE_LOCATION);
    $this->assertEquals('body { color: black; font-family: Sans-Serif; };', $styles);

    $script_file = 'file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id() . '/' . CustomTheme::CUSTOM_THEMES_SCRIPT_LOCATION;
    $scripts = file_get_contents($script_file);
    $this->assertFileExists($script_file);
    $this->assertEquals('alert("Hello World"); test', $scripts);

    // Cleanup.
    $custom_theme->delete();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * Tests custom theme edit and save as default.
   *
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::save
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeForm::redirectOnSave
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUpdateSetDefault(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
    $custom_theme_label = strtolower($this->randomMachineName());

    $this->visitViaVsite('cp/appearance/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', $custom_theme_label);
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; }');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World")');
    $this->getSession()->getPage()->pressButton('Save');
    $this->getSession()->getPage()->pressButton('Confirm');

    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    $custom_theme = CustomTheme::load(CustomTheme::CUSTOM_THEME_ID_PREFIX . $custom_theme_label);

    // Tests.
    $this->visitViaVsite("cp/appearance/custom-themes/{$custom_theme->id()}/edit", $this->group);
    $this->getSession()->getPage()->pressButton('Save and set as default');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $this->assertEquals($custom_theme->id(), $config_factory->get('system.theme')->get('default'));

    // Cleanup.
    $custom_theme->delete();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * Tests custom theme delete.
   *
   * @covers \Drupal\cp_appearance\Entity\CustomTheme::preDelete
   * @covers \Drupal\cp_appearance\Entity\CustomTheme::postDelete
   * @covers \Drupal\cp_appearance\Entity\Form\CustomThemeDeleteForm::submitForm
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testDelete(): void {
    // Setup.
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
    $custom_theme_label = strtolower($this->randomMachineName());

    $this->visitViaVsite('cp/appearance/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', $custom_theme_label);
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; }');
    $this->getSession()->getPage()->findField('scripts')->setValue('alert("Hello World")');
    $this->getSession()->getPage()->pressButton('Save and set as default theme');
    $this->getSession()->getPage()->pressButton('Confirm');

    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    $custom_theme = CustomTheme::load(CustomTheme::CUSTOM_THEME_ID_PREFIX . $custom_theme_label);

    // Tests.
    $this->visitViaVsite("cp/appearance/custom-themes/{$custom_theme->id()}/delete", $this->group);
    $this->getSession()->getPage()->pressButton('Confirm');

    $this->assertContains('cp/appearance', $this->getSession()->getCurrentUrl());

    /** @var \Drupal\Core\Config\ImmutableConfig $theme_setting */
    $theme_setting = $config_factory->get('system.theme');

    $this->assertEquals('clean', $theme_setting->get('default'));
    $this->assertFalse($theme_handler->themeExists($custom_theme->id()));
    $this->assertDirectoryNotExists('file://' . CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION . '/' . $custom_theme->id());
  }

}