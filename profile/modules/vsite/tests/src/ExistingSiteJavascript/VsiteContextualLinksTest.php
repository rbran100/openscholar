<?php

namespace Drupal\Tests\vsite\ExistingSiteJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests contextual link alterations for vsites.
 *
 * @group functional-javascript
 * @group vsite
 */
class VsiteContextualLinksTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests whether the destination parameter is valid in listings.
   *
   * @covers ::vsite_js_settings_alter
   */
  public function testDestinationParameterInListing(): void {
    // Setup.
    $blog = $this->createNode([
      'type' => 'blog',
    ]);
    $this->group->addContent($blog, 'group_node:blog');
    $admin = $this->createUser([
      'access contextual links',
    ], NULL, TRUE);
    $this->group->addMember($admin);
    $this->drupalLogin($admin);

    $this->visitViaVsite('blog', $this->group);
    $this->assertSession()->waitForElement('css', '.contextual button');

    // Tests.
    /** @var \Behat\Mink\Element\NodeElement|null $edit_contextual_link */
    $edit_contextual_link = $this->getSession()->getPage()->find('css', '.contextual-links .entitynodeedit-form a');
    $this->assertNotNull($edit_contextual_link);
    $this->assertEquals("{$this->groupAlias}/blog", $this->getDestinationParameterValue($edit_contextual_link));

    /** @var \Behat\Mink\Element\NodeElement|null $delete_contextual_link */
    $delete_contextual_link = $this->getSession()->getPage()->find('css', '.contextual-links .entitynodedelete-form a');
    $this->assertNotNull($delete_contextual_link);
    $this->assertEquals("{$this->groupAlias}/blog", $this->getDestinationParameterValue($delete_contextual_link));
  }

  /**
   * Tests whether destination parameter is valid in node full view.
   *
   * @covers ::vsite_node_view_alter
   */
  public function testDestinationParameterInFullView(): void {
    // Setup.
    $blog = $this->createNode([
      'type' => 'blog',
    ]);
    $this->group->addContent($blog, 'group_node:blog');
    $admin = $this->createUser([
      'access contextual links',
    ], NULL, TRUE);
    $this->group->addMember($admin);
    $this->drupalLogin($admin);

    $this->visitViaVsite("node/{$blog->id()}", $this->group);
    $this->assertSession()->waitForElement('css', '.contextual-links .entitynodeedit-form');

    // Tests.
    /** @var \Behat\Mink\Element\NodeElement|null $edit_contextual_link */
    $edit_contextual_link = $this->getSession()->getPage()->find('css', '.contextual-links .entitynodeedit-form a');
    $this->assertNotNull($edit_contextual_link);
    $this->assertEquals("{$this->groupAlias}/node/{$blog->id()}", $this->getDestinationParameterValue($edit_contextual_link));

    /** @var \Behat\Mink\Element\NodeElement|null $delete_contextual_link */
    $delete_contextual_link = $this->getSession()->getPage()->find('css', '.contextual-links .entitynodedelete-form a');
    $this->assertNotNull($delete_contextual_link);
    $this->assertEquals("{$this->groupAlias}/blog", $this->getDestinationParameterValue($delete_contextual_link));
  }

  /**
   * Retrieves the destination parameter value from a link.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The link element.
   *
   * @return string
   *   The destination.
   */
  protected function getDestinationParameterValue(NodeElement $element): string {
    $href = $element->getAttribute('href');
    list(, $query) = explode('?', $href);
    list(, $value) = explode('=', $query);

    return $value;
  }

}
