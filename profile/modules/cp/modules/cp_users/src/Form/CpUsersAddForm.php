<?php

namespace Drupal\cp_users\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for the add User to Site form.
 */
class CpUsersAddForm extends FormBase {

  /**
   * Default role for members.
   */
  const DEFAULT_ROLE = 'personal-member';

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vsite.context_manager'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(VsiteContextManagerInterface $vsiteContextManager, EntityTypeManagerInterface $entityTypeManager, AccountProxy $current_user, MailManager $mail_manager) {
    $this->vsiteContextManager = $vsiteContextManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cp-users-add-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $group = $this->vsiteContextManager->getActiveVsite();
    if (!$group) {
      throw new AccessDeniedHttpException();
    }

    $roleData = [];
    /*@var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $group->getGroupType();
    $roles = $group_type->getRoles(TRUE);
    /* @var \Drupal\group\Entity\GroupRoleInterface $role */
    foreach ($roles as $rid => $role) {
      if (!$role->isAnonymous() && !$role->isOutsider()) {
        $roleData[$rid] = $role->label();
      }
    }

    $form['#prefix'] = '<div id="cp-user-add-form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['existing-member'] = [
      '#type' => 'details',
      '#title' => $this->t('Add an Existing User'),
      '#attributes' => [
        'id' => 'existing-member-fieldset',
      ],
      'member-entity' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#selection_settings' => [
          'include_anonymous' => FALSE,
        ],
        '#title' => $this->t('Member'),
      ],
      'role_existing' => [
        '#type' => 'radios',
        '#title' => $this->t('Role'),
        '#options' => $roleData,
        '#default_value' => CpUsersAddForm::DEFAULT_ROLE,
      ],
    ];

    $form['new-user'] = [
      '#type' => 'details',
      '#title' => $this->t('Add New User'),
      '#attributes' => [
        'id' => 'new-user-fieldset',
      ],
      '#access' => !$this->config('cp_users.settings')->get('disable_user_creation'),
      'first_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#maxlength' => 255,
        '#size' => 60,
      ],
      'last_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#maxlength' => 255,
        '#size' => 60,
      ],
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#maxlength' => 255,
        '#size' => 60,
      ],
      'email' => [
        '#type' => 'textfield',
        '#title' => $this->t('E-mail Address'),
        '#maxlength' => 255,
        '#size' => 60,
      ],
      'role_new' => [
        '#type' => 'radios',
        '#title' => $this->t('Role'),
        '#options' => $roleData,
        '#default_value' => CpUsersAddForm::DEFAULT_ROLE,
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => [$this, 'submitForm'],
          'event' => 'click',
        ],
      ],
      'cancel' => [
        '#type' => 'button',
        '#value' => $this->t('Cancel'),
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => [$this, 'closeModal'],
          'event' => 'click',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state_values = $form_state->getValues();

    if (!empty($form_state_values['email'])) {
      $account = (bool) user_load_by_mail($form_state_values['email']);
      if ($account) {
        $form_state->setError($form['new-user']['email'], $this->t('User with this email already exists. Please choose a different email.'));
        return FALSE;
      }
    }

    if (!empty($form_state_values['username'])) {
      $account = user_load_by_name($form_state_values['username']);
      if ($account) {
        $form_state->setError($form['new-user']['username'], $this->t('User with this username already exists. Please choose a different username.'));
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    static $response = NULL;
    // For some reason this function gets run twice? Not sure exactly why.
    // This is a workaround to return the response we've already created.
    if ($response) {
      return $response;
    }
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      $response->addCommand(new ReplaceCommand('#cp-user-add-form', $form));
    }
    else {
      $group = $this->vsiteContextManager->getActiveVsite();
      if (!$group) {
        $response->setStatusCode(403, 'Forbidden');
      }
      else {
        $response->addCommand(new CloseModalDialogCommand());
        $response->addCommand(new RedirectCommand(Url::fromRoute('cp.users')->toString()));

        /** @var string $entity */
        if ($entity = $form_state->getValue('member-entity')) {
          /** @var \Drupal\user\UserInterface $account */
          $account = $this->entityTypeManager->getStorage('user')->load($entity);
          $email_key = CP_USERS_ADD_TO_GROUP;
        }
        else {
          $account = User::create([
            'field_first_name' => $form_state->getValue('first_name'),
            'field_last_name' => $form_state->getValue('last_name'),
            'name' => $form_state->getValue('username'),
            'mail' => $form_state->getValue('email'),
            'status' => TRUE,
          ]);
          $account->save();
          $email_key = CP_USERS_NEW_USER;
        }

        /** @var string $role */
        $role = $form_state->getValue('role');
        if (!$role) {
          $role = $group->getGroupType()->getMemberRoleId();
        }

        $values = [
          'group_roles' => [
            $role,
          ],
        ];
        $group->addMember($account, $values);

        $params = [
          'user' => $account,
          'role' => $role,
          'creator' => $this->currentUser,
          'group' => $group,
        ];
        $this->mailManager->mail('cp_users', $email_key, $form_state->getValue('email'), LanguageInterface::LANGCODE_DEFAULT, $params);
      }
    }
    return $response;
  }

  /**
   * Closes the modal.
   */
  public function closeModal() {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
