<?php

namespace Drupal\os_theme_preview;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains helpers for theme preview.
 *
 * Most probably this would be renamed to be something better, once the scope
 * becomes more clear.
 */
final class Helper implements HelperInterface {

  use StringTranslationTrait;

  /**
   * Session key.
   */
  public const SESSION_KEY = 'os_theme_preview';

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Helper constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function startPreviewMode($theme, $base_path): void {
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface|null $session */
    $session = $this->request->getSession();

    if (!$session) {
      throw new ThemePreviewException($this->t('Preview could not be started.'));
    }

    $session->set(self::SESSION_KEY, [
      'name' => $theme,
      'path' => $base_path,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewedThemeData(): ?array {
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface|null $session */
    $session = $this->request->getSession();

    if (!$session) {
      return NULL;
    }

    /** @var array|null $current_preview_theme */
    $current_preview_theme = $session->get(self::SESSION_KEY);

    return $current_preview_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function stopPreviewMode(): void {
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface|null $session */
    $session = $this->request->getSession();

    if (!$session) {
      throw new ThemePreviewException($this->t('Could not stop preview mode.'));
    }

    $session->remove(self::SESSION_KEY);
  }

}
