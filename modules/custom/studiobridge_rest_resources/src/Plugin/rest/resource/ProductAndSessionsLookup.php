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

  protected $nodeStorage;

  protected $studioModels;

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
  AccountProxyInterface $current_user, $entity_manager, $studioModels) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->nodeStorage = $entity_manager->getStorage('node');

    $this->currentUser = $current_user;

    $this->studioModels = $studioModels;
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
    $container->get('entity_type.manager'),
    $container->get('studio.models')
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
  if ($type == 'products') {
    $bundles = array('products', 'unmapped_products');

  }
  elseif($type == 'sessions') {
    $bundles = array('sessions');
  }elseif($type == 'models'){

    $data = $this->studioModels->getModels();

    return new ResourceResponse($data);

  }elseif($type == 'productsQC'){

    $bundles = array('products');

  }

  $total_count = \Drupal::entityQuery('node')
  ->condition('type', $bundles, 'IN')
  ->count()->execute();

  $order_by = $_GET['order'][0]['column'];
  $order_direction = $_GET['order'][0]['dir'];

  switch ($order_by) {
    case  0:
    $order_field = 'nid';
    break;
    case  1:
    $order_field = 'field_concept_name';
    break;
    case  2:
    $order_field = 'title';
    break;
    case  3:
    $order_field = 'field_color_variant';
    break;
    default:
    $order_field = 'nid';

  }

  if (!empty($_GET['search']['value'])) {
    $value = $_GET['search']['value'];

    $query = \Drupal::entityQuery('node');
    $query->condition('type', $bundles, 'IN');

    // Or condition for product fields
    $orCondition = $query->orConditionGroup();
    $orCondition->condition('field_color_variant', "%$value%", 'LIKE');
    $orCondition->condition('title', "%$value%", 'LIKE');
    $orCondition->condition('field_concept_name', "%$value%", 'LIKE');
    $orCondition->condition('nid', "%$value%", 'LIKE');
    $query->condition($orCondition);
    $query->range(0, 10);
    $query->sort($order_field, strtoupper($order_direction));
    $result = $query->execute();


  }
  else {
    $result = \Drupal::entityQuery('node')
    ->condition('type', $bundles, 'IN')
    ->sort($order_field, strtoupper($order_direction))
    ->range(0, 10)
    ->execute();
  }


  //load all the nodes from the result
  if ($result) {
    if ($type=='productsQC'){
      $productsQC = $this->getProductsQC($result);
    }else{
      $products = $this->getProducts($result);
    }

    //if results are not empty load each node and get info
    if ($products) {

      $data = array(
        'draw' => intval($_GET['draw']),
        'recordsTotal' => $total_count,
        'recordsFiltered' => count($result),
        'data' =>
        $products,
      );

      return new ResourceResponse($data);
    }

    if($productsQC){
      $data = array(
        'group'=>'Today April 23',
        'list' =>
        $productsQC,
      );
  return new ResourceResponse($data);
    }
  }
  else {

    $data = array(
      'draw' => intval($_GET['draw']),
      'recordsTotal' => $total_count,
      'recordsFiltered' => 0,
      'data' => array(),
    );

    return new ResourceResponse($data);

  }

  // Throw an exception if it is required.
  // throw new HttpException(t('Throw an exception if it is required.'));
  return new ResourceResponse("Implement REST State GET!");
}

