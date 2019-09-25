<?php

namespace Drupal\os_widgets\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form for creating Block content slideshow paragraph.
 */
class AddSlideshowForm extends ContentEntityForm {

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;
  protected $blockContent;
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_slideshow_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entity_type_manager, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityTypeManager = $entity_type_manager;
    $this->setEntity($this->entityTypeManager->getStorage('paragraph')->create([
      'type' => 'slideshow',
    ]));
    $this->setModuleHandler($moduleHandler);
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $block_id
   *   Block content id.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $block_id = NULL) {
    $block_content = $this->entityTypeManager->getStorage('block_content')->load($block_id);
    if (!$block_content) {
      throw new NotFoundHttpException('Block content is not found.');
    }
    if ($block_content->bundle() != 'slideshow') {
      throw new AccessDeniedHttpException('Given block content is not a slideshow.');
    }
    $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($block_content);
    $group_content = array_shift($group_contents);
    $block_content_group = $group_content->getGroup();
    $group = $this->vsiteContextManager->getActiveVsite();
    if ($group->id() != $block_content_group->id()) {
      throw new AccessDeniedHttpException();
    }
    $this->blockContent = $block_content;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $paragraph = $this->getEntity();

    $this->blockContent->field_slideshow->appendItem($paragraph);
    $this->blockContent->save();

    $this->messenger()->addMessage('Successfully save slideshow to ' . $this->blockContent->label());
  }

}
