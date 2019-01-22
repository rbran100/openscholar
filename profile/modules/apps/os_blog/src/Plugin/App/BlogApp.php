<?php

namespace Drupal\os_blog\Plugin\App;

use Drupal\Component\Plugin\PluginBase;
use Drupal\vsite\AppInterface;

/**
 * Bog app.
 *
 * @App(
 *   title = @Translation("Blog"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = "blog",
 *   id = "blog"
 * )
 */
class BlogApp extends PluginBase implements AppInterface {

  /**
   * {@inheritdoc}
   */
  public function getGroupContentTypes() {
    return [
      'blog',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

}