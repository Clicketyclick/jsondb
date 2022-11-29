<?php
/**
 *  @file jsondb.php
 *  @brief Rutines to read / write JSON and mixed data to SQL database
 *   
 *  @details   Converting mixed data to and from breadcrumb lists
 *  
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2022-11-25T13:58:39 / Erik Bachmann
 *  @version   2022-11-29T09:13:17 / Erik Bachmann
 */

// Flags for decoding JSON
define("JSON_DECODE_FLAGS", JSON_OBJECT_AS_ARRAY 
|   JSON_BIGINT_AS_STRING
|	JSON_NUMERIC_CHECK
|   JSON_INVALID_UTF8_SUBSTITUTE
|   JSON_THROW_ON_ERROR 
);

// Flags for incoding JSON
define("JSON_ENCODE_FLAGS", JSON_INVALID_UTF8_SUBSTITUTE
|   JSON_NUMERIC_CHECK
|   JSON_PRETTY_PRINT
|   JSON_THROW_ON_ERROR
);

// Delimiter used in breadcrumb paths
define( "BREADCRUMBDELIMITER", ':' );

// Default database and table name
if(!isset($tablename))
{
    $tablename  = 'jtable';
}
if(!isset($dbfile))
{
    $dbfile  = 'json.db';
}


//---------------------------------------------------------------------

// SQL's for creating and testing table
$jsondb_sql = 
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


/**
 *  @fn        putJsonDb
 *  @brief     Store a JSON structure as key/value list
 *  
 *  @param [in] $db         Database handle
 *  @param [in] $tablename  Name of table
 *  @param [in] $struct     List to store
 *  @param [in] $section     Section
 *  @param [in] $language     Language code
 *  @return    Return description
 *  
 *  @details   Convert a mixed data structure to a breadcrumb list
 *  and store the list in a database
 *  
 *  @example   
 *      // Given a table with the structure:
 *      // CREATE TABLE IF NOT EXISTS $tablename (
 *      //      section     text,
 *      //      language    text,
 *      //      key         text NOT NULL,
 *      //      val         text,
 *      //      PRIMARY KEY ( section, key )
 *      //  );
 *      var $db = // File handle to open database
 *      $mixed = [
 *          "search" => [
 *              "form" => [
 *                "field" =>    "Field",
 *                "button" =>   "Button"
 *              ],
 *              "buttons" => [
 *                "send" =>     "Send",
 *                "cancel" =>   "Cancel"
 *              ]
 *          ],
 *          "display" => [
 *              "header" => [
 *                "title" =>    "Title",
 *                "button" =>   "Button"
 *              ],
 *              "main" => [
 *                "title" =>    "Main title",
 *                "button" =>   "Button"
 *              ],
 *              "footer" => [
 *                "send" =>     "Footer",
 *                "cancel" =>   "Cancel"
 *              ]
 *          ]
 *      ];
 *      putJsonDb( $db, "tablename", $mixed, "section", 'en' );
 *
 *  | section | language |          key          |    val      |
 *  |---------|----------|-----------------------|-------------|
 *  | section | en       | search-form-field     | Field       |
 *  | section | en       | search-form-button    | Button      |
 *  | section | en       | display-header-title  | Title       |
 *  | section | en       | display-header-button | Button      |
 *  | section | en       | display-main-title    | Main title  |
 *  | section | en       | display-main-button   | Button      |
 *  | section | en       | display-footer-send   | Footer      |
 *  | section | en       | display-footer-cancel | Cancel      |
  *      
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2022-06-09T08:57:21 / Erik Bachmann
 */
function putJsonDb( &$db, $tablename, $struct, $section = '', $language = 'en' ) {
    $output    = [];
    $index = [];

    array2breadcrumblist($struct, $index, $output );

    executeSql( $db, "BEGIN TRANSACTION;" );
    foreach($index as $key => $value) {
        $value  = SQLite3::escapeString( $value );
        $sql    = "REPLACE INTO $tablename VALUES( '$section', '$language', '$key', '$value' );";
        if( isset($debug) ) print "[$sql]\n";
        executeSql( $db, $sql );
    }
    executeSql( $db, "END;" );
    return( $index );
}   //*** putJsonDb() ***

//---------------------------------------------------------------------

