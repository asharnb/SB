<?php

/**
 * @file
 * Contains \Drupal\studio_session_operations\Controller\CsvMakerController.
 */

namespace Drupal\studio_session_operations\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\node\Entity\Node;
use \Drupal\user\Entity\User;

/**
 * Class CsvMakerController.
 *
 * @package Drupal\studio_session_operations\Controller
 */
class CsvMakerController extends ControllerBase {
  /**
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function hello($id, $type, $concept) {

    $head = array('Identifier', 'Photographer', 'Shoot-Date', 'Color-Variant', 'Session');
    $unMappedHead = array('Identifier', 'Photographer', 'Shoot-Date');

    // Load session
    $session = NODE::load($id);

    if ($session) {
      $bundle = $session->bundle();
      if ($bundle == 'sessions') {
        $pids = $session->field_product->getValue();
        $rows = $this->getMapped($session, $pids, $type, $concept);
        $sid = $session->id();

        if($type == 'unmapped_products'){
          $head = $unMappedHead;
          $file_name = 'Unmapped-shootlist-'.$sid.'.csv';
        }else{
          if($concept){
            $file_name = $concept.' Shoot List-'.$sid.'.csv';
          }else{
            $file_name = 'Mapped-shootlist-'.$sid.'.csv';
          }
        }

        $this->array_to_csv_download($head, $rows,$file_name);

      }
    }

  }

  public function array_to_csv_download($head, $array, $filename = "export.csv", $delimiter = ";") {
    // open raw memory as file so no temp files needed, you might run out of memory though
    ob_start();
    $f = fopen('php://output', 'w');
    $delimiter = ";";

    fputcsv($f, $head, $delimiter);

    // loop over the input array
    foreach ($array as $line) {
      // generate csv lines from the inner arrays
      fputcsv($f, $line, $delimiter);
    }

    // reset the file pointer to the start of the file
    //fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: application/csv');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    // make php send the generated csv lines to the browser

    //header('Content-Type: text/csv; charset=utf-8');

    fpassthru($f);
    ob_end_flush();
    exit();
  }

  /*unmapped_products, products
   *
   */
  public function getMapped($session, $pids, $type, $concept) {
    //print_r($session->toArray());

    $photographer = $session->field_photographer->getValue();
    $sid = $session->id();
    $rows = array();
    $product_concept = '';

    if ($photographer) {
      $photographer = User::load($photographer[0]['target_id']);
      $photographer = $photographer->label();
    }

    $tmp = array();
    if ($pids) {
      foreach ($pids as $pid) {
        $tmp[] = $pid['target_id'];
      }

      $product_objects = Node::loadMultiple($tmp);

      if ($product_objects) {
        foreach ($product_objects as $product) {
          $bundle = $product->bundle();
          if ($bundle == 'unmapped_products' && $type == 'unmapped_products') {

            $title = $product->title->getValue();
            if ($title) {
              $title = $title[0]['value'];
            }

            $created = $product->created->getValue();
            $date = date('d-m-Y', $created[0]['value']);

            $rows[] = array(trim($title), $photographer, $date);
          }


          if ($bundle == 'products' && $type == 'products') {

            if($concept){
              $product_concept = $product->field_concept_name->getValue();
              if($product_concept){
                $product_concept = $product_concept[0]['value'];
                if(strtolower($product_concept) == strtolower($concept)){


                  $title = $product->title->getValue();

                  if ($title) {
                    $title = $title[0]['value'];
                  }

                  $created = $product->created->getValue();
                  $date = date('d-m-Y', $created[0]['value']);

                  $color_variant = '';
                  // Get color variant.
                  $product_color_variant = $product->field_color_variant->getValue();
                  if ($product_color_variant) {
                    $color_variant = $product_color_variant[0]['value'];
                  }
                  if (!$color_variant) {
                    if ($title) {
                      $color_variant = $title[0]['value'];
                    }
                  }

                  $rows[] = array(trim($title), $photographer, $date, $color_variant, $sid);

                }
              }
            }else{


              $title = $product->title->getValue();

              if ($title) {
                $title = $title[0]['value'];
              }

              $created = $product->created->getValue();
              $date = date('d-m-Y', $created[0]['value']);

              $color_variant = '';
              // Get color variant.
              $product_color_variant = $product->field_color_variant->getValue();
              if ($product_color_variant) {
                $color_variant = $product_color_variant[0]['value'];
              }
              if (!$color_variant) {
                if ($title) {
                  $color_variant = $title[0]['value'];
                }
              }

              $rows[] = array(trim($title), $photographer, $date, $color_variant, $sid);


            }



          }


        }
      }

    }

    return $rows;
  }

}
