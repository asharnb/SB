<?php

/**
 * @file
 * Contains \Drupal\studio_session_operations\Controller\CsvMakerController.
 */

namespace Drupal\studio_session_operations\Controller;

use Drupal\Core\Controller\ControllerBase;

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
  public function hello($id) {

    $head = array('ID', 'SKU ID',	'Color Variant', 'Photographer', 'Shoot Date', 'Session');

    $data =  array(
      $head,
      array(rand(1,90),rand(1,90),rand(1,90),rand(1,90),rand(1,90), '8909988098090'),
      );

    $this->array_to_csv_download($data, // this array is going to be the second row
      "numbers".date('H_i_s---').rand(100,9999).".csv"
    );

  }

  public function array_to_csv_download($array, $filename = "export.csv", $delimiter=";") {
    // open raw memory as file so no temp files needed, you might run out of memory though
    //$f = fopen('php://output', 'w');
    // loop over the input array
    foreach ($array as $line) {
      // generate csv lines from the inner arrays
      //fputcsv($f, $line, $delimiter);
    }
    // reset the file pointer to the start of the file
    //fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: application/xls');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    // make php send the generated csv lines to the browser


    header('Content-Type: text/html; charset=utf-8');

    //fpassthru($f);

    echo "a,b,c,d";

    exit();
  }

}
