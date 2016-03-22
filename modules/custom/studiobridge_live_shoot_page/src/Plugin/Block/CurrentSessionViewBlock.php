<?php

/**
 * @file
 * Contains \Drupal\studiobridge_live_shoot_page\Plugin\Block\CurrentSessionViewBlock.
 */

namespace Drupal\studiobridge_live_shoot_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\node\Entity\Node;


/**
 * Provides a 'CurrentSessionViewBlock' block.
 *
 * @Block(
 *  id = "current_session_view_block",
 *  admin_label = @Translation("Current session view block"),
 * )
 */
class CurrentSessionViewBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['current_session_view_block']['#cache']['max-age'] = 0;

    $session_id = studiobridge_store_images_open_session_recent();

    if(!$session_id){
      $build['current_session_view_block']['#markup'] = '--NO session found--' . date('Y m d - H:i:s');
    }else{

      $session = Node::load($session_id);
      //$session = $session->toArray();
      $title = $session->title->getValue();
      $status = $session->field_status->getValue();
      $status = isset($status[0]['value']) ? $status[0]['value'] : '';
      $products =  $session->field_product->getValue();
      $last_scanned_product_title = '';
      $last_scanned_product_stauts = '';
      $last_scanned_product_images = 0;

      $last_scanned_product_nid = \Drupal::state()->get('last_scan_product_nid'.$session->getOwnerId().'_'.$session_id);
      $last_scanned_product = Node::load($last_scanned_product_nid);
      if($last_scanned_product){
        $last_scanned_product_stauts = $last_scanned_product->field_state->getValue();
        $last_scanned_product_stauts = $last_scanned_product_stauts[0]['value'];
        $last_scanned_product_title  = $last_scanned_product->title->getValue();
        $last_scanned_product_title  = $last_scanned_product_title[0]['value'];
        $last_scanned_product_images  = count($last_scanned_product->field_images->getValue());
      }

      $output = '<div id="current-open-session-container">';
        $output .= '<div id="current-open-session-div1">';
          $output .= '<h4>Session</h4>';
          $output .= '<span>'.$status.'</span>';
        $output .= '</div>';
        $output .= '<div id="current-open-session-div2">';
          $output .= '<h4>Current Session</h4>';
          $output .= '<span id="current-open-session-session-name">'.$title[0]['value'].'</span>';
        $output .= '</div>';
      $output .= '<div id="current-open-session-div3">';
        $output .= '<h4>Total Products</h4>';
        $output .= '<span id="current-open-session-session-name">'.count($products).'</span>';
      $output .= '</div>';

      $output .= '<div id="current-open-session-div4">';
      $output .= '<h4>Aproved</h4>';
      $output .= '<span id="current-open-session-session-name">*Yet to add*</span>';
      $output .= '</div>';

      $output .= '<div id="current-open-session-div5">';
      $output .= '<h4>Rejected</h4>';
      $output .= '<span id="current-open-session-session-name">*Yet to add*</span>';
      $output .= '</div>';


      $output .= '<div id="current-open-session-div5">';
      $output .= '<h3>Current Product</h3>';
      $output .= '<span id="current-open-session-session-name">'.$last_scanned_product_title.'</span>';
      $output .= '</div>';

      $output .= '<div id="current-open-session-div5">';
      $output .= '<h4>Product Status</h4>';
      $output .= '<span id="current-open-session-session-name">'.$last_scanned_product_stauts.'</span>';
      $output .= '</div>';

      $output .= '<div id="current-open-session-div5">';
      $output .= '<h4>Images shot</h4>';
      $output .= '<span id="current-open-session-session-name">'.$last_scanned_product_images.'</span>';
      $output .= '</div>';

      $output .= '</div>';

      //$build['current_session_view_block']['#markup'] = '<pre>'.print_r($session->getTitle()).'</pre>';
      $build['current_session_view_block']['#markup'] = $output;
    }

    return $build;
  }

}
