<?php
/**
  *  @file       handleSqlite.php
  *  @brief      Abstraction layer for SQLite database operations
  *  
  *  @details    Detailed description
  *  
  *
  *  
  *  
  * openSqlDb( $dbfile )
  *     // Open / create database
  * createSqlTable( &$db, $tabledef );
  *     // Create table 
  * buildSqlInsert( $tablename, $fields, $rowid = FALSE );
  *     // Build INSERT statement by template
  * buildSqlUpdate( $tablename, $fields, $where = FALSE );
  *     // Build UPDATE statement by template
  * getSqlTableLength( &$db, $table, $where = FALSE );
  *     // Get no of elements in table
  * getSqlTables( &$db );
  *     // List tables in database
  * getSqlMaxRowId( &$db, $table );
  *     // Get highest rowid in table
  * querySql( &$db, $sql );
  *     // Executes an SQL query
  * querySqlSingleValue( &$db, $sql );
  *     // Executes a query and returns a single result (value)
  * querySqlSingleRow( &$db, $sql );
  *     // Executes a query and returns a single result (Row)
  * executeSql( &$db, $sql );
  *     // Prepares an SQL statement for execution, execute and return result as array
  *----------------------------------
  * fetchObject()
  *     // Fetch object from SQLite
  * truncateTable()
  *     // Truncating a table
  * resetRowid()
  *     // Reset rowids after truncate
  * dbNoOfRecords( &$db, $table, $where = ""  )
  *     // 
  * dbLastEntry(  &$db, $table, $orderfield, $where = "" )
  *     //
  * dbSchema( &$db, $table, $where = ""  )
  *     // 
  * vacuumInto( &$db, $newdbfile )
  *     //Vacuum current database to a new database file
  * stdClass2array()
  * 	// Converting an array/stdClass -> array
  * dbDump( $filename, $dumpfile)
  * 	// Dump entire database as SQL
  * closeSqlDb( &$db, $dbfile = FALSE );
  *     // Close / Close +  delete database file
  *  getDbFile
  *     // Get full path to database
  *  getDbName
  *     // Get database name
  *  
  *  
  * Obsolete functions
  * 
  * deleteRow( &$db, $table, $rowno );' );
  *     Use: executeSql()
  * insertData( &$db, $tablename, $fields, $rowid = FALSE );
  *     Use: buildSqlUpdate() + executeSql()
  * updateData( &$db, $tablename, $fields, $rowid = FALSE );' );
  *     Use: buildSqlInsert() + executeSql()
  * Deprecated functions:
  * 
  * array2stdClass( &$array );
  * stdClass2array( &$stdClass );
  * fetchObject( &$sqlite3result, $objectType = NULL);
  * 
  *  php -r "print SQLite3::escapeString( \"O'donald\" ); "
  *  
  *  @todo       
  *  @bug        
  *  @warning    
  *  
  *  @deprecated no
  *  @link       
  *  
  *  @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
  *  @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
  *  @since      2019-11-23T20:25:02
  *  @version    2022-11-27T22:16:13
  */

//---------------------------------------------------------------------

/**
 *  @fn        openSqlDb()
 *  @brief     Open or create database
 *  
 *  @details   Wrapper for SQLite3::open()
 *  
 *  @param [in] $dbfile Path and name of database file to open
 *  @return     File handle to database OR FALSE
 *  
 *  @example   $db = openSqlDb( "./my.db" );
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-11T07:43:08
 */
