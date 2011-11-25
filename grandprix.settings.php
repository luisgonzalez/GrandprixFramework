<?php
    /**
     * Holds configuration setting values for the Grandprix site.
     * The active configuration is set by the defining a global called ACTIVE_CONFIGURATION_NAME
     * If this global is not defined, the 'default' configuration name will be used.
     * 
     * @package Grandprix
     * @subpackage Settings
     */
    class SystemSettings
    {
        // Configuration names
        const CONFNAME_DEFAULT  = 'default';
        const CONFNAME_DEV      = 'development';
        const CONFNAME_PROD     = 'prod';
        const CONFNAME_QA       = 'qa';
        
        // Configuration keys
        const KEY_ACTIVE_CONF = 'activeConfiguration'; // This is the only key at the root level of the tree
        
        // The following constants define the keys for each configuration entry inside a configuration set
        const KEY_LOG_MANAGER   = 'log.manager';
        const KEY_LOG_MANAGER_FILE = 'log.manager.file';
        const KEY_LOG_LASTLINE  = 'log.lastline';   // Last import file
        const KEY_LOG_LEVEL     = 'log.query';
        const KEY_DATA_HOSTNAME = 'data.hostname';  // Database hostname
        const KEY_DATA_USERNAME = 'data.username';  // Database user
        const KEY_DATA_PASSWORD = 'data.password';  // Database password
        const KEY_DATA_SCHEMA   = 'data.schema';    // Database schema
        const KEY_DATA_PORT     = 'data.port';      // Database port
        
        const KEY_MAIL_SERVER   = 'mail.server';
        const KEY_MAIL_PORT     = 'mail.port';
        const KEY_MAIL_USERNAME = 'mail.username';
        const KEY_MAIL_PASSWORD = 'mail.password';
        
        const KEY_SYSTEM_NAME   = 'system.name';
        const KEY_SYSTEM_URL   = 'system.url';
        
        protected static $hasInitialized;
        protected static $settingsArray = array();
        
        /**
         * Internally initializes all configuration settings for all CONFNAMEs
         * This method initializes these settings only once.
         * Multiple configurations can be defined. This is the place to change
         * setting values.
         *
         */
        protected static function initialize()
        {
            if (self::hasInitialized()) return;

            // Sets the active configuration based on a well-defined constant
            if (defined('ACTIVE_CONFIGURATION_NAME'))
                self::$settingsArray[SystemSettings::KEY_ACTIVE_CONF] = ACTIVE_CONFIGURATION_NAME;
            else
                self::$settingsArray[SystemSettings::KEY_ACTIVE_CONF] = SystemSettings::CONFNAME_DEFAULT;
            
            // Initialize confuguration associative arrays
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT] = array();
            self::$settingsArray[SystemSettings::CONFNAME_DEV] = array();
            self::$settingsArray[SystemSettings::CONFNAME_QA] = array();
            self::$settingsArray[SystemSettings::CONFNAME_PROD] = array();
            
            // Populates the settings for the 'default' configuration name

            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_LOG_MANAGER] = 'FileLogManager';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_LOG_MANAGER_FILE] = APPLICATION_DIR . '/logs/log' . date("Ymd") . '.log';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_LOG_LASTLINE] = '1';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_LOG_LEVEL] = DataContext::LOG_LEVEL_EXECUTE;
            
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_SYSTEM_NAME] = 'System';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_SYSTEM_URL] = 'http://tempuri.org';
            
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_DATA_HOSTNAME] = 'localhost';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_DATA_SCHEMA] = 'local';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_DATA_USERNAME] = 'root';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_DATA_PASSWORD] = '';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_DATA_PORT] = 3306;
            
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_MAIL_SERVER] = 'localhost';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_MAIL_PORT] = 25;
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_MAIL_USERNAME] = 'admin@localhost';
            self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][SystemSettings::KEY_MAIL_PASSWORD] = 'password';
        }
        
        /**
         * Gets a stored settings from a file
         *
         * @param string $key
         * @return string
         */
        protected static function getStoredSetting($key)
        {
            $keyPath = self::getDocumentRoot() . $key;
            
            if (file_exists($keyPath))
            {
                $value = file_get_contents($keyPath);
                return $value;
            }
            
            return self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][$key];
        }
        
        /**
         * Saves a setting in a file
         *
         * @param string $key
         * @param string $value
         */
        public static function saveSetting($key, $value)
        {
            $keyPath = self::getDocumentRoot() . $key;
            
            if (file_exists($keyPath))
                unlink($keyPath);
                
            file_put_contents($keyPath, $value);
        }
        
        /**
         * Internally determines if the configuration has been initialized.
         *
         * @return bool
         */
        protected static function hasInitialized()
        {
            return (self::$hasInitialized === true) ? true : false;
        }
        
        /**
         * Gets the active configuration name.
         *
         * @return str
         */
        public static function getActiveConfiguration()
        {
            self::initialize();
            return self::$settingsArray[SystemSettings::KEY_ACTIVE_CONF];
        }
        
        /**
         * Sets the active configuration name for the lifetime of the current request.
         * The active configuration set by this method is not persisted accross requests.
         * To persist the active configuration, define a global called ACTIVE_CONFIGURATION_NAME
         *
         * @param str One of the CONFNAME-prefixed constants defined in this class.
         * 
         */
        public static function setActiveConfiguration($value)
        {
            self::initialize();
            self::$settingsArray[SystemSettings::KEY_ACTIVE_CONF] = $value;
        }
        
        /**
         * Returns a setting value according to the current configuration. If the keyName
         * entry is not found in the current active configuration, it will be obtained from the
         * 'default' configuration. If the keyName is not found under this configuration either,
         * this method will throw an exception.
         * 
         * @param str $keyName one of the 'KEY_' - prefixed constants defined within this class.
         * @return mixed
         */
        public static function getSetting($keyName)
        {
            self::initialize();
            
            // Try to find the item in the active configuration
            if (array_key_exists($keyName, self::$settingsArray[self::getActiveConfiguration()]))
            {
                return self::$settingsArray[self::getActiveConfiguration()][$keyName];
            }
            
            // Try to find the item in the default configuration
            elseif (array_key_exists($keyName, self::$settingsArray[SystemSettings::CONFNAME_DEFAULT]))
            {
                return self::$settingsArray[SystemSettings::CONFNAME_DEFAULT][$keyName];
            }
            
            // Throw the exception
            else
            {
                throw new GrandprixException(GrandprixException::EX_KEYNOTFOUND, 'keyName = ' . $keyName); 
            }
        }
        
        /**
         * Gets the absolute, real path from which the server takes documents.
         * If the constant DOCUMENT_ROOT is not defined, the method tries to obtain the path automatically.
         * Paths returned always have a trailing slash '/'.
         *
         * @return str
         */
        public static function getDocumentRoot()
        {
            if (defined('DOCUMENT_ROOT') === true)
            {
                if (strrchr(DOCUMENT_ROOT, '/') == '/' || strrchr(DOCUMENT_ROOT, '\\') == '\\') return str_replace('\\', '/', DOCUMENT_ROOT);
                else return str_replace('\\', '/', DOCUMENT_ROOT) . '/';
            }
            
            // Get local and absolute paths
            $localPathInfo = pathinfo(getenv("SCRIPT_NAME"));
            $localPath = $localPathInfo['dirname'];
            
            $absolutePath = realpath('.');
            
            // a fix for Windows slashes. Windows 2k and above accept forward slashes
            $absolutePath = str_replace("\\", "/", $absolutePath);
            
            // To lower case strings
            $localPath = strtolower($localPath);
            $absolutePath = strtolower($absolutePath);
            
            // Cut off the last part of the script's path, matching the absolute path
            $documentRoot = substr($absolutePath, 0, strpos($absolutePath, $localPath));
            
            if ($documentRoot != '/') $documentRoot = $documentRoot . '/';
            return $documentRoot;
        }
    }
?>