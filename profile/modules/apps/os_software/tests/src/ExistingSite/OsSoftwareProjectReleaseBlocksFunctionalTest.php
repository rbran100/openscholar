<?php

namespace Drupal\Tests\os_software\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OsSoftwareProjectReleaseBlocksFunctionalTest.
 *
 * @group cp
 * @group functional
 *
 * @package Drupal\Tests\os_software\ExistingSite
 */
class OsSoftwareProjectReleaseBlocksFunctionalTest extends OsExistingSiteTestBase {

  protected $projectNode;
  protected $media;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->projectNode = $this->createNode([
      'type' => 'software_project',
    ]);
    $this->media = $this->createMedia([
      'bundle' => [
        'target_id' => 'executable',
      ],
    ], 'binary');
  }

  /**
   * Test release block recommended on projects node.
   */
  public function testProjectNodeReleaseBlockRecommended() {
    $web_assert = $this->assertSession();
    $this->createNode([
      'type' => 'software_release',
      'field_software_project' => [
        $this->projectNode->id(),
      ],
      'field_software_version' => 'v1.1.2',
      'field_software_package' => [
        $this->media->id(),
      ],
      'field_is_recommended_version' => TRUE,
    ]);
    $this->visitViaVsite('node/' . $this->projectNode->id(), $this->group);
    $web_assert->pageTextContains('Recommended Releases');
    $web_assert->pageTextContains('v1.1.2');
    $web_assert->pageTextNotContains('Recent Releases');
  }

  /**
   * Test release block recent on projects node.
   */
  public function testProjectNodeReleaseBlockRecent() {
    $web_assert = $this->assertSession();
    $this->createNode([
      'type' => 'software_release',
      'field_software_project' => [
        $this->projectNode->id(),
      ],
      'field_software_version' => 'v1.1.1',
      'field_software_package' => [
        $this->media->id(),
      ],
      'field_is_recommended_version' => FALSE,
    ]);
    $this->visitViaVsite('node/' . $this->projectNode->id(), $this->group);
    $web_assert->pageTextContains('Recent Releases');
    $web_assert->pageTextContains('v1.1.1');
    $web_assert->pageTextNotContains('Recommended Releases');
  }

}