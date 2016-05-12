<?php

namespace Drupal\studiobridge_rest_resources\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "time_session_product_post",
 *   label = @Translation("[Studio] Session/Product time record"),
 *   serialization_class = "Drupal\node\Entity\Node",
 *   uri_paths = {
 *     "canonical" = "/studio/time",
 *     "https://www.drupal.org/link-relations/create" = "/studio/time/{node_type}/post"
 *   }
 * )
 */
class SessionProductTimeRestResource extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  protected $studioProducts;

  protected $studioSessions;


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
    AccountProxyInterface $current_user, $studioProducts, $studioSessions) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->studioProducts = $studioProducts;
    $this->studioSessions = $studioSessions;

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
      $container->get('logger.factory')->get('ccms_rest'),
      $container->get('current_user'),
      $container->get('studio.products'),
      $container->get('studio.sessions')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $node_type
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function post($node_type, $data) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    if($node_type == 'start'){
      if($data->body->value['sid']){
        $this->studioSessions->AddEndTimeToSession($data->body->value['sid'], 0);
        $this->studioSessions->AddStartTimeToSession($data->body->value['sid'], $data->body->value['pause']);

        if($data->body->value['pause']){
          $this->studioSessions->updateSessionStatus($data->body->value['sid'], 'pause');
        }


        return new ResourceResponse('started');
      }
    }elseif($node_type == 'end'){
      if($data->body->value['sid']){
        $this->studioSessions->AddEndTimeToSession($data->body->value['sid'], $data->body->value['pause']);

        if($data->body->value['pause']){
          $this->studioSessions->updateSessionStatus($data->body->value['sid'], 'open');
        }

        return new ResourceResponse('ended');
      }
    }

    return new ResourceResponse($data);
  }

}

