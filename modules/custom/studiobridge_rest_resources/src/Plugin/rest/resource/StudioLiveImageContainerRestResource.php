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
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

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
  public $content2 = '';
  public $pnid;

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
    //$content = array('content' =>$this->content);

    $block1 = \Drupal\block\Entity\Block::load('currentsessionviewblock');
    $block_content1 = \Drupal::entityManager()
      ->getViewBuilder('block')
      ->view($block1);

    $content['block1'] = \Drupal::service('renderer')->renderPlain($block_content1);

    $content['block2'] = $this->content2;

    $content['block3'] = $this->getLastImages($this->pnid,$identifier);

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
      $this->pnid = $node_id;
      //$block = \Drupal::service('renderer')->renderPlain(views_embed_view('individual_project_view', 'block_2',$node_id),false);
      //$this->content = (string) $block;

      //product_by_nid
      //$block = \Drupal::service('renderer')->renderPlain(views_embed_view('product_by_nid', 'block_1',$node_id),false);
      $this->content2 = $this->getBlockData2($node_id,$identifier);

    }else{
      $block = '<span color="red">No product scanned</span>';
      $this->content = (string) $block;
    }
  }

  public function getBlockData2($nid,$identifier){

    $output = '<div class="live-shoot-product-container">';

    $product = \Drupal\node\Entity\Node::load($nid);
    $bundle = $product->bundle();
    $style_no = '';
    $color_variant = '';
    $gender = '';
    $description = '';
    $color = '';


    if($bundle == 'unmapped_products'){
      $concept = 'Unmapped';
    }else{

      $product_concept = $product->field_concept_name->getValue();
      if($product_concept){
        $concept = $product_concept[0]['value'];
      }

      $product_style_no = $product->field_style_family->getValue();
      if($product_style_no){
        $style_no = $product_style_no[0]['value'];
      }
      $product_color_variant = $product->field_color_variant->getValue();
      if($product_color_variant){
        $color_variant = $product_color_variant[0]['value'];
      }
      $product_gender = $product->field_gender->getValue();
      if($product_gender){
        $gender = $product_gender[0]['value'];
      }
      $product_color = $product->field_color_name->getValue();
      if($product_color){
        $color = $product_color[0]['value'];
      }

      $product_description = $product->field_description->getValue();
      if($product_description){
        $description = $product_description[0]['value'];
      }

    }


    $output_array = array('concept' => $concept);

//    $output .= '<div class="product-div1"> Concept: '.$concept.'</div>';
//    $output .= '<div class="product-div1"> Identifier: '.$identifier.'</div>';
//    $output .= '<div class="product-div2"> Style No: '.$style_no.'</div>';
//    $output .= '<div class="product-div3"> Color Variant: '.$color_variant.'</div>';
//      $output .= '<div class="product-wrapper-2">';
//        $output .= '<div class="product-div4"> Gender: '.$gender.'</div>';
//        $output .= '<div class="product-div5"> Color: '.$color.'</div>';
//        $output .= '<div class="product-div5"> Description: '.$description.'</div>';
//        //$output .= '<div class="product-div5"> Color: '.$color.'</div>';
//      $output .= '</div>';
//    $output .= '</div>';
    $output = $output_array;
    return $output;
  }

  public function getLastImages($nid,$identifier = null){

    $sid = studiobridge_store_images_open_session_recent();
    if($sid){
      $last_scanned_fid = \Drupal::state()->get('last_img_sent_'.$sid.'_'.$nid,false);
      // todo :
      if($last_scanned_fid){
        $record = db_query("SELECT fid FROM {studio_file_transfers} where pid=:pid AND  sid=:sid AND fid > :fid",array(':pid'=> $nid,':sid'=>$sid,':fid' => $last_scanned_fid))->fetchAll();
      }else{
        $record = db_query("SELECT fid FROM {studio_file_transfers} where pid=:pid AND  sid=:sid",array(':pid'=> $nid,':sid'=>$sid))->fetchAll();
      }
      if ($record) {
        $image_uri = array();
        foreach ($record as $img) {
          $fid = $img->fid;
          $file = File::load($fid);
          //$image_uri[$fid] = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());

          $file_name = $file->filename->getValue();
          $file_name = $file_name[0]['value'];
          $image_uri_value = ImageStyle::load('live_shoot_preview')->buildUrl($file->getFileUri());
          $image_uri[$fid] = array('uri'=>$image_uri_value,'name'=>$file_name);

        }
        \Drupal::state()->set('last_img_sent_'.$sid.'_'.$nid,$fid);
        return $image_uri;
      }
    }
    return false;
  }

}