/**
 *  @fn        putListDb
 *  @brief     Update database with list
 *  
 *  @param [in] $db         Database handle
 *  @param [in] $tablename  Name of table
 *  @param [in] $struct     List to store
 *  @param [in] $section    Section
 *  @param [in] $language   Language code
 *  @return    VOID
 *  
 *  @details   In a transaction write each path/key set to database
 *  
 *  @example   $list    =
 *      [
 *          "test:str1" => "1",
 *          "test:str2" => "2"
 *      ];
 *      putListDb( $db, "tablename", $list, "section" );
 *  
 *    | section  | language |          key          |   value   |
 *    |----------|----------|-----------------------|-----------|
 *    | section  | xx       | test:str1             | 1         |
 *    | section  | xx       | test:str2             | 2         |
 *
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2022-06-09T08:56:06 / Erik Bachmann
 */
function putListDb( &$db, $tablename, $struct, $section = '', $language = 'xx' ) 
{
    //$output    = [];
    executeSql( $db, "BEGIN TRANSACTION;" );
    foreach( $struct as $key => $value ) 
    {
        $sql    = "REPLACE INTO $tablename VALUES( '$section', '$language', '$key', '$value' );";
        if( isset($debug) ) 
            print "[$sql]\n";
        executeSql( $db, $sql );
    }
    executeSql( $db, "END;" );
    //return( $output );
}   //*** putListDb() ***

//---------------------------------------------------------------------

/**
 *  @fn        getJsonDb
 *  @brief     Get mix from database and expand breadcrumb list
 *  
 *  @param [in] $db         Database handle
 *  @param [in] $tablename  Name of table to read from
 *  @param [in] $where      Selection criteria
 *  @param [in] $order      Order
 *  @return    Return mix
 *  
 *  @details   Get a data set of breadcrumb paths and keys and
 *      expand this to a valid mixed structure
 *  
 *  @example   
 *
 *    | section  | language |          key          |   value   |
 *    |----------|----------|-----------------------|-----------|
 *    | section  | xx       | test:str1             | 1         |
 *    | section  | xx       | test:str2             | 2         |
 *  
 *      $mix    = getJsonDb( $db, 'jtable', BREADCRUMBDELIMITER, "section = 'section' AND language = 'xx' ); 
 *      print_r( $mix );
 *  
 *      Array
 *      {
 *          "test": [
 *              {
 *                  "str1": 1
 *              },
 *              {
 *                  "str2": 4
 *              }
 *          ]
 *      }
 *
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2022-11-24T15:16:08 / Erik Bachmann
 */
function getJsonDb( &$db, $tablename, $breadcrumbdelimiter = BREADCRUMBDELIMITER, $where = "", $order = false ) 
{
    //global $breadcrumbdelimiter;
    $output =   [];
    $where  =   empty( $where ) ? $where : "WHERE " . $where;
    $where  .=  empty( $order ) ? "" : "ORDERBY ". $order ;
    $sql    =   "SELECT * FROM $tablename $where;";
    if( isset($debug) ) trigger_error( "SQL: $sql", E_USER_NOTICE );
    
    $data    = executeSql( $db, $sql );

    foreach( $data as $arrayno => $entry ) 
    {
        $path = explode( $breadcrumbdelimiter, $entry['key']);
        $entry['value']    = is_numeric( $entry['value'] ) ? (int) $entry['value'] : $entry['value'];
        setPathKey($path, $output, $entry['value'] );
    }
    return( $output );
}   // getJsonDb() 

//---------------------------------------------------------------------

/**
 *  @fn        getListDb
 *  @brief     Get a mixed data set from database
 *  
 *  @param [in] $db         Database handle
 *  @param [in] $tablename  Name of table
 *  @param [in] $where      WHERE clause to extract list
 *  @return    mixed
 *  
 *  @details   More details
 *  
 *  @example   
 *    | section  | language |          key          |   value   |
 *    |----------|----------|-----------------------|-----------|
 *    | section  | xx       | test:str1             | 1         |
 *    | section  | xx       | test:str2             | 2         |
 *  
 *      getListDb( $db, "tablename", "section like 'section'");
 *  
 *      (
 *          [test:str1] => 1
 *          [test:str2] => 2
 *      )
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2022-06-09T08:58:47 / Erik Bachmann
 */
