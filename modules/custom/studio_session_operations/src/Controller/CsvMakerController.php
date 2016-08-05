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

    $head = array('Identifier', 'Photographer', 'Shoot-Date', 'Color-Variant', 'Session', 'size name', 'size variant');
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

        $this->array_to_csv_download($head, $rows,$file_name, ',');

      }
    }

  }

  public function array_to_csv_download($head, $array, $filename = "export.csv", $delimiter = ";") {
    // open raw memory as file so no temp files needed, you might run out of memory though
    ob_start();
    $f = fopen('php://output', 'w');
    $delimiter = ",";

    fputcsv($f, $head, $delimiter,'"');

    // loop over the input array
    foreach ($array as $line) {
      // generate csv lines from the inner arrays
      fputcsv($f, $line, $delimiter,'"');
    }

    $csv = stream_get_contents($f);
    /* converting CSV content encoding inside */
    $csv = mb_convert_encoding($csv, 'iso-8859-2', 'utf-8');


    // reset the file pointer to the start of the file
    //fseek($f, 0);
    // tell the browser it's going to be a csv file

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');

    //header('Content-Type: text/csv; charset=utf-8');

    //fpassthru($f);
    echo $csv;
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

                  // add size name & size variant.
                  $identifier = $title;
                  $size_name = '';
                  $size_variant = '';

                  // check the product is multi sku or not
                  // if barcode exists we can treat this product as multi sku
                  $field_barcode = $product->field_barcode->getValue();
                  $barcodes = array();

                  if($field_barcode){
                    foreach($field_barcode as $v){
                      $barcodes[] = $v['value'];
                    }
                  }

                  $field_size_variant = $product->field_size_variant->getValue();
                  $size_variant_values = array();

                  if($field_size_variant){
                    foreach($field_size_variant as $s){
                      $size_variant_values[] = $s['value'];
                    }
                  }

                  $field_size_name = $product->field_size_name->getValue();
                  $size_name_values = array();
                  if($field_size_name){
                    foreach($field_size_name as $n){
                      $size_name_values[] = $n['value'];
                    }
                  }

                  if($field_barcode || $field_size_variant){

                    // check identifier available in barcode array.
                    // else available in size variant.
                    if(in_array($identifier, $barcodes)){
                      $pos = array_search($identifier, $barcodes);
                      $size_name = $size_name_values[$pos];
                      $size_variant = $size_variant_values[$pos];

                    }elseif(in_array($identifier, $size_variant_values)){

                      $pos = array_search($identifier, $size_variant_values);
                      $size_name = $size_name_values[$pos];
                      $size_variant = $size_variant_values[$pos];

                    }

                  }

                  $rows[] = array(trim($title), $photographer, $date, $color_variant, $sid, $size_name, $size_variant);

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
