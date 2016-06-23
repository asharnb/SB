<?php

/**
 * @file
 * Contains \Drupal\studiobridge_rest_resources\Plugin\rest\resource\studiobridge_rest_resources.
 */

namespace Drupal\studiobridge_rest_resources\Plugin\rest\resource;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Drupal\node\Entity\Node;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "file_name_rest_resource",
 *   label = @Translation("[StudioBridge] File name rest resource"),
 *   uri_paths = {
 *     "canonical" = "/filename/{fid}/{random}"
 *   }
 * )
 */
class FileNameRestResource extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   *
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->database = $database;
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
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * Responds to GET requests.
   * Returns a filename for specified fid file.
   *
   * @param fid
   *   file entity fid.
   * @param random
   *   any random number or string.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Array of file name for successful request or exception message.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($fid, $random) {

    if(!empty($_GET['fids'])){
      if ($fid && $random) {
        $fids_get = explode(',',$_GET['fids']);
        $record = $this->database->select('file_managed', 'f')->fields('f', ['fid'])->condition('f.fid', $fids_get, 'IN')->execute();
        $records = $record->fetchAll();
        $fids = array();
        $fids_final = $fids_get;

        if($record){
          foreach($records as $each){
            $fids[] =$each->fid;
          }
        }
        if($fids){
          foreach($fids_get as $key=>$f){
            if(!in_array($f,$fids)){
              unset($fids_final[$key]);
            }
          }
        }

        if (!empty($fids)) {
          return new ResourceResponse(array('fids'=>$fids_final));
        }
        throw new NotFoundHttpException(t('File entry with ID @id was not found', array('@id' => $fid)));
      }
    }

    // Return filename.
    if ($fid && $random) {
      $record = $this->database->select('file_managed', 'f')->fields('f', ['filename'])->condition('f.fid', $fid)->execute();
      $record = $record->fetchField();

      if (!empty($record)) {
        return new ResourceResponse(array('filename'=>$record));
      }
      throw new NotFoundHttpException(t('File entry with ID @id was not found', array('@id' => $fid)));
    }

    return new ResourceResponse("Implement REST State GET!");
  }

}
