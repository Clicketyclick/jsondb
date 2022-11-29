<?php
print "Example for jsondb.php\n";
/*
$dbfile       = "json.db";              // Alternate database name
$tablename    = "jtable";               // Alternate table name
*/
include_once( "jsondb.php" );           // Rutines to read / write JSON and mixed data to SQL database
include_once( "handleSqlite.php" );     // SQLite i/o rutines


$db = openSqlDb( $dbfile );             // Open/create dabase
executeSql( $db, $jsondb_sql['create_table'] );   // Create table (if not found)

$mixed = [                              // Array to store
    "display" => [
        "header" => [
          "title" =>    "Title",
        ],
        "main" => [
          "title" =>    "Main title",
          "button" =>   "Button"
        ],
        "footer" => [
          "title" =>     "Footer"
        ]
    ]
];

$section    = 'mix';                    // Section name in table
$language   = 'en';                     // Language code

print "// Save structure in database\n";
putJsonDb( $db, $tablename, $mixed, $section, $language );

print "// Read data as array/mix\n";
$where  = "section = '$section' AND language = '$language'";
$mix    = getJsonDb( $db, $tablename, BREADCRUMBDELIMITER, $where );
print_r( $mix );

print "// Read data as breadcrumb/value list\n";
$list   = getListDb( $db, $tablename, $where );
print_r( $list );
?>