function openSqlDb( $dbfile ) {
    $db = new SQLite3( $dbfile );
    if ( ! $db ) {
        trigger_error( "Cannot open database [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    return( $db );
}   // openSqlDb()

//---------------------------------------------------------------------

/**
 *  @fn        createTable()
 *  @brief     Create new table in database
 *  
 *  @details   Build new tables from tabledef. Tabledef is an assosiative array with
 *      table name as key and field name / type pairs as sub array
 *  
 *  @param [in] $db       Handle to open database
 *  @param [in] $tabledef Table definitions
 *  @return    Return description
 *  
 *  
 *  @example    $tabledef    = [ "mytable" => ["id" => "INTEGER", "str" => "TEXT"] ];
 *              createSqlTable( $db, $tabledef );
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-10T08:25:50
 */
function createSqlTable( &$db, $tabledef ) {
    $result     = array();
    foreach( $tabledef as $tablename => $fields ) {
        $sql = "CREATE TABLE IF NOT EXISTS ${tablename} (\n";
        $count  = 0;
        foreach ( $fields as $fieldname => $config ) {
            if ( 0 < $count )
                $sql .= ",\n";
            $sql .= "\t${fieldname} ${config}";
            $count++;
        }
        $sql .= ");";

        //$r  = executeSql( $db, $sql );
        $r  = $db->exec( $sql );
        array_push( $result, $r );
    }
    return( $result );
}   // createSqlTable()

//---------------------------------------------------------------------
/**
 *  @fn        buildSqlInsert()
 *  @brief     Insert new row in table
 *  
 *  @details   More details
 *  
 *  @return    Return description
 *  
 *  @example   $fields = [ "name" = 'HP ZBook 17', "model" => 'ZBook', "serial" => 'SN-2015' ];
 *      echo buildSqlInsert("devices", $fields );
 *  
 *   INSERT INTO devices (name, model, serial)
 *   VALUES('HP ZBook 17','ZBook','SN-2015');
 *  
 *  @param [in] $tablename Name of table
 *  @param [in] $fields    Hash of field tags and values
 *  @param [in] $rowid     RecNo to update - or FALSE to Insert
 *  @return    SQL expression
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-11-23T20:09:46
 */
function buildSqlInsert( $tablename, $fields, $rowid = FALSE ) {
    $sql = "INSERT " 
    .   ($rowid ? "OR REPLACE" : "") 
    .   " INTO $tablename (";
    $count  = 0;
    $values = "";
    foreach ( $fields as $fieldname => $value ) {

        if ( 0 < $count ) {
            $sql .= ", ";
            $values .= ", ";
        }
        $sql .= " '${fieldname}'";
        $values .= " '${value}'";
        $count++;
    }
    $sql .= ") VALUES(${values} )";
    if ( $rowid )
        $sql .= "WHERE rowid = $rowid";
    $sql .= ";\n" ;

    return( $sql );
}   // buildSqlInsert()

//---------------------------------------------------------------------
/**
 *  @fn        buildSqlUpdate()
 *  @brief     Build an UPDATE statement in SQL
 *  
 *  @details   More details
 *  
 *  @param [in] $tablename Name of table
 *  @param [in] $fields    Hash of field tags and values
 *  @param [in] $shere     WHERE clause
 *  @return    SQL expression
 *  
 *  @example    $fields = [ "name" => 'HP ZBook 17', "model" => 'ZBook', "serial" => 'SN-2015' ];
 *      $where = [ "rowid" => 1];
 *      echo buildSqlUpdate("devices", $fields, $where );
 *
 *      UPDATE devices SET "name" = 'HP ZBook 17', "model" => 'ZBook', "serial" => 'SN-2015'
 *      WHERE     rowid = 1;
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-11T07:51:32
 */
function buildSqlUpdate( $tablename, $fields, $where = FALSE ) {
    $sql = "UPDATE $tablename SET ";
    $count  = 0;
    $values = "";
    foreach ( $fields as $fieldname => $value ) {

        if ( 0 < $count ) {
            $sql .= ", ";
        }
        $sql .= "'${fieldname}' = '${value}'";
        $count++;
    }
    $count = 0;
    if ( $where )
        $sql .= " WHERE ";
        foreach ( $where as $fieldname => $value ) {
            if ( 0 < $count ) {
                $sql .= " AND ";
            }
            $sql .= "$fieldname = $value";
        }
    $sql .= ";" . PHP_EOL;

    return( $sql );
}   // buildSqlUpdate()

//---------------------------------------------------------------------

/**
 *  @fn        getSqlTableLength()
 *  @brief     Return no of elements in table
 *  
 *  @details   Count no of elements
 *  
 *  @param [in] $db    Handle to database
 *  @param [in] $table Name of table
 *  @param [in] $where WHERE clause
 *  @return    No of rows
 *  
 *  @example   getSqlTableLength( $db, "meta" )
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-11T08:23:06
 */
function getSqlTableLength( &$db, $table, $where = FALSE ) {
    $query = "SELECT max( rowid ) AS max FROM $table";
    if ( $where ) {
        $query = "SELECT count(*) AS max FROM $table";
        $query  .= " WHERE $where";
    }
    
    $result = $db->query( "$query;");

    $row = $result->fetchArray(SQLITE3_ASSOC);
    return( $row['max'] ); 
}   // getSqlTableLength()

//---------------------------------------------------------------------

/**
 *  @fn        getSqlTables()
 *  @brief     List tables/indices in database
 *  
 *  @details   Getting list of tables from sqlite_master
 *  
 *  CREATE TABLE sqlite_master (
  type text,
  name text,
  tbl_name text,
  rootpage integer,
  sql text
);

 *  @param [in] $db Database handle
 *  @param [in] $fields Field name (name)
 *  @param [in] $type   Element type (table/trigger/index)
 *  @return    Array of tables
 *  
 *  @example   $list = getSqlTables( $db );
 *  
 *  @todo       
 *  @bug        
 *  @warning    
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T13:58:34
 */
 function getSqlTables( &$db, $fields = "name", $type = "table") {
    //return( getData( $db, "SELECT name FROM sqlite_master WHERE type='table';" ) );
    $sql    = "SELECT $fields FROM sqlite_master WHERE type='$type';";
    $got    = $db->query( $sql );

    $rows   = [];
    while ($row = $got->fetchArray(SQLITE3_ASSOC)) {
        array_push( $rows, $row );
    }
    return( $rows );
}   // getSqlTables()

//---------------------------------------------------------------------

/**
 *  @fn        getSqlMaxRowId()
 *  @brief     Get highest rowid in table
 *  
 *  @details   More details
 *  
 *  @param [in] $db    Database handle
 *  @param [in] $table Name of table
 *  @return    Highest rowid
 *  
 *  @example   getSqlMaxRowId
 *  
 *  @todo       
 *  @bug        
 *  @warning    Highest rowid is NOT the no of rows! Use getSqlTableLength()
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:12:18
 */
function getSqlMaxRowId( &$db, $table ) {
    $query = "SELECT max(ROWID) FROM $table;";

    $result = $db->query($query);

    $row = $result->fetchArray(SQLITE3_ASSOC);

    return( $row['max(ROWID)'] ); 
}   // getSqlMaxRowId()

//---------------------------------------------------------------------

/**
 *  @fn        querySql()
 *  @brief     Executes an SQL query
 *  
 *  @details   More details
 *  
 *  @param [in] $db  Database handle
 *  @param [in] $sql SQL statement
 *  @return    Return result
 *  
 *  @example   querySql
 *  
 *  @todo       
 *  @bug        
 *  @warning    
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:13:49
 */
function querySql( &$db, $sql ) {
    $result = $db->query( $sql );

    $names=false;
    if ( $result->numColumns() ) {
        $names=array();

        while($arr=$result->fetchArray(SQLITE3_ASSOC))
        {
            array_push( $names, $arr );
        }
    }
    return( $names ); 
}   //querySql()

//---------------------------------------------------------------------

/**
 *  @fn        querySqlSingleValue()
 *  @brief     Executes a query and returns a single result (value)
 *  
 *  @details   Alias for SQLite3::querySingle (default)
 *  
 *  @param [in] $db  Description for $db
 *  @param [in] $sql Description for $sql
 *  @return    Return description
 *  
 *  @example   $sql    = "SELECT str FROM ${tablename};";
 *  @example   $got    = querySqlSingleValue( $db, $sql );
 *  @example   $expected   = "'Hello world'";
 *  
 *  @todo       
 *  @bug        
 *  @warning    Returned value is quoted: "'Hello'"
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T13:09:19
 */
function querySqlSingleValue( &$db, $sql ) {
    return( $db->querySingle( $sql ) );
}   //querySqlSingleValue()

//---------------------------------------------------------------------


/**
 *  @fn        querySqlSingleRow()
 *  @brief     Executes a query and returns a single result (Row)
 *  
 *  @details   Alias for SQLite3::querySingle (entire_row = true)
 *  
 *  @param [in] $db  Description for $db
 *  @param [in] $sql Description for $sql
 *  @return    Return description
 *  
 *  @example   $sql    = "SELECT * FROM ${tablename};";
 *  @example   $got    = querySqlSingleRow( $db, $sql );
 *  @example   $expected   = "array (
 *  @example     'id' => 1,
 *  @example     'str' => 'Hello world',
 *  @example   )";

 *  
 *  @todo       
 *  @bug        
 *  @warning    
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T13:09:19
 */
function querySqlSingleRow( &$db, $sql ) {
    return( $db->querySingle( $sql, TRUE ) );
}   //querySqlSingleRow()


//---------------------------------------------------------------------

/**
 *  @fn        executeSql()
 *  @brief     Prepares an SQL statement for execution, execute and return result as array
 *  
 *  @details   More details
 *  
 *  @param [in] $db  Database handle
 *  @param [in] $sql SQL statement
 *  @return    Return description
 *  
 *  @example   executeSql()
 *  
 *  @todo       
 *  @bug        
 *  @warning    May only process ONE statement at a time
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:18:28
 */
function executeSql( &$db, $sql ) {
    global $debug;
    $stmt   = FALSE;

    $names=false;
    //$sql    = SQLite3::escapeString( $sql );
    if ( $debug ) {
        trigger_error( "SQL: [$sql]", E_USER_NOTICE );
    }
    //if($stmt = $db->prepare( SQLite3::escapeString( $sql ) ))
    //if( ( $stmt = $db->prepare( $sql  ) ) === TRUE )
    if( $stmt = $db->prepare( $sql  ) )
    {
        //trigger_error( print_r($stmt, TRUE), E_USER_NOTICE);
        
        if ( ! isset( $stmt ) )
            trigger_error( "Cannot prepare SQL [$sql]", E_USER_ERROR );
        try {
            $result = $stmt->execute();
        }
        catch (Exception $exception) {
            if ($sqliteDebug) {
                trigger_error( $exception->getMessage(), E_USER_WARNING) ;
            }
            trigger_error( "Error executing SQL [$sql]", E_USER_ERROR );
        }
        
        $names  = TRUE;
        if ( $result->numColumns() ) {
            $names=array();

            while($arr=$result->fetchArray(SQLITE3_ASSOC))
            {
                array_push( $names, $arr );
            }
        }
    } else {
        $err = error_get_last();
        trigger_error( "Error executing SQL [$sql]" . var_export( $err, TRUE), E_USER_ERROR );
    }

    return( $names ); 
}   // executeSql()


//---------------------------------------------------------------------

/**
 *  @fn        closeSqlDb()
 *  @brief     Close / Close +  delete database file
 *  
 *  @details   More details
 *  
 *  @param [in] $db     Database handle
 *  @param [in] $dbfile Name of database file to remove
 *  @return    Return description
 *  
 *  @example   closeSqlDb
 *  
 *  @todo       
 *  @bug        
 *  @warning    Will rename database file without warning
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:19:02
 */
function closeSqlDb( &$db, $dbfile = FALSE ) {
    $result = $db->close();
    if (! $result ) {
        trigger_error("Failed to close database $dbfile", E_USER_WARNING );
    } 
    if ( $dbfile ) {
        rename( "$dbfile", "$dbfile.old" );
        if ( file_exists( $dbfile ) )
            trigger_error( "Database file still exists [$dbfile] [$result]", E_USER_WARNING );
    }
    return( $result );
}   // closeSqlDb()

//---------------------------------------------------------------------

/**
 *  @fn        stdClass2array()
 *  @brief     Converting an array/stdClass -> array
 *  
 *  @details   Converting an array/stdClass -> array
 *   The manual specifies the second argument of json_decode as: assoc
 *   When TRUE, returned objects will be converted into associative arrays.
 *  
 *  @param [in] $stdClass Description for $stdClass
 *  @return    Return description
 *  
 *  @example   stdClass2array
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  @deprecated No longer in used
 *  
 *  @see        https://stackoverflow.com/a/18576902
 *  @since      2019-11-23T16:33:15
 */
function stdClass2array( &$stdClass ) {

    $array = json_decode(json_encode($stdClass), TRUE);
    return( $array );
}   // stdClass2array()

//---------------------------------------------------------------------

/**
 *  @fn        fetchObject()
 *  @brief     Fetch object from SQLite
 *  
 *  @details   More details
 *  
 *  @param [in] $sqlite3result Description for $sqlite3result
 *  @param [in] $objectType    Description for $objectType
 *  @return    Return description
 *  
 *  @example   fetchObject
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  @deprecated No longer in used
 *  
 *  @see        https://www.php.net/manual/en/class.sqlite3result.php#101589
 *  @since      2019-12-11T09:07:07
 */
function fetchObject( &$sqlite3result, $objectType = NULL) {
    $array = $sqlite3result->fetchArray();

    if(is_null($objectType)) {
        $object = new stdClass();
    } else {
        // does not call this class' constructor
        $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($objectType), $objectType));
    }
   
    $reflector = new ReflectionObject($object);
    for($i = 0; $i < $sqlite3result->numColumns(); $i++) {
        $name = $sqlite3result->columnName($i);
        $value = $array[$name];
       
        try {
            $attribute = $reflector->getProperty($name);
           
            $attribute->setAccessible(TRUE);
            $attribute->setValue($object, $value);
        } catch (ReflectionException $e) {
            $object->$name = $value;
        }
    }
   
    return $object;
}   // fetchObject()

