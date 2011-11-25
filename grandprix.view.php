<?php
    require_once("grandprix.html.php");
    
    /**
     * Defines the methods necessary for a class to be renderable.
     *
     * @package Grandprix
     * @subpackage View
     */
    interface IRenderable
    {
        /**
         * Renders the associated object
         */
        public function render();
        
        public function &getModel();
    }

    /**
     * Provides a standard HTML renderer.
     * Controls, fields and containers are rendered by using callbacks.
     * Add, remove or modify callbacks using the renderCallbacks array
     *
     * @package Grandprix
     * @subpackage View
     */
    abstract class HtmlView implements IRenderable
    {
        /**
         * @var IModel
         */
        protected $model;
        /**
         * @var array
         */
        protected $renderCallbacks;
        protected $cssClass;
        protected $masterCssClass;
        protected $showFrame;
        protected $frameWidth;
        protected $frameHeight;
        protected $isAsync;
        
        /**
         * Creates a new instance of this class
         *
         * @param IModel $model
         */
        public function __construct(&$model)
        {
            $this->model = $model;
            $this->masterCssClass = 'view';
            $this->showFrame = true;
            $this->frameWidth = '85%';
            $this->isAsync = true;
            
            // Instantiate the callback collection
            $this->renderCallbacks = array();
        }
        
        public static function registerCommonDependencies()
        {
            $yuiCssPath = 'lib/yui/sam/yui.css';
            $dtCSSPath = 'lib/yui/sam/datatable.css';
            $yuiCalendarCssPath= 'lib/yui/sam/calendar.css';
            
            $yuiPath = 'lib/yui/utilities.js';
            $yuiMenuPath = 'lib/yui/menu-min.js';
            $yuiContainerPath = 'lib/yui/container-min.js';
            $yuiDatasourcePath = 'lib/yui/datasource-min.js';
            $yuiDatatablePath = 'lib/yui/datatable-min.js';
            $yuiCalendarPath = 'lib/yui/calendar-min.js';
            $yuiButtonPath = 'lib/yui/button-min.js';
            $yuiSWF = 'lib/yui/swf-min.js';
            $yuiUploaderPath = 'lib/yui/uploader-min.js';
            $yuiCharts = 'lib/yui/charts-min.js';
            $jsonPath = 'lib/yui/json-min.js';

            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'DtCSS', $dtCSSPath);
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'YUICSS', $yuiCssPath);
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'CalendarCSS', $yuiCalendarCssPath);
            
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUI', $yuiPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIContainer', $yuiContainerPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIDs', $yuiDatasourcePath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIDt', $yuiDatatablePath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUICalendar', $yuiCalendarPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIButton', $yuiButtonPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUISWF', $yuiSWF);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIMenu', $yuiMenuPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIUploader', $yuiUploaderPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUICharts', $yuiCharts);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'JSON', $jsonPath);
        }
        
        public function getIsAsync()
        {
            return $this->isAsync;
        }
        
        public function setIsAsync($value)
        {
            $this->isAsync = $value;
        }
        
        public function getShowFrame()
        {
            return $this->showFrame;
        }
        
        public function setShowFrame($value)
        {
            $this->showFrame = $value;
        }
        
        /**
         * Determines the outer frame's width as a CSS style attribute
         * This property has no effect if showFrame is set to false.
         *
         * @return string
         */
        public function getFrameWidth()
        {
            return $this->frameWidth;
        }
    
        /**
         * Determines the outer frame's width as a CSS style attribute
         * This property has no effect if showFrame is set to false.
         *
         * @param $value string
         */
        public function setFrameWidth($value)
        {
            $this->frameWidth = $value;
        }
        
        /**
         * Determines the outer frame's height as a CSS style attribute
         * This property has no effect if showFrame is set to false.
         *
         * @return string
         */
        public function getFrameHeight()
        {
            return $this->frameHeight;
        }
    
        /**
         * Determines the outer frame's height as a CSS style attribute
         * This property has no effect if showFrame is set to false.
         *
         * @param $value string
         */
        public function setFrameHeight($value)
        {
            $this->frameHeight = $value;
        }
        
        /**
         * Renders the form and each control within it.
         *
         */
        public function render()
        {
            $tw = HtmlWriter::getInstance();
            
            if ($this->getShowFrame() == true)
            {
                $tw->openDiv();
                $tw->addAttribute('class', $this->getMasterCssClass() . '-frame');
                $tw->addAttribute('style', 'width: ' . $this->frameWidth . '; height: ' . $this->frameHeight . ';');
            }
            
            call_user_func_array($this->renderCallbacks[get_class($this->model)], array(&$this->model, &$this));
            
            if ($this->getShowFrame() == true)
            {
                $tw->closeDiv();
            }
        }
     
        /**
         * Gets the Model that this instance renders
         *
         * @return IModel
         */
        public function &getModel()
        {
            return $this->model;
        }
     
        /**
         * Returns the keyed collection containing the callback methods used to render each model type.
         * The keys are the class names of the model types. The values are the callbacks themselves.
         *
         * @return array
         */
        public function &getRenderCallbacks()
        {
            return $this->renderCallbacks;
        }
        
        /**
         * Gets the Css class prefix to use for rendering.
         * 
         * @return str
         */
        public function getMasterCssClass()
        {
            return $this->masterCssClass;
        }
        
        /**
         * Sets the Css class prefix to use for rendering.
         * 
         * @param str $value
         */
        public function setMasterCssClass($value)
        {
            $this->masterCssClass = $value;
            self::registerDependencies();
        }
        
        /**
         * Gets the Css class prefix to use for rendering.
         * 
         * @return str
         */
        public function getCssClass()
        {
            return $this->cssClass;
        }
        
        /**
         * Sets the Css class prefix to use for rendering.
         * 
         * @param str $value
         */
        public function setCssClass($value)
        {
            $this->cssClass = $value;
            self::registerDependencies();
        }
        
        public static function renderPostBackTrigger($model)
        {
            $value = 'form_' . $model->getName();
            
            if ($model instanceof DataTable)
                $value = 'datatable_' . $model->getName();
                
            $tw = HtmlWriter::getInstance();
            
            $tw->openInput();
            
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('class', 'postBackTrigger');
            $tw->addAttribute('value', $value);
            
            $tw->closeInput();
        }
        
        public static function renderRedirector($url)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openInput();
            
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', 'redirectUrl');
            $tw->addAttribute('value', $url);
            
            $tw->closeInput();
            exit;
        }
    }
    
    /**
     * Represents an HTML dependency usually injected in the <head> section of the document.
     * Use the HtmlViewManager to register dependencies.
     *
     * @package Grandprix
     * @subpackage View
     */
    class HtmlDependency
    {
        const JS_FILE = 0;
        const JS_BLOCK = 1;
        const CSS_FILE = 2;
        const CSS_BLOCK = 3;
    
        public $Type;
        public $Content;
    
        /**
         * Creates a new instance of this class.
         *
         * @param int $type The type constant defined within this class.
         * @param str $content The absolute virtual path to the dependency or the text contents of it.
         */
        public function __construct($type, &$content)
        {
            $this->Type = $type;
            $this->Content = $content;
        }
    }

    /**
     * Registers and renders page resource dependencies such as css stylesheets, and javascript code.
     * The render method should be called inside the <head> section of the HTML.
     *
     * @package Grandprix
     * @subpackage View
     */
    class HtmlViewManager
    {
        protected static $dependencies;
        protected static $hasRendered;
    
        /**
         * Registers a dependency.
         *
         * @param $type The dependency type constant defined in the HtmlDependency class.
         * @param $id The unique identifier of the dependency.
         * @param $content The absolute virtual path or the string content of the dependency.
         */
        public static function registerDependency($type, $id, &$content)
        {
            self::$dependencies[$id] = new HtmlDependency($type, $content);
        }
    
        /**
         * Gets the underlying dependency collection.
         *
         * @return array
         */
        public static function &getDependencyCollection()
        {
            if (is_null(self::$dependencies) == true)
                self::$dependencies = array();
                
            return self::$dependencies;
        }
    
        /**
         * Gets a collection of dependencies
         *
         * @param int $type
         * @return array
         */
        public static function &getDependencies($type)
        {
            $filteredCollection = array();
    
            foreach(self::getDependencyCollection() as $key => $dep)
            {
                if ($dep->Type === $type)
                    $filteredCollection[$key] = $dep;
            }
    
            return $filteredCollection;
        }
    
        /**
         * Gets a dependency by id.
         *
         * @return HtmlDependency
         */
        public static function getDependency($id)
        {
            if (key_exists($id, self::getDependencyCollection()))
                return self::$dependencies[$id];
                
            return null;
        }
    
        /**
         * Determines if the dependencies have been renderered.
         * Useful for views to check whether their own dependencies have been rendered.
         *
         * @return bool
         */
        public static function hasRendered()
        {
            return (self::$hasRendered === true) ? true : false;
        }
    
        /**
         * Gets the depth of the current script in the directories tree
         * Useful for views to check how many directories back will the dependencies be
         *
         * @return int
         */
        public static function getDepth()
        {
            /*$urlPath = parse_url(Settings::getSetting(Settings::KEY_URL_FORMS), PHP_URL_PATH);
            $filepath = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
            $relativePath = str_ireplace($urlPath, "", $filepath);
            $count = preg_replace("/[^\/]/","",$relativePath);*/
            return 1;
        }
        
        /**
         * Renders the registered HTML resources.
         * This method should be called statically inside the <head> section of the HTML document.
         *
         */
        public static function render()
        {
            $tw = HtmlWriter::getInstance();
            $depth = self::getDepth();
            
            // Css files
            $deps = self::getDependencies(HtmlDependency::CSS_FILE);
            
            foreach ($deps as $dep)
            {
                $tw->openLink();
                $tw->addAttribute('type', 'text/css');
                $tw->addAttribute('rel', 'stylesheet');
                $tw->addAttribute('href', str_repeat("../", $depth) . $dep->Content);
                $tw->closeLink();
            }
    
            // Css blocks
            $deps = self::getDependencies(HtmlDependency::CSS_BLOCK);
            
            if (count($deps) > 0)
            {
                $tw->openStyle();
                $tw->addAttribute('type', 'text/css');
                
                foreach ($deps as $dep)
                    $tw->writeRaw($dep->Content . "\r\n");
                
                $tw->closeStyle(true);
            }
            
            // Javascript files
            $deps = self::getDependencies(HtmlDependency::JS_FILE);
            
            foreach ($deps as $dep)
            {
                $tw->openScript();
                $tw->addAttribute('type', 'text/javascript');
                $tw->addAttribute('language', 'javascript');
                $tw->addAttribute('src', str_repeat("../", $depth) . $dep->Content);
                $tw->closeScript(true);
            }
    
            // Javascript blocks
            $deps = self::getDependencies(HtmlDependency::JS_BLOCK);
            
            if (count($deps) > 0)
            {
                $tw->openScript();
                $tw->addAttribute('type', 'text/javascript');
                $tw->addAttribute('language', 'javascript');
                
                foreach ($deps as $dep)
                    $tw->writeRaw($dep->Content . "\r\n");
                
                $tw->closeScript(true);
            }
            
            self::$hasRendered = true;
        }
    }
?>