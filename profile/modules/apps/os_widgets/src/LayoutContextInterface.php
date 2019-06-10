<?php

namespace Drupal\os_widgets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\layout_builder\SectionListInterface;

/**
 * Interface for LayoutContext objects.
 */
interface LayoutContextInterface extends ConfigEntityInterface {

  /**
   * Get the description of the LayoutContext.
   */
  public function getDescription();

  /**
   * Get the rules under which this LayoutContext should be activated.
   */
  public function getActivationRules();

  /**
   * Get the weight of the Layout Context.
   *
   * Higher weights override lower.
   */
  public function getWeight();

  /**
   * Does this LayoutContext apply to the given page.
   */
  public function applies(): bool;

  /**
   * Get all blocks this LayoutContext controls.
   */
  public function getBlockPlacements();

  /**
   * Set the block placements for this Context.
   */
  public function setBlockPlacements(array $blocks);

}
