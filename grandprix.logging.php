<?php
    /**
     * Defines the necessary methods for a class to be a LogManager.
     * A log manager is meant to expose static methods for logging application events
     * in a store-independent manner.
     * 
     * @package Grandprix
     * @subpackage Logging
     */
    interface ILogManager
    {
        /**
         * Logs an exception.
         *
         * @param Exception $ex
         */
        public static function logException($ex);
        
        /**
         * Logs a message.
         *
         * @param str $message
         */
        public static function logMessage($message);
        
        /**
         * Logs a user action
         *
         * @param Action $action
         */
        public static function logAction($action);
        
        /**
         * Logs a new entry.
         *
         * @param str $action
         * @param str $date
         * @param str $message
         */
        public static function log($action, $date, $message);
        
        /**
         * Logs a SQL query
         *
         * @param string $sqlStatement
         * @param array $params
         */
        public static function logSQLQuery($sqlStatement, $params);
    }
    
    /**
     * Logging singleton using Settings
     *
     * @package Grandprix
     * @subpackage Logging
     */
    class LogManager implements ILogManager
    {
        const LOG_ENTITY = 'Prix_log';
        const LOGGER_FILE = 'FileLogManager';
        const LOGGER_DB = 'DatabaseLogManager';
        const LOGGER_BOTH = 'MultiLogManager';
        
        private static $instance;
        private static $userId;
        
        /**
         * Gets current instance using Settings
         *
         * @return ILogManager
         */
        private static function getInstance()
        {
            if(is_null(self::$instance))
            {
                $logClassName = class_exists("Settings")
                                ? Settings::getSetting(Settings::KEY_LOG_MANAGER)
                                : SystemSettings::getSetting(SystemSettings::KEY_LOG_MANAGER);
                self::$instance = new $logClassName;
                self::$userId = null;
            }
           
           return self::$instance;
        }
        
        /**
         * Logs an exception.
         *
         * @param Exception $ex
         */
        public static function logException($ex)
        {
           self::getInstance()->logException($ex);
        }
        
        /**
         * Logs a message.
         *
         * @param str $message
         */
        public static function logMessage($message)
        {
           self::getInstance()->logMessage($message);
        }
         
        /**
         * Logs a user action
         *
         * @param Action $action
         */
        public static function logAction($action)
        {
           self::getInstance()->logAction($action);
        }
        
        /**
         * Logs a new entry
         *
         * @param string $action
         * @param string $date
         * @param string $message
         */
        public static function log($action, $date, $message)
        {
           self::getInstance()->log($action, $date, $message);
        }
        
        /**
          * Logs a SQL query
          *
          * @param string $sqlStatement
          * @param array $params
          */
        public static function logSQLQuery($sqlStatement, $params)
        {
           $paramsValues = array();
           
           foreach($params as $param)
           {
              $paramsValues[] = $param->__toString();
           }
           
           self::getInstance()->logSQLQuery($sqlStatement, $paramsValues);
        }
        
        /**
         * Handles errors
         * 
         */
        public static function handleError($errno, $errstr, $errfile, $errline)
        {
           if ($errno == E_NOTICE || $errno == E_USER_NOTICE || $errno == E_STRICT)
              return true;
           
           $message = "$errstr \n($errfile : $errline)";
           self::logMessage($message);
           
           return true;
        }
        
        /**
         * Logs a SQL statement with error
         *
         * @param string $sql
         * @param array $params
         */
        public static function logSQLError($sql, $params)
        {
            $message = "Error executing SQL: \n" . $sql . "\nwith params: ";
            $subject = "Error DB at " . $_SERVER['SERVER_NAME'];
           
            foreach ($params as $param)
            {
                if (is_null($param->Value))
                    $message .= $param->Name . " = NULL, ";
                else
                    $message .= $param->Name . " = '" . $param->Value . "', ";
            }
           
           self::logMessage($message);
           self::sendEmail($subject, $message);
        }
        
        /**
         * Handles exceptions
         *
         * @param Exception $exception
         */
        public static function handleException($exception)
        {
            if(isset($_SERVER["HTTP_HOST"]) == false || $_SERVER["HTTP_HOST"] == 'localhost')
                var_dump($exception);
                
            self::logException($exception);
        }
        
        public static function setUserId($value) { self::$userId = $value; }
        public static function getUserId() { return self::$userId; }
    }
    
    /**
     * Pojo to save user's action information
     *
     * @package Grandprix
     * @subpackage Logging
     */
    class Action
    {
        /**
         * @var str
         */
        public $Name;
        /**
         * @var array
         */
        public $Arguments;
        /**
         * @var int
         */
        public $User;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param int $user
         * @param array $arguments
         */
        public function __construct($name, $user, $arguments = array())
        {
            $this->Name = $name;
            $this->Arguments = $arguments;
            $this->User = $user;
        }
        
        public function __toString()
        {
          $string = $this->Name . " Arguments = [" . implode('; ', $this->Arguments) . "]";
          
          if (!is_null($this->User))
             $string .= " User = " . $this->User;
             
          return $string;
        }
    }
    
    /**
     * File-based and database-based log Manager
     * 
     * @package Grandprix
     * @subpackage Logging
     */
    class MultiLogManager implements ILogManager
    {
        /**
         * Logs an exception.
         *
         * @param Exception $ex
         */
        public static function logException($ex)
        {
           DatabaseLogManager::logException($ex);
           FileLogManager::logException($ex);
        }
        
        /**
         * Logs a message.
         *
         * @param str $message
         */
        public static function logMessage($message)
        {
           DatabaseLogManager::logMessage($message);
           FileLogManager::logMessage($message);
        }
        
        /**
         * Logs a new entry.
         *
         * @param str $action
         * @param str $date
         * @param str $message
         */
        public static function log($action, $date, $message)
        {
           DatabaseLogManager::log($action, $date, $message);
           FileLogManager::log($action, $date, $message);
        }
        
        /**
         * Logs a user action
         *
         * @param Action $action
         */
        public static function logAction($action)
        {
           DatabaseLogManager::logAction($action);
           FileLogManager::logAction($action);
        }
        
        /**
         * Logs a SQL query
         *
         * @param string $sqlStatement
         * @param array $params
         */
        public static function logSQLQuery($sqlStatement, $params)
        {
           DatabaseLogManager::logSQLQuery($sqlStatement, $params);
           FileLogManager::logSQLQuery($sqlStatement, $params);
        }
    }
    
    /**
     * File-based Log Manager
     * 
     * @package Grandprix
     * @subpackage Logging
     */
    class FileLogManager implements ILogManager
    {
        /**
         * Logs an exception.
         *
         * @param Exception $ex
         */
        public static function logException($ex)
        {
            if (class_exists("Settings"))
            {
                file_put_contents(Settings::getSetting(Settings::KEY_LOG_MANAGER_FILE),
                ">>exception\t" . date('Y-m-d H:i:s') . "\t" .
                "[Code = " . $ex->getCode() . ", Message = " . $ex->getMessage() . ", File = " . $ex->getFile() . ", Line = " . $ex->getLine() . ", StackTrace = " . $ex->getTraceAsString() . "]" . "\r\n", FILE_APPEND);
            }
            else
            {
                file_put_contents(SystemSettings::getSetting(SystemSettings::KEY_LOG_MANAGER_FILE),
                ">>exception\t" . date('Y-m-d H:i:s') . "\t" .
                "[Code = " . $ex->getCode() . ", Message = " . $ex->getMessage() . ", File = " . $ex->getFile() . ", Line = " . $ex->getLine() . ", StackTrace = " . $ex->getTraceAsString() . "]" . "\r\n", FILE_APPEND);
            }
        }
        
        /**
         * Logs a message.
         *
         * @param str $message
         */
        public static function logMessage($message)
        {
            if (class_exists("Settings"))
            {
                file_put_contents(
                    Settings::getSetting(Settings::KEY_LOG_MANAGER_FILE),
                    ">>message\t" . date('Y-m-d H:i:s') . "\t" . $message . "\r\n", FILE_APPEND);
            }
            else
            {
                file_put_contents(
                    SystemSettings::getSetting(SystemSettings::KEY_LOG_MANAGER_FILE),
                    ">>message\t" . date('Y-m-d H:i:s') . "\t" . $message . "\r\n", FILE_APPEND);
            }
        }
        
        /**
         * Logs a new entry.
         *
         * @param str $action
         * @param str $date
         * @param str $message
         */
        public static function log($action, $date, $message)
        {
            file_put_contents(
                Settings::getSetting(Settings::KEY_LOG_MANAGER_FILE),
                ">>$action\t" . $date . "\t" . $message . "\r\n", FILE_APPEND);
        }
        
        /**
         * Logs a user action
         *
         * @param Action $action
         */
        public static function logAction($action)
        {
            file_put_contents(
                Settings::getSetting(Settings::KEY_LOG_MANAGER_FILE),
                ">>action\t" . date('Y-m-d H:i:s') . "\t" . $action . "\r\n", FILE_APPEND);
        }
        
        /**
         * Logs a SQL query
         *
         * @param string $sqlStatement
         * @param array $params
         */
        public static function logSQLQuery($sqlStatement, $params)
        {
          $content = "SQL = " . $sqlStatement . "; Params = " . implode(", ", $params);
          
          file_put_contents(
                Settings::getSetting(Settings::KEY_LOG_MANAGER_FILE),
                ">>query\t" . date('Y-m-d H:i:s') . "\t" . $content . "\r\n", FILE_APPEND);
        }
    }
    
    /**
     * Database-based Log Manager
     * 
     * @package Grandprix
     * @subpackage Logging
     */
    class DatabaseLogManager implements ILogManager
    {
        /**
         * Logs an exception.
         *
         * @param Exception $ex
         */
        public static function logException($ex)
        {
           self::writeLog('Exception', null,
              "[Code = " . $ex->getCode() . ", Message = " . $ex->getMessage() . ", File = " . $ex->getFile() . ", Line = " . $ex->getLine() . ", StackTrace = " . $ex->getTraceAsString() . "]");
        }
        
        /**
         * Logs a message.
         *
         * @param str $message
         */
        public static function logMessage($message)
        {
           self::writeLog('Message', null, $message);
        }
        
        /**
         * Logs a user action
         *
         * @param Action $action
         */
        public static function logAction($action)
        {
           self::writeLog($action->Name, $action->User, 'Arguments: ' . implode('; ', $action->Arguments));
        }
        
        /**
         * Logs a SQL query
         *
         * @param string $sqlStatement
         * @param array $params
         */
        public static function logSQLQuery($sqlStatement, $params)
        {
            $content = "SQL = " . $sqlStatement . "; Params = " . implode(", ", $params);
           
            self::writeLog('SQLQuery', LogManager::getUserId(), $content);
        }
        
        /**
         * Logs a new entry.
         *
         * @param str $action
         * @param str $date
         * @param str $message
         */
        public static function log($action, $date, $message)
        {
            // @todo Handle Logging to Sql
            $query = new DataQuery("INSERT INTO " . LogManager::LOG_ENTITY . " (Action, User, Message, LogDate) " .
                                   "VALUES (?,NULL,?,?)");
            
            $params = array();
            $params[] = new DataParameter('Action', DataParameter::TYPE_VARCHAR, $action);
            $params[] = new DataParameter('Message', DataParameter::TYPE_VARCHAR, $message);
            $params[] = new DataParameter('Date', DataParameter::TYPE_VARCHAR, $date);
            
            $result = DataContext::execute($query, $params, false);
        }
        
        /**
         * Writes log in database
         *
         * @param str $action
         * @param str $user
         * @param str $message
         */
        private static function writeLog($action, $user, $message)
        {
            // @todo Handle Logging to Sql
            $query = new DataQuery("INSERT INTO " . LogManager::LOG_ENTITY . " (Action, User, Message, LogDate) " .
                                   "VALUES (?,?,?,now())");
            
            $params = array();
            $params[] = new DataParameter('Action', DataParameter::TYPE_VARCHAR, $action);
            $params[] = new DataParameter('User', DataParameter::TYPE_VARCHAR, $user);
            $params[] = new DataParameter('Message', DataParameter::TYPE_VARCHAR, $message);
            
            $result = DataContext::execute($query, $params, false);
            
            if ($result == -1)
               FileLogManager::logSQLQuery($query->SqlStatement, $params);
        }
    }
?>