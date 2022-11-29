<?php
/**
 *  @file      load_json.php
 *  @brief     Testing writing a JSON struct into a database table and recover it again
 *  
 *  @details   More details
 *  
 *  putJsonDb( &$db, $tablename, $struct );
 *      // Write mulitidimentional array (actually not a JSON) to table with a key / value combination
 *  getJsonDb( &$db, $tablename, $where );
 *      // Get structure from db to mulitidimentional array
 *  
 *  Table schema:
 *  CREATE TABLE IF NOT EXISTS jtable (
 *      key        text PRIMARY KEY NOT NULL,
 *      val        text
 *  );
 *  
 *  
 *  @see        https://stackoverflow.com/a/55212162 How to SQL query parent-child for specific JSON format?
 *  
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2022-04-26T18:53:40 / Erik Bachmann
 *  @version   2022-11-25T08:27:02 / Erik Bachmann
 */

fprintf( STDERR, "*** %s ***\n\n", basename(__FILE__));
include_once( "jsondb.php" );

$dbfile       = "json.db";
$tablename    = "jtable";
//$output        = [];
$verbose    = true;
//$debug      = true;
if( $debug ?? false ) $verbose    = true;
//$breadcrumbdelimiter  = '§';
//$breadcrumbdelimiter  = '|';
//$breadcrumbdelimiter  = ':';

if( $debug ?? false ) printf( "JSON_DECODE_FLAGS=[%s], JSON_ENCODE_FLAGS=[%s]\n", JSON_DECODE_FLAGS, JSON_ENCODE_FLAGS );

$sql    = 
[
    'create_table'  => 
        "CREATE TABLE IF NOT EXISTS $tablename 
        (
            section     text,
            language    text,
            key         text NOT NULL,
            value       text,
            PRIMARY KEY ( section, language, key )
        );",
    'exists_table'  => "SELECT name FROM sqlite_master WHERE type='table' AND name='$tablename';"
];

// Test data
$projects   = 
[
    "projects" => 
    [
         [
            "id" => 1
         ],
         [
            "id" => 4
         ]
    ]
];
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


// Load SQLite functions
/*
$path = '../../.releases/04.21';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
include "lib/handleSqlite.php";
*/
include "handleSqlite.php";

open_database( $dbfile, $sql, $tablename );

print "// Projects >>\n";
if( $verbose ?? false ) print( json_encode( $projects, JSON_ENCODE_FLAGS ));
if( $verbose ?? false ) print_r( array2breadcrumblist( $projects, $output, [], BREADCRUMBDELIMITER ) );
if( $verbose ?? false ) var_dump( array2breadcrumblist( $projects, $output ) );
if( $verbose ?? false ) var_export( array2breadcrumblist( $projects, $output ) );
writeToDatabase( $db, $projects, $tablename, 'projects', 'xx' );
print "// Projects <<\n";


testJsonFile( $db, $tablename, "local.da", "da" );

$list_da    = readListFromDatabase( $db, $tablename, "section = 'local.da' AND language = 'da'" );

print "// Testprint:\n";
$key    = implode( BREADCRUMBDELIMITER, ["display", "header", "title"] );
print_key_value( $key,  $list_da[ $key ] );

if( $verbose ?? false ) {
    print "// List_da:\n";
    print_r( $list_da );
}

array2breadcrumblist($mixed, $index );
if( $verbose ?? false ) {
    print "// list_mixed:\n";
    print_r( $index );
}

/* * /
testJsonFile( $db, $tablename, "icons" );
testJsonFile( $db, $tablename, "local" );
testJsonFile( $db, $tablename, "recordDef" );
/* */
//---------------------------------------------------------------------

function testJsonFile( $db, $tablename, $file, $language = 'xx' )
{
    //global $breadcrumbdelimiter;
    print "// $file\n";
    $local   = loadJson( "testdata/$file.json" );
    writeToDatabase( $db, $local, $tablename, $file, $language );
    $index   = readMixFromDatabase( $db, $tablename, BREADCRUMBDELIMITER, "section = '$file' AND language = '$language'" );
    file_put_contents( "$file.1", var_export( $local, true ) );
    file_put_contents( "$file.2", var_export( $index, true ) );
    print "\t// Match: " . (filesize("$file.1",) == filesize("$file.2",) ? "OK" : "FAILED" ). "\n";
}

//---------------------------------------------------------------------