function getListDb( &$db, $tablename, $where = "" ) {
    $output = [];
    $where  = empty( $where ) ? $where : "WHERE " . $where;
    $sql    = "SELECT * FROM $tablename $where;";
    if( isset($debug) )trigger_error( "SQL: $sql", E_USER_NOTICE );
    $data   = executeSql( $db, $sql );
    
    foreach( $data as $arrayno => $entry ) {
        $entry['value']    = is_numeric( $entry['value'] ) ? (int) $entry['value'] : $entry['value'];
        $output[ $entry['key'] ] = $entry['value'];
    }
    return( $output );
}   //*** getListDb() ***

//---------------------------------------------------------------------

/**
 *  @fn        setBreadcrumpValue
 *  @brief     Insert value in arra using breadcrump path
 *  
 *  @param [in] $output Array to hold value
 *  @param [in] $key     Breadcrump trail
 *  @param [in] $value     Value to assign
 *  @return    VOID
 *  
 *  @details   More details
 *  
 *  @example   
 *          $list    =
 *          [
 *              "test:str1" => "1",
 *              "test:str2" => "2"
 *          ];
 *          setBreadcrumpValue( $list, "test:str3", "Hello" );
 *          $list    =
 *          [
 *              "test:str1" => "1",
 *              "test:str2" => "2",
 *              "test:str3" => "Hello"
 *          ];
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2022-11-24T15:38:32 / Erik Bachmann
 */
function setBreadcrumpValue( &$output, $key, $value, $breadcrumbdelimiter = BREADCRUMBDELIMITER ) {
    $path = explode( $breadcrumbdelimiter, $key);
    setPathKey($path, $output, $value);
}   // setBreadcrumpValue()

//---------------------------------------------------------------------

/**
 *  @fn        array2breadcrumblist
 *  @brief     Recursive build bread crum key / value array
 *  
 *  @param [in] $a          Description for $a
 *  @param [in] $index      Description for $index
 *  @return    Return description
 *  
 *  @details   More details
 *  
 *  @example
 *      print_r( $input );
 *      print_r( array2breadcrumblist($input, $output ) );
 *  
 *  Input:
 *     {
 *         "projects": [
 *             {
 *                 "id": 1
 *             },
 *             {
 *                 "id": 4
 *             }
 *         ]
 *     }
 *  Output:
 *     [
 *        'projects-0-id' => '1',
 *        'projects-1-id' => '4'
 *     ]
 *      
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/53998295
 *  @since     2022-04-27T11:29:36 / Erik Bachmann
 */
function array2breadcrumblist($a, &$index, $keys=array(), $breadcrumbdelimiter = BREADCRUMBDELIMITER , &$output = false) {
    //global $breadcrumbdelimiter;
    if (!is_array($a)) 
    {
        $index[ implode( $breadcrumbdelimiter, $keys) ] = $a;
        setBreadcrumpValue( $output, implode( $breadcrumbdelimiter, $keys), $a, $breadcrumbdelimiter );
        return;
    }
    foreach($a as $k=>$v) 
    {
        $newkeys = array_merge($keys,array($k));
        array2breadcrumblist($v, $index, $newkeys, $breadcrumbdelimiter );
    }
    return( $index );
}   // array2breadcrumblist()


//----------------------------------------------------------------------

/**
 *  @fn        setPathKey
 *  @brief     Set value for each breadcrumb
 *  
 *  @param [in] $path       Breadcrump trail
 *  @param [in] $array      Array to insert into
 *  @param [in] $value      Value to assign
 *  @return    VOID
 *  
 *  @details   More details
 *      This combination will set a value in an existing array or create the array 
 *      if you pass one that has not yet been defined. Make sure to define $array 
 *      to be passed by reference &$array
 *      returns NULL if the path doesn't exist combination will set a value in an 
 *      existing array or create the array if you pass one that has not yet been defined. 
 *      Make sure to define $array to be passed
 *  
 *  @example   
 *      $value = getPathKey($path, $arr); // by reference &$array:
 *      setPathKey($path, $arr);
 *      //or
 *      setPathKey($path, $arr, 'some value');
 *
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/27930028 How to access and manipulate multi-dimensional array by key names / path?
 *  @since     2022-04-26T16:52:48 / Erik Bachmann
 */
function setPathKey($path, &$array=array(), $value=null) {
    $temp =& $array;

    foreach($path as $key)
    {
        $temp =& $temp[$key];
    }
    $temp = $value;
}   // setPathKey

//---------------------------------------------------------------------

?>
