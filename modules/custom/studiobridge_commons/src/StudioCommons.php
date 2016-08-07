<?php

/**
 * @file
 * Contains \Drupal\studiobridge_commons\StudioCommons.
 */

namespace Drupal\studiobridge_commons;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class StudioCommons.
 *
 * @package Drupal\studiobridge_commons
 */
class StudioCommons implements StudioCommonsInterface {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage service.
   */
  public $nodeStorage;

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
   * Constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager, AccountProxyInterface $current_user, QueryFactory $query_factory) {

    $this->entityTypeManager = $entityTypeManager;

    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->queryFactory = $query_factory;

  }

  /*
   * @param config
   *   studio settings configurations.
   * @param concept
   *   concept name, like max, unmapped..
   * @param sid
   *   session node nid.
   * @param session
   *   session node object.
   */
  public function sendConceptShootlist($config, $concept, $sid, $session){
    global $base_insecure_url;

    $to = '';
    $title = $session->title->getValue();
    if ($title) {
      $title = $title[0]['value'];
    }

    $mailManager = \Drupal::service('plugin.manager.mail');

    $cc = $config->get('to_email');
    if(empty($cc)){
      $cc = 'ashar.babar@landmarkgroup.com, krishnakanth@valuebound.com';
    }
    $concept_machine_string = strtolower(str_replace(' ','', $concept));
    $concept_to_email = $body = $config->get($concept_machine_string.'_email');
    if($concept_to_email){
      $to = $concept_to_email;
    }

    if($to){
      $to = $to.', '.$cc;
      $to = trim($to);
    }else{
      $to = $cc;
      $to = trim($to);
    }

    $module = 'studio_session_operations';
    $key = 'shootlist';

    if($concept == 'unmapped'){
      $link = $base_insecure_url."/session-shootlist/$sid/unmapped_products/0";
    }else{
      $url_concept = str_replace(' ', '+', $concept);
      $link = $base_insecure_url."/session-shootlist/$sid/products/$url_concept";
    }


    $body = $config->get('body');
    if(empty($body)){
      $params['message'] = 'Download the shootlist csv file here ' . $link . '<br />';
    }else{
      // replace tokens.
      $params['message'] = str_replace(array('@session_name@', '@shootlist_link@'),array($title, $link),$body);
    }

    $subject = $config->get('subject');
    if(empty($subject)){
      $params['node_title'] = $title;
    }else{
      // replace tokens
      $params['node_title'] = str_replace(array('@session_name@'),array($title),$subject);
    }


    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    if ($result['result'] !== true) {
      drupal_set_message(t('There was a problem sending automated shootlist email, please download shootlists manually.'), 'error');
    }
    else {
      drupal_set_message(t('Automated shootlists have been sent.'));
    }
  }

}