//---------------------------------------------------------------------

/** 
 *  @fn        truncateTable()
 *  @brief     Truncating a table
 *  
 *  @details   Delete all entries - execpt LIMIT last entries
 *  
 *  @param [in] $db     File pointer to database
 *  @param [in] $table  Table name
 *  @param [in] $limit  Rest to leave in table
 *  @return    Return description
 *  
 *  @example   truncateTable
 *  
 *  @todo     
 *  @bug     
 *  @warning    This function requires that table has rowid's (default)
 *  
 *  @see        https://stackoverflow.com/a/6990013/7485823
 *  @since      2020-01-28T10:43:43
 */
function truncateTable( &$db, $table, $limit = 10 ) {
    $sql    = "
DELETE FROM $table WHERE rowid NOT IN ( 
   SELECT rowid FROM $table
   ORDER BY rowid DESC
   LIMIT $limit
);
";
    $db->exec( $sqlQueue );
}   // deleteFromTableExcept()

//---------------------------------------------------------------------

/** 
 *  @fn        resetRowid()
 *  @brief     Reset rowids after truncate
 *  
 *  @details   Unload and reload entries to reset rowid. 
 *  Usefull for trucating log files
 *  
 *  @param [in] $db    	Description for $db
 *  @param [in] $table 	Description for $table
 *  @return    Return description
 *  
 *  @example   resetRowid
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2020-01-28T10:47:05
 */
