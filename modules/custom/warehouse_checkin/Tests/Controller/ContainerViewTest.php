<?php

/**
 * @file
 * Contains \Drupal\warehouse_checkin\Tests\ContainerView.
 */

namespace Drupal\warehouse_checkin\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the warehouse_checkin module.
 */
class ContainerViewTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "warehouse_checkin ContainerView's controller functionality",
      'description' => 'Test Unit for module warehouse_checkin and controller ContainerView.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests warehouse_checkin functionality.
   */
  public function testContainerView() {
    // Check that the basic functions of module warehouse_checkin.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
