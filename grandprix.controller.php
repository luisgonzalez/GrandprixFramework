<?php
    /**
     * Represents an event that was raised by an IEventTrigger object
     *
     * @package Grandprix
     * @subpackage Controller
     */
    class ControllerEvent 
    {
        /**
         * @var IEventTrigger
         */
        private $source;
        private $value;
  
        /**
         * Creates a new instance of this class
         *
         * @param IEventTrigger $source
         * @param str $value
         */
        public function __construct(&$source, $value)
        {
            $this->source = $source;
            $this->value = $value;
        }
  
        /**
         * Gets the name of the event
         *
         * @return str
         */
        public function getName()
        {
            return $this->source->getEventName();
        }
  
        /**
         * Gets the value of the event
         *
         * @return str
         */
        public function getValue()
        {
            return $this->value;
        }
        
        /**
         * Get the object, by reference, from which the vent was triggered
         *
         * @return IEventTrigger
         */
        public function &getSource()
        {
            return $this->source;
        }
    }
 
    /**
     * Defines the main event-handling controller
     * For an event to be fired, two conditions must be met:
     * 1. IModel->getName() is a key in HttpContext->getRequestVars()
     * 2. eventValue_{IModel->getName}_{IEventTrigger->getEventName()} is a key in HttpContext->getRequestVars() and its value is not an empty string
     * 
     * For an event to be handled, use the registerEventHandler() method and define a function with the following signature:
     * function on{EventName}(IModel &$sender, ControllerEvent &$eventArgs)
     * 
     * @package Grandprix
     * @subpackage Controller
     */
    class Controller
    {
        static private $eventHandlers;
  
        /**
         * Registers a callback function to handle an event.
         * The handler's signature must take a ControllerEvent by reference.
         * For example: function onButton_Submit(FormModel &$controller, ControllerEvent &$event)
         *
         * @param str $eventName The name of the event to register
         * @param callback $callback A standard PHP callback to a function that handles the event.
         */
        static public function registerEventHandler($eventName, $callback)
        {
            if (self::$eventHandlers === null) self::$eventHandlers = array();
            
            self::$eventHandlers[$eventName] = $callback;
        }
  
        /**
         * Calls the registered event handlers.
         * To register an event handler callback, use the registerEventHandler method.
         * 
         * @param IModel $model
         * @return int The number of handled events
         */
        static public function handleEvents(&$model)
        {
            if (self::$eventHandlers == null || count(self::$eventHandlers) == 0)
               return 0;
            
            $events = self::getEvents($model);
            
            $handledEvents = array();
            foreach ($events as $event)
            {
                if (key_exists($event->getName(), self::$eventHandlers) === true)
                {
                    $modelReference = &$model;
                    $eventReference = &$event;
                    call_user_func(self::$eventHandlers[$event->getName()], $modelReference, $eventReference);
                    
                    $handledEvents[] = $event->getName();
                    unset(self::$eventHandlers[$event->getName()]);
                }
            }
             
            return $handledEvents;
        }
        
        /**
         * Determines whether at least 1 event was triggered.
         * This is a simple way to determine if the specified model has posted back.
         *
         * @param IModel $model
         * @return bool
         */
        static public function hasEvents(&$model)
        {
            return (count(self::getEvents($model)) > 0) ? true : false;
        }
        
        /**
         * Returns an array of ControllerEvent
         *
         * @param IModel $model
         * @return array
         */
        static public function &getEvents(&$model)
        {
            $events = array();
            $eventTriggerControls = self::getEventTriggeringModels($model);
            
            foreach($eventTriggerControls as $controlName=>$control)
            {
                $eventValueKey = 'eventValue_' . $control->getName() . '_' . $control->getEventName();
                
                if (key_exists($control->getName(), $_REQUEST) && key_exists($eventValueKey, $_REQUEST))
                {
                   $event = new ControllerEvent($control, $_REQUEST[$eventValueKey]);
                   $events[] = $event;
                }
            }
            
            return $events;
        }
        
        /**
         * Internal, recursive function to scan for IEventTrigger objects inside of $definedVars
         *
         * @param array[mixed] $definedVars
         *
         */
        static private function &getEventTriggeringModels(&$definedVars)
        {
            $ignoreList=array("GLOBALS", "_REQUEST", "_SERVER",
                        "_POST", "_GET", "_COOKIES",
                        "_SESSION", "_COOKIE", "_FILES",
                        "HTTP_POST_VARS", "HTTP_GET_VARS", "HTTP_COOKIE_VARS",
                        "HTTP_SERVER_VARS", "HTTP_ENV_VARS", "HTTP_SESSION_VARS",
                        "_ENV", "PHPSESSID", "SESS_DBUSER", "SESS_DBPASS",
                        "HTTP_COOKIE");
              
            if (!isset($result)) $result = array();
            
            while (list($key, $value) = each($definedVars))
            {
                $ignoreItem = in_array("" . $key, $ignoreList);
                if ($ignoreItem) continue;
                
                if (is_object($value))
                {
                    $implementingInterfaces = class_implements($value);
                    
                    if (key_exists("IEventTrigger", $implementingInterfaces) === true)
                       $result[] = $value;
            
                    $subresult = self::getEventTriggeringModels($value);
                    
                    if (count($subresult) > 0)
                       $result = array_merge($result, $subresult);
                }
                elseif (is_array($value))
                {
                    $subresult = self::getEventTriggeringModels($value);
                    
                    if (count($subresult) > 0)
                       $result = array_merge($result, $subresult);
                }
            }
            
            reset($definedVars);
   
            return $result;
        }
  
        /**
         * Determines if the model has caused a postback through its view.
         * 
         * @param IModel $model
         * @return bool
         */
        static public function isPostBack(&$model)
        {
            return key_exists('postBackFlag_' . $model->getName(), $_REQUEST);
        }
    }
    
    class SessionWrapper
    {
        private static function &getSession()
        {
            return $_SESSION[APPLICATION_NAME];
        }
        
        public static function &getValue($key)
        {
            $session =& self::getSession();
            return $session[$key];
        }
        
        public static function setValue($key, &$value)
        {
            $session =& self::getSession();
            $session[$key] = $value;
        }
        
        public static function unsetValue($key)
        {
            $session =& self::getSession();
            unset($session[$key]);
        }
        
        public static function exists($key)
        {
            $session =& self::getSession();
            return is_array($session) && array_key_exists($key, $session);
        }
        
        public static function clear()
        {
            $session =& self::getSession();
            $session = array();
        }
        
        private static function &getCurrentPage()
        {
            return self::getValue($_SERVER['PHP_SELF']);
        }
        
        public static function setCurrentPageValue($key, $value)
        {
            $session =& self::getCurrentPage();
            $session[$key] = $value;
        }

        public static function &getCurrentPageValue($key)
        {
            $session =& self::getCurrentPage();
            return $session[$key];
        }
        
        public static function existsCurrentPage($key)
        {
            $currentPage =& self::getCurrentPage(); 
            return is_array($currentPage) && array_key_exists($key, $currentPage);
        }

    }
?>