function open_database( $dbfile, $sql, $tablename ) 
{
    global $db;
    fputs( STDERR, "// Open database\t");
    $start  = microtime(true);

    $db = openSqlDb( $dbfile );

    fprintf( STDERR,"Duration: %s\n", microtime(true) - $start );

    fputs( STDERR, "\t// Check table [$tablename]\t");
    $start  = microtime(true);
    // Create table if not exists
    // Redundant when using: CREATE TABLE IF NOT EXISTS
    if ( ! executeSql( $db, $sql['exists_table'] ) )
    {
        if ( ! executeSql( $db, $sql['create_table'] ) ) 
        {
            trigger_error( "Failed to create table $tablename", E_USER_ERROR );
        }
        else 
        {
            trigger_error( "Table $tablename created", E_USER_NOTICE );
        }
    } 
    else 
    {
        if ( $debug ?? false ) trigger_error( "Table $tablename exists", E_USER_NOTICE );
    }
    fprintf( STDERR,"Duration: %s\n", microtime(true) - $start );
}   // open_database()

//---------------------------------------------------------------------

function loadJson( $file ) {
    fputs( STDERR, "\t// read data\t\t\t");
    $start      = microtime(true);
    $mix        = json_decode( file_get_contents( $file ), JSON_DECODE_FLAGS );
    fprintf( STDERR,"Duration: %s\n", microtime(true) - $start );

    if ( $verbose ?? false ) trigger_error( "Data read", E_USER_NOTICE );
    //if ( $debug ?? false ) print_r( $local );
    if ( $verbose ?? false ) var_dump( $mix );
    return( $mix );
}   // loadJson()

//---------------------------------------------------------------------

function writeToDatabase( &$db, &$data, $tablename, $section, $language ) {
    fputs( STDERR, "\t// Write to database\t\t");
    $start  = microtime(true);
    $index    = putJsonDb( $db, $tablename, $data, $section, $language );
    fprintf( STDERR,"Duration: %s\n", microtime(true) - $start );

    if( $debug ?? false ) trigger_error( "Data written", E_USER_NOTICE );
}   // writeToDatabase()

//---------------------------------------------------------------------

function readMixFromDatabase( $db, $tablename, $breadcrumbdelimiter = BREADCRUMBDELIMITER, $where = '' ) 
{
    fputs( STDERR, "\t// Read from database\n" );
    fputs( STDERR, "\t\t// Read JSON\t\t" );

    $start  = microtime(true);
    $mix    = getJsonDb( $db, $tablename, $breadcrumbdelimiter, $where );
    fprintf( STDERR,"Duration: %s\n", microtime(true) - $start );

    return( $mix );
}   // readMixFromDatabase()

//---------------------------------------------------------------------

function readListFromDatabase( $db, $tablename, $where ) 
{
    fputs( STDERR, "\t\t// Read list\t\t" );
    $start  = microtime(true);
    $list   = getListDb( $db, $tablename, $where );
    fprintf( STDERR,"Duration: %s\n", microtime(true) - $start );

    return( $list );
}   // readListFromDatabase()

//=====================================================================


function print_key_value($key, $item )
{
    echo "- [$key] = [$item]\n";
}

//---------------------------------------------------------------------

// https://stackoverflow.com/a/53736239
// recursive function loop through the dimensional array
function __loop($array){
    //loop each row of array
    foreach($array as $key => $value)
    {
         //if the value is array, it will do the recursive
         if(is_array($value) ) 
             $array[$key] =  loop($array[$key]);

         if(!is_array($value)) 
         {
            // you can do your algorithm here
            // example: 
             var_dump($array[$key]);
         }
    }
    return $array;
}   // loop()

//---------------------------------------------------------------------


/**
 *  @fn        getPathKey
 *  @brief     Brief description
 *  
 *  @param [in] $path       Description for $path
 *  @param [in] $array      Description for $array
 *  @return    Return description
 *  
 *  @details   More details
 *  
 *  @example   $value = getPathKey($path, $arr); //returns NULL if the path doesn't exist
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/27930028 How to access and manipulate multi-dimensional array by key names / path?
 *  @since     2022-04-26T16:52:48 / Erik Bachmann
 */
function __getPathKey($path, $array) {
    //$path = explode('.', $path); //if needed
    $temp =& $array;

    foreach($path as $key) {
        $temp =& $temp[$key];
    }
    return $temp;
}   // getPathKey()

//---------------------------------------------------------------------


/**
 *  @fn        unsetterPathKey
 *  @brief     unset the final key in the path
 *  
 *  @param [in] $path     Description for $path
 *  @param [in] $array     Description for $array
 *  @return    Return description
 *  
 *  @details   More details
 *  
 *  @example   unsetter($path, $arr);
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/27930028 How to access and manipulate multi-dimensional array by key names / path?
 *  @since     2022-11-24T15:04:00 / Erik Bachmann
 */
function __unsetterPathKey($path, &$array) 
{
    //$path = explode('.', $path); //if needed
    $temp =& $array;

    foreach($path as $key) 
    {
        if(!is_array($temp[$key])) 
        {
            unset($temp[$key]);
        }
        else 
        {
            $temp =& $temp[$key];
        }
    }
}   // unsetterPathKey

?>