function resetRowid( &$db, $table ) {
    $sqlQueue = "
-- Reset rowid
-- Create temporary table
CREATE TABLE IF NOT EXISTS ${table}_destination 
    AS SELECT * FROM $table;
-- SELECT * FROM ${table}_destination;
-- Delete from $table
DELETE FROM $table;
-- Reload source from temporary
INSERT INTO $table
    SELECT * FROM ${table}_destination;
-- SELECT rowid, * FROM $table;
";
    $db->exec( $sqlQueue );
}   // resetRowid()


//----------------------------------------------------------------------

function dbNoOfRecords( &$db, $table, $where = ""  ) {
    $no = querySqlSingleValue( $db, "SELECT count(*) FROM $table $where;" );
    return( $no );
}

//----------------------------------------------------------------------


function dbLastEntry(  &$db, $table, $orderfield = '*', $where = "" ) {
//function dbLastEntry(  &$db, $table, $where = "" ) {
    //$sql    = "SELECT $orderfield FROM $table $where ORDER BY $orderfield LIMIT 1;";
    //$sql    = "SELECT * FROM $table $where ORDER BY $orderfield LIMIT 1;";
	//https://stackoverflow.com/a/53947463
    //$sql    = "SELECT * FROM $table $where ORDER BY rowid DESC LIMIT 1;";
    $sql    = "SELECT $orderfield FROM $table $where ORDER BY rowid DESC LIMIT 1;";
	//error_log( $sql );
    //$no = querySqlSingleValue( $db, $sql );
    $no = querySqlSingleRow( $db, $sql );
	//error_log( var_export( $no, TRUE) );
    return( $no );
}	//*** dbLastEntry() ***

