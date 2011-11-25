<?php

    /**
     * Represents a pojo-type class to hold information about a query.
     * 
     * @package Grandprix
     * @subpackage Data
     */
    class DataQuery
    {
        /**
         * Creates a new instance of a SQL Query
         *
         * @param str $sqlStatement
         * 
         */
        public function __construct($sqlStatement)
        {
            $this->SqlStatement = $sqlStatement;
        }
        
        public $SqlStatement = ""; // The statement to execute.
    }
    
    /**
     * Represents a DataQuery parameter passed as a query argument in prepared statements.
     *
     * @package Grandprix
     * @subpackage Data
     */
    class DataParameter
    {
        const TYPE_VARCHAR = 's';
        const TYPE_INTEGER = 'i';
        const TYPE_DOUBLE = 'd';
        const TYPE_BINARY = 'b';
        
        public $Name;
        public $SqlType;
        public $Value;
        
        /**
         * Creates a new instance of this class.
         *
         * @param str $name The name of the parameter
         * @param str $type One of the 'TYPE_' - prefixed constants defined by this class
         * @param str $value The value of the parameter
         */
        public function __construct($name, $type, $value)
        {
            $this->Name = $name;
            $this->SqlType = $type;
            $this->Value = $value;
        }
        
        public function __toString()
        {
            return $this->Name . ': ' . $this->Value;
        }
    }

    /**
     * Provides a data access layer with mysql-i(mproved) library.
     * The connection pool, sql queries, and commands must all be executed
     * through this class.
     * 
     * @package Grandprix
     * @subpackage Data
     */
    class DataContext
    {
        // used as flags
        const LOG_LEVEL_EXECUTE = 1;
        const LOG_LEVEL_QUERY = 2;
        
        const LOG_LEVEL_NONE = 0;
        const LOG_LEVEL_ALL = 3; // LOG_LEVEL_EXECUTE | LOG_LEVEL_QUERY
        
        protected $username;
        protected $password;
        protected $hostname;
        protected $port;
        protected $schema;

        protected $logLevel = self::LOG_LEVEL_NONE;
        protected $connectionManager;
        protected $isConnected;

        public $lastInsertedId;
        
        protected function __construct($username, $password, $hostname, $schema, $port)
        {
            $this->username = $username;
            $this->password = $password;
            $this->hostname = $hostname;
            $this->port = $port;
            $this->schema = $schema;
        }
        /**
         * Connects to default database using settings
         *
         */
        public function connect()
        {
            if ($this->isConnected()) return;

            $this->connectionManager = new mysqli(
                $this->hostname, $this->username, $this->password, $this->schema, $this->port);
            
            if (mysqli_connect_errno())
                $this->isConnected = false;
            else
                $this->isConnected = true;
        }
        
        /**
         * Determines if the connection to the database has already been established.
         *
         * @return bool
         */
        public function isConnected()
        {
            return $this->isConnected;
        }
        
        /**
         * Preparses SQL statement for clear NULL values
         *
         * @param string $query
         * @param array &$params
         * @return string
         */
        private static function preparseStatement($query, &$params)
        {
            $pos = 0;
            $newQuery = $query;
            
            foreach ($params as $key => $param)
            {
                if (($pos = strpos($newQuery, '?', $pos + 1)) === false)
                   break;
                
                if (!is_null($param->Value))
                   continue;

                $newQuery = substr_replace($newQuery, 'NULL', $pos, 1);
                unset($params[$key]);
            }     
                   
            return $newQuery;
        }

        /**
         * Executes a SQL statement that returns a resultset.
         * The resultset is returned as an indexed array of associative arrays.
         * This method works well for limited resultsets.
         * As a rule of thumb the amount of data returned from the database should always be limited.
         *
         * @param DataQuery $dataQuery
         * @param array $dataParameters of DataParameter
         * 
         * @return array
         */
        public function query($dataQuery, $dataParameters)
        {
            // Check for connection pool
            $this->connect();
            
            if (!$this->isConnected())
            {
                throw new GrandprixException(GrandprixException::EX_NODBCONNECTION, mysqli_connect_error());
            }
            
            // Prepare the SQL statement
            $dataQuery->SqlStatement = self::preparseStatement($dataQuery->SqlStatement, $dataParameters);
            $statement = $this->connectionManager->prepare($dataQuery->SqlStatement);
            
            if ($this->logLevel & self::LOG_LEVEL_QUERY)
               LogManager::logSQLQuery($dataQuery->SqlStatement, $dataParameters);
            
            if ($statement == false)
               throw new GrandprixException(GrandprixException::EX_DBQUERY, mysqli_connect_error());
            
            // Create the types string
            $callbackArgs = array();
            $callbackArgs[0] = '';
            
            foreach ($dataParameters as $dataParam)
            {
                $callbackArgs[0] .= $dataParam->SqlType;
            }
            
            // Add the values in the callback arguments
            foreach ($dataParameters as $dataParam)
            {
                if (phpversion() > '5.3')
                    $callbackArgs[count($callbackArgs)] = &$dataParam->Value;
                else
                    $callbackArgs[count($callbackArgs)] = &$dataParam->Value;
            }
            
            // Bind the parameters dynamically
            if (count($dataParameters) > 0)
            {
                call_user_func_array(array($statement,'bind_param'), $callbackArgs);
            }
            
            // Execute the statement
            $statement->execute();
            
            // store the result
            $statement->store_result();
            
            $row = array();
            $rows = array();
            self::bindResultRow($statement, $row);
            
            while ($statement->fetch())
            {
                $rowKeys = array_keys($row);
                $rowCopy = array();
                
                foreach($rowKeys as $rowKey)
                    $rowCopy[$rowKey] = $row[$rowKey];

                $rows[] = $rowCopy;               
            }
            
            $statement->free_result();
            // Close the query
            $statement->close();
            
            return $rows;
        }
        
        /**
         * Executes a SQL statement that does not return a result set.
         * Use this method to insert, update or delete records.
         * 
         * @param DataQuery $dataQuery
         * @param array $dataParameters of DataParameter
         * @param bool $throwExceptionOnError
         *
         * @return int The number of affected rows
         */
        public function execute($dataQuery, $dataParameters, $throwExceptionOnError = false)
        {
            // Check for connection pool
            $this->connect();
            if (!$this->isConnected())
            {
                throw new GrandprixException(GrandprixException::EX_NODBCONNECTION, mysqli_connect_error());
            }
            
            // Prepare the SQL statement
            $dataQuery->SqlStatement = self::preparseStatement($dataQuery->SqlStatement, $dataParameters);
            
            if ($this->logLevel & self::LOG_LEVEL_EXECUTE)
               LogManager::logSQLQuery($dataQuery->SqlStatement, $dataParameters);
            
            $statement = $this->connectionManager->prepare($dataQuery->SqlStatement);
            if ($statement === false)
               throw new GrandprixException(GrandprixException::EX_DBQUERY, mysqli_error($this->connectionManager));

            // Create the types string
            $callbackArgs = array();
            $callbackArgs[0] = '';
        
            foreach ($dataParameters as $dataParam)
            {
                $callbackArgs[0] .= $dataParam->SqlType;
            }
            
            // Add the values in the callback arguments
            foreach ($dataParameters as $dataParam)
            {
                if (phpversion > '5.3')
                    $callbackArgs[count($callbackArgs)] = &$dataParam->Value;
                else
                    $callbackArgs[count($callbackArgs)] = $dataParam->Value;
            }
            
            // Bind the parameters dynamically
            if (count($dataParameters) > 0)
            {
                call_user_func_array(array($statement,'bind_param'), $callbackArgs);
            }
            
            // Execute the statement
            $statement->execute();

            $this->lastInsertedId = $statement->insert_id;
            $error = $statement->errno;
            $statement->close();
            
            if ($throwExceptionOnError && $error)
                throw new DataException($error);
            
            return $this->connectionManager->affected_rows;
        }
        
        /**
         * Binds an output array to a prepared statement
         *
         * @param mysqli_stmt $stmt
         * @param array $out The results array to bind to
         */
        private static function bindResultRow(&$stmt, &$out)
        {
            $result = $stmt->result_metadata();
            $fields = array();
            $out = array();
            
            while($field = $result->fetch_field())
            {
                $out[$field->name] = "";
                $fields[] = &$out[$field->name];
            }
            
            $return = call_user_func_array(array($stmt,'bind_result'), $fields);
        }

        /**
         * Starts a transaction
         */
        public function transactionBegin()
        {
            $this->connect();
            $this->connectionManager->autocommit(false);
        }
        
        /**
         * Commits an open transaction
         */
        public function transactionCommit()
        {
            $this->connectionManager->commit();
            $this->connectionManager->autocommit(true);
        }
        
        /**
         * Rollsback a transaction
         */
        public function transactionRollback()
        {
            $this->connectionManager->rollback();
            $this->connectionManager->autocommit(true);
        }

        /**
         * Extracts tables from database
         *
         * @return array
         */
        public function getTables()
        {
            $sqlStm = "SELECT TABLE_NAME as tableName,
                    CASE TABLE_TYPE WHEN 'VIEW' THEN 'true'
                    ELSE 'false'
                    END as isView
                    FROM information_schema.tables
                    WHERE TABLE_SCHEMA = ?";
            
            $query = new DataQuery($sqlStm);
            $param = new DataParameter('schema', DataParameter::TYPE_VARCHAR , $this->schema);
            return  $this->query($query, array($param));
        }
        /**
         * Extracts primary keys of a table
         *
         * @param str $tableName
         * @return array
         */
        public function getPrimaryKeys($tableName)
        {
            $sqlStm = "SELECT COLUMN_NAME as columnName, DATA_TYPE as dataType FROM information_schema.columns
                        WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?
                        AND COLUMN_KEY = 'PRI'";
            
            $query = new DataQuery($sqlStm);
            $params = array();
            $params[] = new DataParameter('tableName', DataParameter::TYPE_VARCHAR , $tableName);
            $params[] = new DataParameter('schema', DataParameter::TYPE_VARCHAR , $this->schema);
            return $this->query($query, $params);
        }
        
        /**
         * Extracts columns from table
         *
         * @param str $tableName
         * @return array
         */
        public function getColumns($tableName)
        {
            $sqlStm = "SELECT COLUMN_NAME as columnName, COLUMN_DEFAULT as defaultValue, DATA_TYPE as dataType,
                        IFNULL(CHARACTER_MAXIMUM_LENGTH, 0) as size,
                        CASE IS_NULLABLE WHEN 'NO' THEN 'true'
                        ELSE 'false'
                        END as isRequired,
                        CASE EXTRA WHEN 'auto_increment' THEN 'true'
                        ELSE 'false'
                        END as isAutoNumber
                        FROM information_schema.columns
                        WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?";
            
            $query = new DataQuery($sqlStm);
            $params = array();
            $params[] = new DataParameter('tableName', DataParameter::TYPE_VARCHAR , $tableName);
            $params[] = new DataParameter('schema', DataParameter::TYPE_VARCHAR , $this->schema);
            return $this->query($query, $params);
        }
        
        /**
         * Extracts relations from table
         *
         * @param str $table
         * @return array
         */
        public function getRelations($tableName)
        {
            $sqlStm = "SELECT REFERENCED_TABLE_NAME as tableName, REFERENCED_COLUMN_NAME as foreignColumnName,
                        COLUMN_NAME as localColumnName
                        FROM information_schema.key_column_usage 
                        WHERE TABLE_NAME = ? AND CONSTRAINT_NAME != 'PRIMARY'";
            
            $query = new DataQuery($sqlStm);
            $params = array();
            $params[] = new DataParameter('tableName', DataParameter::TYPE_VARCHAR , $tableName);
            return $this->query($query, $params);
        }
        
        /**
         * Extracts children from table
         *
         * @param str $tableName
         * @return array
         */
        public function getChildren($tableName)
        {
            $sqlStm = "SELECT TABLE_NAME as tableName, REFERENCED_COLUMN_NAME as localColumnName,
                        COLUMN_NAME as foreignColumnName 
                        FROM information_schema.key_column_usage 
                        WHERE REFERENCED_TABLE_NAME = ?";	
            
            $query = new DataQuery($sqlStm);
            $params = array();
            $params[] = new DataParameter('tableName', DataParameter::TYPE_VARCHAR , $tableName);
            return $this->query($query, $params);
        }
        
        /**
         * Gets the equivalent PHP type as a string
         *
         * @param string $sqlDataType
         * @return string
         */
        protected static function getPhpType($sqlDataType)
        {
            if (strpos($sqlDataType, "char") !== false)
                return "string";
          
            if (strpos($sqlDataType, "decimal") !== false)
                return "float";
           
            if (strpos($sqlDataType, "date") !== false ||
                strpos($sqlDataType, "time") !== false)
                return "DateTime";
           
            if (strpos($sqlDataType, "tinyint") !== false ||
                strpos($sqlDataType, "bool") !== false)
                return "bool";
            
            if (strpos($sqlDataType, "int") !== false)
                return "int";
          
            return "string";
        }

        /**
         * Gets the equivalent DataParameter type
         *
         * @param string $sqlDataType
         * @return string
         */
        private static function getDataParameterType($sqlDataType)
        {
            if (strpos($sqlDataType, "char") !== false)
                return 'DataParameter::TYPE_VARCHAR';
          
            if (strpos($sqlDataType, "decimal") !== false)
                return 'DataParameter::TYPE_DOUBLE';
           
            if (strpos($sqlDataType, "date") !== false ||
                strpos($sqlDataType, "time") !== false)
                return 'DataParameter::TYPE_VARCHAR';
           
            if (strpos($sqlDataType, "tinyint") !== false ||
                strpos($sqlDataType, "bool") !== false)
                return 'DataParameter::TYPE_INTEGER';
            
            if (strpos($sqlDataType, "int") !== false)
                return 'DataParameter::TYPE_INTEGER';
          
            return 'DataParameter::TYPE_VARCHAR';
        }
        
        /**
         * Generates the necessary DataEntity-extended classes for the database
         *
         * @return string
         */
        public function generateEntityCode()
        {
            $tables = $this->getTables();
            
            $code = "    require_once 'grandprix.data.php';";
            foreach ($tables as $table)
            {
                $columns = $this->getColumns($table['tableName']);
                $children = $this->getChildren($table['tableName']);
                
                $code .=
"
    /**
     * " . ucfirst($table['tableName']) . " Data Entity class.
     * 
     * @package Grandprix
     * @subpackage Data
     */
    class " . ucfirst($table['tableName']) . " extends DataEntity
    {    
";
        
                foreach ($columns as $column)
                {
                    $code .=
"
        /**
         * @var " . self::getPhpType($column['dataType']) . "
         */
        public \$" . ucfirst($column['columnName']) . ";
";
                }

                    $code .=
"
        /**
         * Creates an empty instance of this class.
         * 
         * @return " . ucfirst($table['tableName']) . "
         */
        public static function createInstance()
        {
            \$className = __CLASS__; return new \$className();
        }
        
        /**
         * Creates an instance of this class based on the provided data array.
         *
         * @param array \$data The keyed array containing the data
         * @return " . ucfirst($table['tableName']) . "
         */
        public static function fromData(&\$data)
        {
            \$entity = self::createInstance();
            \$entity->setObjectData(\$data);
            return \$entity;
        }
    }
";
              
            }
            
            return $code;
        } // end of generateEntityCode
        
        /**
         * Generates a basic DataContext-extended class that provides
         * generic CRUD operations for the database.
         *
         * @return string
         */
        public function generateDataContextCode($dataContextName, $throwExceptionsOnError = false)
        {
            $tables = $this->getTables();
            $throwExceptionsOnError = $throwExceptionsOnError ? 'true' : 'false';
            $code =
"    require_once \"grandprix.data.php\";
    
    /**
     * Provides a single access point to perform CRUD operations against the database.
     *
     * @package Grandprix
     * @subpackage Data
     */
    class " . $dataContextName . " extends DataContext
    {
        /**
         * @var " . $dataContextName . "
         */
        protected static \$instance;

        /**
         * Creates a new instance of this class. This is intended to be used
         * as a singleton. In order to get an instance of this class 
         * use the getInstance method
         */
        protected function __construct()
        {
            parent::__construct(
                Settings::getSetting(Settings::KEY_DATA_USERNAME),
                Settings::getSetting(Settings::KEY_DATA_PASSWORD),
                Settings::getSetting(Settings::KEY_DATA_HOSTNAME),
                Settings::getSetting(Settings::KEY_DATA_SCHEMA),
                Settings::getSetting(Settings::KEY_DATA_PORT));
            
            \$this->logLevel = Settings::getSetting(Settings::KEY_LOG_LEVEL);
        }
        
        /**
         * Retrieves the singleton instance for this class
         *
         * @return " . $dataContextName . "
         */
        public static function getInstance()
        {
            if (is_null(self::\$instance))
                self::\$instance = new " . $dataContextName . "();

            return self::\$instance;
        }
";
            foreach ($tables as $table)
            {
                $priKeys = $this->getPrimaryKeys($table['tableName']);
                $params = array();
                $columns = $this->getColumns($table['tableName']);
                $cols = array();
                $selectWhere = array();
                $insertCols = array();
                foreach ($columns as $column)
                {
                    $cols[] = $column['columnName'];
                    if ($column['isAutoNumber'] == 'false')
                    {
                        $insertCols[] = $column;
                    }
                }
// Start of DataEntity_Retrieve
                $code .=
"
        /**
         * Retrieves a uniquely-identified " . ucfirst($table['tableName']) . " data entity.";
                foreach ($priKeys as $priKey)
                {
                    $params[] = $priKey['columnName'];
                    $selectWhere[] = $priKey['columnName'] . ' = ? ';
                    $code .=
"
         * @param " . self::getPhpType($priKey['dataType']) . " \$" . strtolower($priKey['columnName']) . "
";
                }

                $code .=
"         * @return " . ucfirst($table['tableName']) . "
         */
        public function " . ucfirst($table['tableName']) . "_Retrieve(\$" . implode(', $', $params) . ")
        {
            \$query = new DataQuery('SELECT " . implode(', ', $cols) . " FROM " . $table['tableName'] . " WHERE " . implode(',', $selectWhere) . " LIMIT 1', array());
            \$params = array();";
            foreach ($priKeys as $param)
            {
                $code .=
"
            \$params[] = new DataParameter('" . $param['columnName'] . "', " . self::getDataParameterType($param['dataType']) . ", \$" . $param['columnName'] . ");";
            }
            $code .=
"
            \$result = \$this->query(\$query, \$params);
            if (count(\$result) == 0) return null;
            return " . ucfirst($table['tableName']) . "::fromData(\$result[0]);
        }
"; // end of DataEntity_Retrieve Function

// Start of DataEntity_Delete
                $code .=
"
        /**
         * Deletes a uniquely-identified " . ucfirst($table['tableName']) . " data entity.";
                foreach ($priKeys as $priKey)
                {
                    $code .=
"
         * @param " . self::getPhpType($priKey['dataType']) . " \$" . strtolower($priKey['columnName']) . "
";
                }

                $code .=
"         * @return int The number of affected rows.
         */
        public function " . ucfirst($table['tableName']) . "_Delete(\$" . implode(', $', $params) . ")
        {
            \$query = new DataQuery('DELETE FROM " . $table['tableName'] . " WHERE " . implode(',', $selectWhere) . " LIMIT 1', array());
            \$params = array();";
            foreach ($priKeys as $param)
            {
                $code .=
"
            \$params[] = new DataParameter('" . $param['columnName'] . "', " . self::getDataParameterType($param['dataType']) . ", \$" . $param['columnName'] . ");";
            }
            $code .=
"
            return \$this->execute(\$query, \$params, $throwExceptionsOnError);
        }
"; // end of DataEntity_Delete Function

// Start of DataEntity_Create
                $insertParams = array();
                $insertArgs = array();
                $updateParams = array();
                foreach ($insertCols as $col)
                {
                    $updateParams[] = $col['columnName'] . ' = ?';
                    $insertParams[] = $col['columnName'];
                    $insertArgs[] = '?';
                }
                
                $code .=
"
        /**
         * Creates (or Inserts) a " . ucfirst($table['tableName']) . " data entity.
         * @param " . ucfirst($table['tableName']) . " \$entity
         * @return " . ucfirst($table['tableName']) . " The newly-created entity.
         */
        public function " . ucfirst($table['tableName']) . "_Create(\$entity)
        {
            \$query = new DataQuery('INSERT INTO " . $table['tableName'] . " (" . implode(', ', $insertParams) . ") VALUES (" . implode(', ', $insertArgs) . ")', array());
            \$params = array();";
            foreach ($insertCols as $param)
            {
                $code .=
"
            \$params[] = new DataParameter('" . $param['columnName'] . "', " . self::getDataParameterType($param['dataType']) . ", \$entity->" . ucfirst($param['columnName']) . ");";
            }
            $code .=
"
            \$affectedRows = \$this->execute(\$query, \$params);
            if (\$affectedRows == 0) return null;
            \$insertId = mysqli_insert_id(\$this->connectionManager);
            return \$this->" . ucfirst($table['tableName']) . "_Retrieve(\$insertId);
        }
"; // end of DataEntity_Create Function

// Start of DataEntity_Update
                $updateWhereParams = array();
                $retrieveParams = array();
                foreach($priKeys as $priKey)
                {
                    $updateWhereParams[] = $priKey['columnName'] . ' = ?';
                    $retrieveParams[] = '$entity->' . ucfirst($priKey['columnName']);
                }
                $code .=
"
        /**
         * Updates a " . ucfirst($table['tableName']) . " data entity.
         * @param " . ucfirst($table['tableName']) . " \$entity
         * @return " . ucfirst($table['tableName']) . " The newly-updated entity.
         */
        public function " . ucfirst($table['tableName']) . "_Update(\$entity)
        {
            \$query = new DataQuery('UPDATE " . $table['tableName'] . " SET " . implode(', ', $updateParams) . " WHERE " . implode(', ', $updateWhereParams) . " LIMIT 1', array());
            \$params = array();";
            foreach ($insertCols as $param)
            {
                $code .=
"
            \$params[] = new DataParameter('" . $param['columnName'] . "', " . self::getDataParameterType($param['dataType']) . ", \$entity->" . ucfirst($param['columnName']) . ");";
            }
            foreach ($priKeys as $param)
            {
                $code .=
"
            \$params[] = new DataParameter('" . $param['columnName'] . "', " . self::getDataParameterType($param['dataType']) . ", \$entity->" . ucfirst($param['columnName']) . ");";
            }
            $code .=
"
            \$affectedRows = \$this->execute(\$query, \$params);
            if (\$affectedRows == 0) return null;
            return \$this->" . ucfirst($table['tableName']) . "_Retrieve(" . implode(', ', $retrieveParams) . ");
        }
"; // end of DataEntity_Update Function

            } // end of foreach Table

            $code .=
"
    }
"; // end of data context code genration
            return $code;
        } // end of generateDataContextCode        
        
    }
    
    /**
     * Provides a base implementation for a data entity.
     * That is, a way to convert an associative array pulled out from the database
     * into a live object.
     *
     * @package Grandprix
     * @subpackage Data
     */
    abstract class DataEntity
    {        
        /**
         * Used to create an empty of default instance of the data entity.
         *
         * @return DataEntity
         */
        public static abstract function createInstance();
        
        /**
         * Populates matching object variables to those found in the keyed array.
         *
         * @param array $data The keyed array containing the data
         */
        public function setObjectData(&$data)
        {
            $objVarNames = array_keys(get_object_vars($this));
            $theData = $data;
            if (is_object($theData)) $theData = get_object_vars($data);
            foreach($theData as $propertyName => &$propertyValue)
            {
                foreach ($objVarNames as &$varName)
                {
                    if (strcasecmp($varName, $propertyName) == 0)
                    {
                        $this->$varName = $propertyValue;
                    }
                }
            }
        }
    }
    
    class DataException extends Exception
    {
        const EX_FK_VIOLATION    = 1451;
        const EX_DUPLICATE_KEY   = 1022;
        const EX_DUPLICATE_ENTRY = 1062;
        const EX_UQ_VIOLATION    = 1169;
        
        public function __construct($exceptionCode, $extendedMessage = '')
        {
            $message = "";
            switch ($exceptionCode)
            {
                case self::EX_FK_VIOLATION :
                    $message = "El registro ya esta siendo usado por otros registros.";
                    break;
                case self::EX_DUPLICATE_KEY :
                    $message = "La llave del registro esta en uso.";
                    break;
                case self::EX_DUPLICATE_ENTRY :
                    $message = "Ya existe una entrada con ese valor.";
                    break;
                case self::EX_UQ_VIOLATION :
                    $message = "Alguno de los campos esta duplicado";
                    break;
                default :
                    $message = "Error desconocido en la base de datos.";
                    break;
            }

            parent::__construct($message. " " . $extendedMessage, $exceptionCode);
        }
    }
?>