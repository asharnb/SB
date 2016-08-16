<?php
 
/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simply to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */
 
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */
 
// DB table to use
$table = 'datatables_demo';
 
// Table's primary key
$primaryKey = 'id';
 
// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes
$columns = array(
    array( 'db' => 'first_name', 'dt' => 0 ),
    array( 'db' => 'last_name',  'dt' => 1 ),
    array( 'db' => 'position',   'dt' => 2 ),
    array( 'db' => 'office',     'dt' => 3 ),
    array(
        'db'        => 'start_date',
        'dt'        => 4,
        'formatter' => function( $d, $row ) {
            return date( 'jS M y', strtotime($d));
        }
    ),
    array(
        'db'        => 'salary',
        'dt'        => 5,
        'formatter' => function( $d, $row ) {
            return '$'.number_format($d);
        }
    )
);
 
// SQL server connection information
$sql_details = array(
    'user' => '',
    'pass' => '',
    'db'   => '',
    'host' => ''
);
 
 
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */
 
//require( 'ssp.class.php' );

$data = array(
   'draw' => 1,
   'recordsTotal' => 2,
   'recordsFiltered' => 2,
   'data' => 
  array (
    0 => 
    array (
      0 => 'Airi',
      1 => 'Satou',
      2 => 'Accountant',
      3 => 'Tokyo',
      4 => '28th Nov 08',
      5 => '$162,700',
    ),
    1 => 
    array (
      0 => 'Angelica',
      1 => 'Ramos',
      2 => 'Chief Executive Officer (CEO)',
      3 => 'London',
      4 => '9th Oct 09',
      5 => '$1,200,000',
    ),
    2 => 
    array (
      0 => 'Ashton',
      1 => 'Cox',
      2 => 'Junior Technical Author',
      3 => 'San Francisco',
      4 => '12th Jan 09',
      5 => '$86,000',
    ),
    3 => 
    array (
      0 => 'Bradley',
      1 => 'Greer',
      2 => 'Software Engineer',
      3 => 'London',
      4 => '13th Oct 12',
      5 => '$132,000',
    ),
    4 => 
    array (
      0 => 'Brenden',
      1 => 'Wagner',
      2 => 'Software Engineer',
      3 => 'San Francisco',
      4 => '7th Jun 11',
      5 => '$206,850',
    ),
    5 => 
    array (
      0 => 'Brielle',
      1 => 'Williamson',
      2 => 'Integration Specialist',
      3 => 'New York',
      4 => '2nd Dec 12',
      5 => '$372,000',
    ),
    6 => 
    array (
      0 => 'Bruno',
      1 => 'Nash',
      2 => 'Software Engineer',
      3 => 'London',
      4 => '3rd May 11',
      5 => '$163,500',
    ),
    7 => 
    array (
      0 => 'Caesar',
      1 => 'Vance',
      2 => 'Pre-Sales Support',
      3 => 'New York',
      4 => '12th Dec 11',
      5 => '$106,450',
    ),
    8 => 
    array (
      0 => 'Cara',
      1 => 'Stevens',
      2 => 'Sales Assistant',
      3 => 'New York',
      4 => '6th Dec 11',
      5 => '$145,600',
    ),
    9 => 
    array (
      0 => 'Cedric',
      1 => 'Kelly',
      2 => 'Senior Javascript Developer',
      3 => 'Edinburgh',
      4 => '29th Mar 12',
      5 => '$433,060',
    ),
  ),
); 

if($_GET['search']['value']){
 $data =  array(
   'draw' => 4,
   'recordsTotal' => 57,
   'recordsFiltered' => 1,
   'data' => 
  array (
    0 => 
    array (
      0 => '1111',
      1 => 'Satou',
      2 => 'Accountant',
      3 => 'Tokyo',
      4 => '28th Nov 08',
      5 => '$162,700',
    ),
  ),
); 
}


echo json_encode(
    $data
);