//----------------------------------------------------------------------

function dbSchema( &$db, $table, $where = ""  ) {
    $sql    = "SELECT type, name, tbl_name, REPLACE( sql , char(10), '<BR>' ) as sql
    FROM    sqlite_master";
    return( querySql( $db, $sql ) );
}

//----------------------------------------------------------------------

/**
 *  @fn        vacuumInto
 *  @brief     Vacuum current database to a new database file
 *  
 *  @param [in] $db        Handle to current database
 *  @param [in] $newdbfile File name for new database
 *  @return    VOID
 *  
 *  @details   As of SQLite 3.27.0 (2019-02-07), it is also possible 
 *  to use the statement VACUUM INTO 'file.db'; to backup the database 
 *  to a new file.
 *  
 *  @example    $db = openSqlDb( "source.db" );
 *              var_export( vacuumInto($db, "target.db" ) );
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://www.php.net/manual/en/sqlite3.backup.php
 *  @since     2022-01-16T22:32:40 / erba
 */
function vacuumInto( &$db, $newdbfile ) {
    if ( ! file_exists( $newdbfile ) ) {
        $sql = "VACUUM INTO '$newdbfile';";
        return( executeSql( $db, $sql ) );
    } else
        trigger_error( "Database already exists: [$newdbfile]", E_USER_WARNING );
    return( FALSE );
}   //*** vacuumInto() ***

