<?php

/**
 * @file
 * Contains \Drupal\studiobridge_rest_resources\Plugin\rest\resource\studiobridge_rest_resources.
 */

namespace Drupal\studiobridge_rest_resources\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "studio_live_image_container_rest_resource",
 *   label = @Translation("Live shooting page image container"),
 *   uri_paths = {
 *     "canonical" = "/live-shoot-image-container/{identifier}/{random}"
 *   }
 * )
 */
class StudioLiveImageContainerRestResource extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  public $content = '';

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($identifier,$random) {
    $this->getBlockData($identifier);
    $content = array('content' =>$this->content);

    $block1 = \Drupal\block\Entity\Block::load('currentsessionviewblock');
    $block_content1 = \Drupal::entityManager()
      ->getViewBuilder('block')
      ->view($block1);

    $content['block1'] = \Drupal::service('renderer')->renderPlain($block_content1);
    return new ResourceResponse($content);
  }

  public function getBlockData($identifier){
    // todo : if identifier found in db then get the node id of mapped product, else check for unmapped product id
    // todo : if both are failed then ????????

    // todo : get node id by identifier
    $node_id = \Drupal::entityQuery('node')
      ->condition('title', $identifier)
      ->sort('created', 'DESC')
      //->condition('field_state','open')
      ->range(0,1)
      ->execute();
    if(count($node_id)){
      $node_id = reset($node_id);
      $block = \Drupal::service('renderer')->renderPlain(views_embed_view('individual_project_view', 'block_2',$node_id),false);
      $this->content = (string) $block;
    }else{
      $block = '<span color="red">No product scanned</span>';
      $this->content = (string) $block;
    }
  }

}
