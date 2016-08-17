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
   *
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

}
