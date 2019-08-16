<?php

namespace Drupal\Tests\os_app_access\ExistingSite;

/**
 * AppAccessFunctionalTest.
 *
 * @covers \Drupal\os_app_access\Access\AppAccess
 * @covers \Drupal\os_app_access\Plugin\views\access\AppAccess
 * @covers \Drupal\os_app_access\Form\AppAccessForm
 * @group functional
 * @group os
 */
class AppAccessFunctionalTest extends AppAccessTestBase {

  /**
   * Group admin.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * Group member.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->groupMember = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->group->addMember($this->groupMember);
  }

  /**
   * @covers \Drupal\os_app_access\Access\AppAccess::access
   * @covers \Drupal\os_app_access\Plugin\views\access\AppAccess::access
   * @covers \Drupal\os_app_access\Plugin\views\access\AppAccess::alterRouteDefinition
   * @covers ::os_app_access_node_access
   * @covers ::_os_app_access_node_type_access
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testNode(): void {
    // Setup.
    $news = $this->createNode([
      'type' => 'news',
      'field_date' => [
        'value' => '2019-08-15',
      ],
    ]);
    $this->group->addContent($news, 'group_node:news');

    // Tests.
    // Test default config.
    $this->visitViaVsite("node/{$news->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    // Test disabled app setting.
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/app-access', $this->group);
    $this->getSession()->getPage()->find('css', 'input[type=checkbox][name="enabled[news][disable]"]')->check();
    $this->getSession()->getPage()->pressButton('Save configuration');

    $this->drupalLogout();
    $this->visitViaVsite("node/{$news->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(403);
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->groupMember);
    $this->visitViaVsite("node/{$news->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(403);
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->statusCodeEquals(403);

    // Test enabled app setting.
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/app-access', $this->group);
    $this->getSession()->getPage()->find('css', 'input[type=checkbox][name="disabled[news][enable]"]')->check();
    $this->getSession()->getPage()->pressButton('Save configuration');

    $this->drupalLogout();
    $this->visitViaVsite("node/{$news->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    // Test private app setting.
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/app-access', $this->group);
    $this->getSession()->getPage()->selectFieldOption('enabled[news][privacy]', 1);
    $this->getSession()->getPage()->pressButton('Save configuration');

    $this->drupalLogin($this->groupMember);
    $this->visitViaVsite("node/{$news->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->visitViaVsite('news', $this->group);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * @covers \Drupal\os_app_access\Access\AppAccess::access
   * @covers \Drupal\os_app_access\Plugin\views\access\AppAccess::access
   * @covers \Drupal\os_app_access\Plugin\views\access\AppAccess::alterRouteDefinition
   * @covers ::os_app_access_bibcite_reference_access
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPublications(): void {
    // Setup.
    $reference = $this->createReference();
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

    // Tests.
    // Test default config.
    $this->visitViaVsite("bibcite/reference/{$reference->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->visitViaVsite('publications', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    // Test disabled app setting.
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/app-access', $this->group);
    $this->submitForm([
      'enabled[blog][privacy]' => 0,
      'enabled[class][privacy]' => 0,
      'enabled[event][privacy]' => 0,
      'enabled[faq][privacy]' => 0,
      'enabled[links][privacy]' => 0,
      'enabled[news][privacy]' => 0,
      'enabled[page][privacy]' => 0,
      'enabled[presentations][privacy]' => 0,
      'enabled[profiles][privacy]' => 0,
      'enabled[publications][privacy]' => 1,
      'enabled[software][privacy]' => 0,
    ], 'Save configuration');

    $this->drupalLogout();

    $this->visitViaVsite("bibcite/reference/{$reference->id()}", $this->group);
    $this->assertSession()->statusCodeEquals(403);
    $this->visitViaVsite('publications', $this->group);
    $this->assertSession()->statusCodeEquals(403);

    // TODO: Write private app setting test.
  }

}