//----------------------------------------------------------------------

/** 
	https://github.com/ephestione/php-sqlite-dump/blob/master/sqlite_dump.php
	PHP SQLite Dump

Tired of searching for "dump sqlite php" on the interwebs and 
finding only people suggesting to use the sqlite3 tool from 
CLI, or using PHP just as a wrapper for said sqlite3 tool? Look 
no further!

*/
function dbDump( $filename, $dumpfile) {
	//$db = new SQLite3(dirname(__FILE__)."/your/db.sqlite");
	$db = new SQLite3( $filename );
	$db->busyTimeout(5000);
	$length	= 0;

	$sql="-- Dumping '$filename' to '$dumpfile'\n";
	file_put_contents($dumpfile,$sql);
	
	$tables     =   $db->query("SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';");
    trigger_error( "List of tables: " . print_r($tables), E_USER_NOTICE );
	//$tables     =   $db->query( $sql_config['get_table_names' ]);

	while ($table=$tables->fetchArray(SQLITE3_NUM)) {
        trigger_error( "Table name: '{$table[0]}'", E_USER_NOTICE );
		$sql.=$db->querySingle("SELECT sql FROM sqlite_master WHERE name = '{$table[0]}'").";\n\n";
		$rows=$db->query("SELECT * FROM {$table[0]}");
		$sql.="INSERT INTO {$table[0]} (";
		$columns=$db->query("PRAGMA table_info({$table[0]})");
		$fieldnames=array();
		while ($column=$columns->fetchArray(SQLITE3_ASSOC)) {
			$fieldnames[]=$column["name"];
		}
		$sql.=implode(",",$fieldnames).") VALUES";
		while ($row=$rows->fetchArray(SQLITE3_ASSOC)) {
			foreach ($row as $k=>$v) {
				//if ( empty( $v ) ) 	trigger_error( "Empty value [$v] in key [$k]", E_USER_WARNING );
				$row[$k]="'".SQLite3::escapeString("$v")."'";
			}
			//$sql.="\n(".implode(",",$row)."),";
            file_put_contents( $dumpfile, $sql . "\n(".implode(",",$row).");" , FILE_APPEND );
		}
		/*
        $sql=rtrim($sql,",").";\n\n";
        trigger_error( "SQL: " . print_r($sql), E_USER_NOTICE );
		file_put_contents( $dumpfile, $sql, FILE_APPEND );
		*/
        $length	+= strlen($sql);
		$sql 	= "";
	}
	file_put_contents($dumpfile,"-- Done", FILE_APPEND );
	//file_put_contents( $dumpfile,$sql, FILE_APPEND );
	//file_put_contents("sqlitedump.sql",$sql);
	return( $length );
}	//*** dbDump() ***

//----------------------------------------------------------------------

/**
 *  @fn        getDbFile
 *  @brief     Get full path to database
 *  
 *  @param [in] $db        Handle to current database
 *  @return    Path as string
 *  
 *  @details   More details
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/44279467
 *  @since     2022-04-26T14:15:52 / erba
 */
function getDbFile( &$db ) {
    $name = querySqlSingleValue( $db, "SELECT file FROM pragma_database_list WHERE name='main';" );
    return( $name );
}   //*** getDbFile() ***

//----------------------------------------------------------------------

/**
 *  @fn        getDbName
 *  @brief     Get database name
 *  
 *  @param [in] $db     	Handle to current database
 *  @return    Return description
 *  
 *  @details   More details
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/44279467
 *  @since     2022-04-26T14:16:08 / erba
 */
function getDbName( &$db ) {
    $name = querySqlSingleValue( $db, "SELECT file FROM pragma_database_list WHERE name='main';" );
    return( basename( $name ) );
}   //*** getDbName() ***

//----------------------------------------------------------------------

function flatten(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}

//----------------------------------------------------------------------

?>
