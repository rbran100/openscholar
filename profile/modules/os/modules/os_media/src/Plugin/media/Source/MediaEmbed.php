<?php

namespace Drupal\os_media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\MediaInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbed;
use Drupal\os_media\MediaEntityHelper;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a media source plugin for oEmbed resources.
 *
 * @MediaSource(
 *   id = "oembed:html",
 *   label = @Translation("HTML source"),
 *   description = @Translation("Use HTML URL for reusable media."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "generic.png",
 *   providers = {},
 * )
 */
class MediaEmbed extends OEmbed {

  /**
   * Media Helper service.
   *
   * @var \Drupal\os_media\MediaEntityHelper
   */
  protected $mediaHelper;

  /**
   * Constructs a new OEmbed instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for media.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   * @param \Drupal\os_media\MediaEntityHelper $media_helper
   *   The media helper instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerInterface $logger, MessengerInterface $messenger, ClientInterface $http_client, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, IFrameUrlHelper $iframe_url_helper, MediaEntityHelper $media_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory, $field_type_manager, $logger, $messenger, $http_client, $resource_fetcher, $url_resolver, $iframe_url_helper);
    $this->mediaHelper = $media_helper;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('logger.factory')->get('media'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('os_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'name' => $this->t('Name of the media item'),
      'default_name' => $this->t('Default name of the media item'),
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case 'name':
      case 'default_name':
        return $media->getName();

      case 'thumbnail_uri':
        if ($media->bundle() === 'oembed') {
          $media_url = $this->getSourceFieldValue($media);
          $resource = $this->mediaHelper->fetchEmbedlyResource($media_url);
          $this->mediaHelper->downloadThumbnail($resource);
          $thumbnail_uri = $this->mediaHelper->getLocalThumbnailUri($resource);
          if (!empty($thumbnail_uri)) {
            return $thumbnail_uri;
          }
          return NULL;
        }
        else {
          $icon_base = $this->configFactory->get('media.settings')->get('icon_base_uri');
          $thumbnail = $icon_base . '/generic.png';
          if (is_file($thumbnail)) {
            return $thumbnail;
          }
        }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [];
  }

}
