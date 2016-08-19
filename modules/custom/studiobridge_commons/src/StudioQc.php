<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioQc.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class StudioQc.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioQc implements StudioQcInterface {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage service.
   */
  protected $nodeStorage;

  /**
   * The user storage service.
   */
  protected $userStorage;

  /**
   * The entity type manager service.
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser = array();

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;


  /**
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager, AccountProxyInterface $current_user, QueryFactory $query_factory, StateInterface $state) {

    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->queryFactory = $query_factory;
    $this->state = $state;

  }


  /*
   * Helper function to add record to qc_records table.
   */
  public function addQcRecord($pid, $sid, $qc_note, $qc_state) {

    $table_exists = $this->database->schema()->tableExists('studio_products_qc_records');

    if ($table_exists) {
      $uid = $this->currentUser->id();
      $this->database->insert('studio_products_qc_records')
        ->fields(array(
          'pid' => $pid,
          'sid' => $sid,
          'qc_note' => $qc_note,
          'qc_state' => $qc_state,
          'uid' => $uid,
          'create' => REQUEST_TIME
        ))
        ->execute();

    }
  }

  /*
   * Helper function, to update notes of the qc records.
   */
  public function updateQcRecordNote($sid, $pid, $note){

    $table_exists = $this->database->schema()->tableExists('studio_products_qc_records');
    $uid = $this->currentUser->id();

    if ($table_exists) {
      $this->database->update('studio_products_qc_records') // Table name no longer needs {}
        ->fields(array(
          'qc_note' => $note,
        ))
        ->condition('sid', $sid)
        ->condition('pid', $pid)
        ->execute();
    }

  }

  /*
   * Helper function, to get qc records of a session.
   */
  public function getQcRecordsBySession($sid, $fields = array('pid')){
    $result = $this->database->select('studio_products_qc_records', 'spqr')
      ->fields('spqr', $fields)
      ->condition('spqr.sid', $sid);

    $records = $result->execute()->fetchAll();

    return $records;
  }

  /*
   * Helper function, to get qc records of today sessions(sessions created today)
   */
  public function getQcRecordsFromTodaySessions($fields = array('pid')){
    $records = array();

    $day_start = strtotime(date('Y-m-d 00:00:00', time()));
    $day_end = strtotime(date('Y-m-d 23:59:59', time()));

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('sessions'), 'IN')
      ->sort('created', 'DESC')
      ->condition('created', array($day_start, $day_end), 'BETWEEN')
      ->execute();

    if($result){
      $result = $this->database->select('studio_products_qc_records', 'spqr')
        ->fields('spqr', $fields)
        ->condition('spqr.sid', $result, 'IN');

      return $records = $result->execute()->fetchAll();

    }

    return $records;
  }

  /*
   * Helper function, to get all product id's from to particular session which are not checked in QC DESK.
   */
  public function getSessionProductsNotUpdatedInQcDesk($sid){
    $updatedProducts = $this->getQcRecordsBySession($sid);
    $updatedPids = array();

    foreach($updatedProducts as $each){
      $updatedPids[] = $each->pid;
    }

    $session = $this->nodeStorage->load($sid);

    $pids = array();
    $products = $session->field_product->getValue();
    foreach($products as $product){
      $pids[] = $product['target_id'];
    }

    return array_diff($pids, $updatedPids);

  }


  /*
   * Helper function, to get all product id's from to particular session which are not checked in QC DESK.
   */
  public function getTodaySessionsProductsNotUpdatedInQcDesk(){
    $updatedProducts = $this->getQcRecordsFromTodaySessions();
    $updatedPids = array();

    foreach($updatedProducts as $each){
      $updatedPids[] = $each->pid;
    }

    $day_start = strtotime(date('Y-m-d 00:00:00', time()));
    $day_end = strtotime(date('Y-m-d 23:59:59', time()));

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('sessions'), 'IN')
      ->sort('created', 'DESC')
      ->condition('created', array($day_start, $day_end), 'BETWEEN')
      ->execute();


    $pids = array();
    $sessions = $this->nodeStorage->loadMultiple($result);

    foreach($sessions as $session){
      $products = $session->field_product->getValue();
      foreach($products as $product){
        $pids[] = $product['target_id'];
      }
    }


    return array_diff($pids, $updatedPids);
  }

  /*
   * Helper function, to get required fields from products for QC page.
   */
  public function getProductsData($pids){

    if($pids){
      $products = $this->nodeStorage->loadMultiple($pids);

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

  /*
   *  Helper function, to get product node ids from today sessions.
   */
  public function getAllProductIdsFromTodaySessions(){

    $day_start = strtotime(date('Y-m-d 00:00:00', time()));
    $day_end = strtotime(date('Y-m-d 23:59:59', time()));

    $result = \Drupal::entityQuery('node')
      ->condition('type', array('sessions'), 'IN')
      ->sort('created', 'DESC')
      ->condition('created', array($day_start, $day_end), 'BETWEEN')
      ->execute();


    $pids = array();
    $sessions = $this->nodeStorage->loadMultiple($result);

    foreach($sessions as $session){
      $products = $session->field_product->getValue();
      foreach($products as $product){
        $pids[] = $product['target_id'];
      }
    }

    return $pids;
  }


  /*
   * Helper function, to get product node ids from particular session.
   */
  public function getAllProductIdsFromSession($sid){

    $pids = array();
    $sessions = $this->nodeStorage->load($sid);

    foreach($sessions as $session){
      $products = $session->field_product->getValue();
      foreach($products as $product){
        $pids[] = $product['target_id'];
      }
    }

    return $pids;
  }

}
