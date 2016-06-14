<?php

/**
 * @file
 * Contains \Drupal\studio_session_operations\Tests\MapUnMappedProducts.
 */

namespace Drupal\studio_session_operations\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the studio_session_operations module.
 */
class MapUnMappedProductsTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "studio_session_operations MapUnMappedProducts's controller functionality",
      'description' => 'Test Unit for module studio_session_operations and controller MapUnMappedProducts.',
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
   * Tests studio_session_operations functionality.
   */
  public function testMapUnMappedProducts() {
    // Check that the basic functions of module studio_session_operations.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
