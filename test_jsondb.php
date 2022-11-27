<?php
print "Mimimal example of jsondb.php\n";
include_once( "jsondb.php" );
include_once( "handleSqlite.php" );
$dbfile       = "json.db";
$tablename    = "jtable";

$sql_create_table =
    "CREATE TABLE IF NOT EXISTS $tablename 
    (
        section     text,
        language    text,
        key         text NOT NULL,
        value       text,
        PRIMARY KEY ( section, language, key )
    );";

        
$db = openSqlDb( $dbfile );
executeSql( $db, $sql_create_table );

$mixed = [
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

$section    = 'mix';
$language   = 'en';
putJsonDb( $db, $tablename, $mixed, $section, $language );

$where  = "section = '$section' AND language = '$language'";
$mix    = getJsonDb( $db, $tablename, BREADCRUMBDELIMITER, $where );
print_r( $mix );

$list   = getListDb( $db, $tablename, $where );
print_r( $list );

?>