public function getProducts($result) {

  $products = Node::loadMultiple($result);
  $total_images = 0;
  $data = array();


  foreach ($products as $current_product) {
    // Get product type; mapped or unmapped
    $bundle = $current_product->bundle();
    // Map unmapped & mapped products
    if ($bundle == 'products') {
      $cp = $current_product->toArray();
      $pid = $current_product->id();

      $total_images = count($cp['field_images']);

      // Get Concept
      $concept = $current_product->field_concept_name->getValue();

      $product_concept = $current_product->field_concept_name->getValue();
      if ($product_concept) {
        $concept = $product_concept[0]['value'];
        $theme_path = base_path().''.drupal_get_path('theme', 'studiobridge');
        //$theme_path = base_path().
        $img_file_name = str_replace(' ','', $concept);
        $concept_img = $theme_path.'/images/brands/brand_logo_'.strtolower($img_file_name).'.png';
        $concept_img = '<img height="20px" src="'.$concept_img.'">';
      }

      $product_color_variant = $current_product->field_color_variant->getValue();
      if ($product_color_variant) {
        $color_variant = $product_color_variant[0]['value'];
      }

      $product_state = $current_product->field_state->getValue();
      if ($product_state) {
        $state = $product_state[0]['value'];
      }

      $product_title = $current_product->title->getValue();
      $title = '';
      if ($product_title) {
        $title = $product_title[0]['value'];
      }

      $view_link = '<a class="btn btn-xs " href="/view-product/' . $pid . '">View</a>';

      $data[] = array(
        $pid,
        $concept_img,
        $title,
        $color_variant,
        $total_images,
        $view_link
      );


    }
    elseif ($bundle == 'unmapped_products') {
      $cpu = $current_product->toArray();
      $total_images = count($cpu['field_images']);


      $concept = 'Unmapped';
      $pid = $current_product->id();

      $product_state = $current_product->field_state->getValue();
      if ($product_state) {
        $state = $product_state[0]['value'];
      }

      $product_title = $current_product->title->getValue();
      $title = '';
      if ($product_title) {
        $title = $product_title[0]['value'];
      }

      $view_link = '<a class="btn btn-xs " href="/view-product/' . $pid . '">View</a>';


      $data[] = array(
        $pid,
        $concept,
        $title,
        '',
        $total_images,
        $view_link
      );


    }
  }


  return $data;
}


public function getProductsQC($result) {

  $products = Node::loadMultiple($result);
  $total_images = 0;
  $data = array();


  foreach ($products as $current_product) {
    // Get product type; mapped or unmapped
    $bundle = $current_product->bundle();
    // Map unmapped & mapped products
    if ($bundle == 'products') {
      $cp = $current_product->toArray();
      $pid = $current_product->id();

      $total_images = count($cp['field_images']);

      // Get Concept
      $concept = $current_product->field_concept_name->getValue();

      $product_concept = $current_product->field_concept_name->getValue();
      if ($product_concept) {
        $concept = $product_concept[0]['value'];
        $theme_path = base_path().''.drupal_get_path('theme', 'studiobridge');
        //$theme_path = base_path().
        $img_file_name = str_replace(' ','', $concept);
        $concept_img = $theme_path.'/images/brands/brand_logo_'.strtolower($img_file_name).'.png';
        $concept_img = '<img height="20px" src="'.$concept_img.'">';
      }

      $product_color_variant = $current_product->field_color_variant->getValue();
      if ($product_color_variant) {
        $color_variant = $product_color_variant[0]['value'];
      }

      $product_state = $current_product->field_state->getValue();
      if ($product_state) {
        $state = $product_state[0]['value'];
      }

      $product_title = $current_product->title->getValue();
      $title = '';
      if ($product_title) {
        $title = $product_title[0]['value'];
      }

      $view_link = '<a class="btn btn-xs " href="/view-product/' . $pid . '">View</a>';

      $data[] = array(
        'id'=>$pid,
        'concept'=>$concept_img,
        'title'=>$title,
        'colorvariant'=>$color_variant,
        'totalimages'=>$total_images,
        'view'=>$view_link
      );


    }
    elseif ($bundle == 'unmapped_products') {
      $cpu = $current_product->toArray();
      $total_images = count($cpu['field_images']);


      $concept = 'Unmapped';
      $pid = $current_product->id();

      $product_state = $current_product->field_state->getValue();
      if ($product_state) {
        $state = $product_state[0]['value'];
      }

      $product_title = $current_product->title->getValue();
      $title = '';
      if ($product_title) {
        $title = $product_title[0]['value'];
      }

      $view_link = '<a class="btn btn-xs " href="/view-product/' . $pid . '">View</a>';


      $data[] = array(
        'id'=>$pid,
        'concept'=>$concept,
        'title'=>$title,
        'colorvariant'=>"",
        'totalimages'=>$total_images,
        'view'=>$view_link
      );


    }
  }


  return $data;
}


}
