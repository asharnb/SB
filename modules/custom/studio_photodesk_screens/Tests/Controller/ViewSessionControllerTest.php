<?php

/**
 * @file
 * Contains \Drupal\studio_photodesk_screens\Tests\ViewSessionController.
 */

namespace Drupal\studio_photodesk_screens\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the studio_photodesk_screens module.
 */
class ViewSessionControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "studio_photodesk_screens ViewSessionController's controller functionality",
      'description' => 'Test Unit for module studio_photodesk_screens and controller ViewSessionController.',
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
   * Tests studio_photodesk_screens functionality.
   */
  public function testViewSessionController() {
    // Check that the basic functions of module studio_photodesk_screens.
    $this->assertEquals(TRUE, TRUE, 'Test example.');
  }

}
