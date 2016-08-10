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
use \Drupal\node\Entity\Node;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "product_and_sessions_lookup",
 *   label = @Translation("Product and sessions lookup"),
 *   uri_paths = {
 *     "canonical" = "/screens/{type}"
 *   }
 * )
 */
class ProductAndSessionsLookup extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
  public function get($type) {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $product_data = array();
    if($type == 'products'){
      $bundles = array('products', 'unmapped_products');

    }else{
      $bundles = array('sessions');
    }

    $total_count = \Drupal::entityQuery('node')
      ->condition('type', $bundles, 'IN')
      ->sort('created', 'DESC')
      ->count()->execute();


    if(!empty($_GET['search']['value'])){
      $result = \Drupal::entityQuery('node')
        ->condition('type', $bundles, 'IN')
        ->condition('title',$_GET['search']['value'],'LIKE')
        ->sort('created', 'DESC')
        ->execute();
    }else{
      $result = \Drupal::entityQuery('node')
        ->condition('type', $bundles, 'IN')
        ->sort('created', 'DESC')
        ->execute();
    }




    //load all the nodes from the result
    if ($result) {
      $products = $this->getProducts($result);


      //if results are not empty load each node and get info
      if ($products) {
        foreach ($products as $product) {
          //$product_data[] = $product->toArray();
        }


        $data =  array(
          'draw' => intval( $_GET['draw'] ),
          'recordsTotal' => $total_count,
          'recordsFiltered' => $total_count,
          'data' =>
            array (
              0 =>
                array (
                  0 => '1111',
                  1 => 'Satou',
                  2 => 'Accountant',
                  3 => 'Tokyo',
                  4 => '28th Nov 08',
                  5 => '$162,700',
                ),
              1 =>
                array (
                  0 => 'Cedric',
                  1 => 'Kelly',
                  2 => 'Senior Javascript Developer',
                  3 => 'Edinburgh',
                  4 => '29th Mar 12',
                  5 => '$433,060',
                ),
            ),
        );

        return new ResourceResponse($data);
      }
    }


    // Throw an exception if it is required.
    // throw new HttpException(t('Throw an exception if it is required.'));
    return new ResourceResponse("Implement REST State GET!");
  }

  public function get1($type){
    \Drupal::service('page_cache_kill_switch')->trigger();

    $requestData = $_GET;
    if(!empty($requestData['search']['value'])){
      $data =  array(
        'draw' => intval( $requestData['draw'] ),
        'recordsTotal' => 11,
        'recordsFiltered' => 2,
        'data' =>
          array (
            0 =>
              array (
                0 => '1111',
                1 => 'Satou',
                2 => 'Accountant',
                3 => 'Tokyo',
                4 => '28th Nov 08',
                5 => '$162,700',
              ),
            1 =>
              array (
                0 => 'Cedric',
                1 => 'Kelly',
                2 => 'Senior Javascript Developer',
                3 => 'Edinburgh',
                4 => '29th Mar 12',
                5 => '$433,060',
              ),
          ),
      );
    }else{

      $data = array(
        'draw' => intval( $requestData['draw'] ),
        'recordsTotal' => 11,
        'recordsFiltered' => 11,
        'data' =>
          array (
            0 =>
              array (
                0 => 'Airi',
                1 => 'Satou',
                2 => 'Accountant',
                3 => 'Tokyo',
                4 => '28th Nov 08',
                5 => '$162,700',
              ),
            1 =>
              array (
                0 => 'Angelica',
                1 => 'Ramos',
                2 => 'Chief Executive Officer (CEO)',
                3 => 'London',
                4 => '9th Oct 09',
                5 => '$1,200,000',
              ),
            2 =>
              array (
                0 => 'Ashton',
                1 => 'Cox',
                2 => 'Junior Technical Author',
                3 => 'San Francisco',
                4 => '12th Jan 09',
                5 => '$86,000',
              ),
            3 =>
              array (
                0 => 'Bradley',
                1 => 'Greer',
                2 => 'Software Engineer',
                3 => 'London',
                4 => '13th Oct 12',
                5 => '$132,000',
              ),
            4 =>
              array (
                0 => 'Brenden',
                1 => 'Wagner',
                2 => 'Software Engineer',
                3 => 'San Francisco',
                4 => '7th Jun 11',
                5 => '$206,850',
              ),
            5 =>
              array (
                0 => 'Brielle',
                1 => 'Williamson',
                2 => 'Integration Specialist',
                3 => 'New York',
                4 => '2nd Dec 12',
                5 => '$372,000',
              ),
            6 =>
              array (
                0 => 'Bruno',
                1 => 'Nash',
                2 => 'Software Engineer',
                3 => 'London',
                4 => '3rd May 11',
                5 => '$163,500',
              ),
            7 =>
              array (
                0 => 'Caesar',
                1 => 'Vance',
                2 => 'Pre-Sales Support',
                3 => 'New York',
                4 => '12th Dec 11',
                5 => '$106,450',
              ),
            8 =>
              array (
                0 => 'Cara',
                1 => 'Stevens',
                2 => 'Sales Assistant',
                3 => 'New York',
                4 => '6th Dec 11',
                5 => '$145,600',
              ),
            9 =>
              array (
                0 => 'Cedric',
                1 => 'Kelly',
                2 => 'Senior Javascript Developer',
                3 => 'Edinburgh',
                4 => '29th Mar 12',
                5 => '$433,060',
              ),
            10 =>
              array (
                0 => 'xxx',
                1 => 'Kelly',
                2 => 'Senior Javascript Developer',
                3 => 'Edinburgh',
                4 => '29th Mar 12',
                5 => '$433,060',
              ),
          ),
      );
    }
    return new ResourceResponse($data);
  }

  public function getProducts($result){

    $products = Node::loadMultiple($result);

    

    return array();
  }

}
