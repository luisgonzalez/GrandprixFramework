<?php
    /**
     * Widget Base Class
     *
     * @package Grandprix
     * @subpackage Layout
     */
    abstract class WidgetBase extends ControlModelBase implements IRenderable
    {
        /**
         * @var IRenderable
         */
        protected $view;
        /**
         * @var IModel
         */
        protected $model;
        
        /**
         * @var array
         */
        protected $dependentModels = array();

        /**
         * @var string
         */
        protected $handledEvents = array();
        
        /**
         * Creates a new widget instance
         *
         * @param string $name
         */
        public function __construct($name, $dependentModels = array())
        {
            parent::__construct($name, true);
            
            if (is_array($dependentModels) === false)
                throw new GrandprixException(GrandprixException::EX_INVALIDPARAMMETHOD, "dependentModels must be an array of IModels");

            foreach($dependentModels as $model)
                $this->dependentModels[$model->getName()] = $model;
        }
        
        /**
         * Default handler for postback action
         *
         */
        public function handlePostBack()
        {
            if (Controller::isPostBack($this->model))
            {
                if (in_array('DataSourceControlModelBase', class_parents($this->model)) === false)
                    $this->model->dataBind($_REQUEST);

                $this->handledEvents = Controller::handleEvents($this->model);
                
                if ($this->view->getIsAsync())
                {
                    $this->render();
                    exit();
                }
            }
        }
        
        /**
         * Renders widget
         *
         */
        public function render()
        {
            $this->onBeforeRender();

            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'view-widget');
            
            $this->view->render();
            
            $tw->closeDiv();
        }
          
        /**
         * Renders the postback trigger for the dependent models
         */
        public function renderDependentModelsPostBackTrigger()
        {
            foreach ($this->dependentModels as $model)
                HtmlView::renderPostBackTrigger($model);
        }

        /**
         * Gets the IRenderable object for this widget
         * 
         * @return IRenderable
         */
        public function &getView()
        {
            return $this->view;
        }
        
        /**
         * Gets the IModel object for this widget
         * 
         * @return IModel
         */
        public function &getModel()
        {
            return $this->model;
        }
        
        /**
         * Method called before rendering the widget. It is intended to build the components
         * of the widget.
         */
        abstract protected function onBeforeRender();
    }
    
    /**
     * Layout Class
     *
     * @package Grandprix
     * @subpackage Layout
     */
    abstract class Layout implements IRenderable
    {
        protected $name;
        protected $caption;
        protected $widgets;
        protected $frameWidth;
        
        public function __construct($name, $caption)
        {
            $this->name = $name;
            $this->caption = $caption;
            $this->widgets = array();
            $this->frameWidth = '700px';
            
            HtmlFormView::registerDependencies();
            
            $layoutPath = 'lib/javascript/layout.js';
            
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'Layout', $layoutPath);
        }
        
        /**
         * Adds a widget to layout
         *
         * @param IRenderable $widget
         */
        public function addWidget($widget)
        {
            if (in_array("IRenderable", class_implements(get_class($widget))))
                $this->widgets[$widget->getModel()->getName()] = $widget;
            else
                throw new Exception("Widget must implement IRenderable interface");
        }
        
        /**
         * Gets an already added widget given its name
         *
         * @param string $widgetName
         *
         * @return IRenderable
         */
        public function &getWidget($widgetName)
        {
            if(array_key_exists($widgetName, $this->widgets))
                return $this->widgets[$widgetName];

            return null;
        }
        
        public function setCaption($value)
        {
            $this->caption = $value;
        }
        
        public function setName($value)
        {
            $this->name = $value;
        }
        
        public function getName()
        {
            return $this->name;
        }
        
        /**
         * Gets the IModel object for this widget
         * 
         * @return IModel
         */
        public function &getModel()
        {
            return $this->widgets;
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
        
        protected function renderForm()
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openForm();
            $tw->addAttribute('id', 'form_' . $this->getName());
            $tw->addAttribute('name', $this->getName());
            $tw->addAttribute('class', 'form-view');
            $tw->addAttribute('action', $_SERVER['PHP_SELF']);
            $tw->addAttribute('method', 'post');
            $tw->addAttribute('enctype', 'multipart/form-data');
            $tw->addAttribute('type', 'layout');
            
            $postBackIdentifier = 'postBackFlag_' . $this->getName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $postBackIdentifier);
            $tw->addAttribute('name', $postBackIdentifier);
            $tw->addAttribute('value', '1');
            $tw->closeInput();
            
            $tw->closeForm();
        }
        
        public function setCurrentWidget($widget)
        {
            $this->currentWidget = $widget;
        }
        
        public function &getCurrentWidget()
        {
            return $this->currentWidget;
        }
    }
    
    /**
     * Layout Basic Class
     *
     * @package WorshipKitchen
     * @subpackage Layout
     */
    class BasicLayout extends Layout
    {
        public function render()
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'layout-frame');
            $tw->addAttribute('style', 'border-width: 0; width: '.$this->getFrameWidth());
            
            $tw->openDiv();
            $tw->addAttribute('class', 'layout-content-basic');
            
            foreach($this->widgets as $widget)
            {
                $tw->openDiv();
                $tw->addAttribute('class', 'view-item');
                
                $widget->render();
                
                $tw->closeDiv();
            }
            
            $tw->closeDiv();
            
            parent::renderForm();

            $tw->closeDiv();
        }
    }
    
    /**
     * Layout Accordion Class
     *
     * @package Grandprix
     * @subpackage Layout
     */
    class AccordionLayout extends Layout
    {
        public function render()
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'layout-frame');
            $tw->addAttribute('style', 'width:'.$this->getFrameWidth());
            
            $tw->openDiv();
            $tw->addAttribute('class', 'layout-caption');
            $tw->writeContent($this->caption);
            $tw->closeDiv();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'layout-accordion');
            
            foreach($this->widgets as $widget)
            {
                $className = 'view-accordionitem';
                if ($widget->getName() == $this->currentWidgetName)
                    $className .= ' view-accordionitem-selected';
                
                $tw->openDiv();
                $tw->addAttribute('class', 'view-accordionitem');
                    
                $widget->render();
                
                $tw->closeDiv();
            }
            
            $tw->closeDiv();
            
            parent::renderForm();
            
            $tw->closeDiv();
        }
    }
    
    /**
     * Layout TabView Class
     *
     * @package Grandprix
     * @subpackage Layout
     */
    class TabViewLayout extends Layout
    {
        public function render()
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'view-frame');
            $tw->addAttribute('style', 'width: ' . $this->frameWidth . ';');
            
            $tw->openDiv();
            $tw->addAttribute('class', 'view-caption');
            $tw->writeContent($this->caption);
            $tw->closeDiv();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'view-content');
            
            $tw->openDiv();
            $tw->addAttribute('class', 'layout-navset');
            
            $tw->openUl();
            $tw->addAttribute('class', 'yui-nav');
            
            $i = 0;
            foreach ($this->widgets as $index => $widget)
            {
                if ($i++ == 0)
                    $currentWidgetIndex = $index;
                if ($widget === $this->getCurrentWidget())
                {
                    $currentWidgetIndex = $index;
                    break;
                }
            }
            
            foreach ($this->widgets as $index => $widget)
            {
                $tw->openLi();
                
                if ($index == $currentWidgetIndex)
                    $tw->addAttribute('class', 'selected');
                
                $tw->openA();
                $tw->addAttribute('href', '#' . $widget->getName());
                $tw->openEm();
                $tw->writeContent($widget->getModel()->getCaption());
                $tw->closeEm();
                $tw->closeA();
                
                $tw->closeLi();
            }
            
            $tw->closeUl();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'yui-content');
            
            foreach ($this->widgets as $widget) $widget->render();
            
            $tw->closeDiv();
            
            $tw->closeDiv();
            
            parent::renderForm();
            
            $tw->closeDiv();
        }
    }
?>