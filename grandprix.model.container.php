<?php
    require_once "grandprix.model.base.php";
    
    /**
     * Represents the standard web Form model implementation.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class Form extends ContainerModelBase
    {
        protected $errorMessage;
    
        /**
         * Creates a new instance of this class
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
        }
        
        /**
         * Adds a button to the buttonContainer subcontainer
         *
         * @param ButtonModelBase $button
         */
        public function addButton(&$button)
        {
           $this->getChildren()->addControl($button);
        }
    
        /**
         * Gets a button, given its name by searching for it recursively.
         *
         * @param str $buttonName
         * @return ButtonModelBase
         */
        public function getButton($buttonName)
        {
           return $this->getChildren()->getControl($buttonName, true, 'ButtonModelBase');
        }
    
        /**
         * Gets all button names within the form
         * @param bool $searchRecursive
         * @return array
         */
        public function getButtonNames($searchRecursive = true)
        {
            return $this->getChildren()->getControlNames($searchRecursive, 'ButtonModelBase');
        }
    
        /**
         * Determines if a button control exists, given its name
         *
         * @param str $buttonName
         * @return bool
         */
        public function buttonExists($buttonName)
        {
            return $this->getChildren()->controlExists($buttonName, 'ButtonModelBase');
        }
    
        /**
         * Validates all the IValidatable controls within this form.
         *
         * @return bool
         */
        public function validate()
        {
            $result = true;

            $fieldNames = $this->getChildren()->getControlNames(true, 'IValidatable');
            
            foreach($fieldNames as $fieldName)
            {
                if ($this->getChildren()->getControl($fieldName, true)->validate() === false)
                    $result = false;
            }
            
            if ($result === true)
                $this->setErrorMessage('');
            else
                $this->setErrorMessage("Debe corregir los errores antes de continuar.");
     
            return $result;
        }
    
        /**
         * Binds all IBindingTargetMember controls within the form to the given data source.
         * Also, sets IEventTrigger controls by looking for the following key pattern in the collection
         * IEventTrigger->getEventName() . '_eventValue'
         *
         * @param array $dataSource
         */
        public function dataBind(&$dataSource)
        {
            $theData = $dataSource;
            if (is_object($theData)) $theData = get_object_vars($theData);
            elseif (is_array($theData) == false) return;
            
            // First, update the controls that are member-wise bindable
            $controlNames = $this->getChildren()->getControlNames(true, 'IBindingTargetMember');
            
            foreach($controlNames as $controlName)
            {
                $control = $this->getChildren()->getControl($controlName, true);
    
                if ($control->getIsBindable() && key_exists($control->getBindingMemberName(), $theData))
                   $control->setValue($theData[$control->getBindingMemberName()]);
            }
     
            // Now, bind the event values in the model
            $eventTriggerControlNames = $this->getChildren()->getEventTriggerControlNames();
            
            foreach($eventTriggerControlNames as $controlName)
            {
                $control = $this->getChildren()->getControl($controlName, true);
                $eventValueKey = 'eventValue_' . $control->getName() . "_" . $control->getEventName();
      
                if (key_exists($eventValueKey, $theData))
                   $control->setEventValue($theData[$eventValueKey]);
            }
        }
    }
    
    /**
     * Represents the data details web Form model implementation.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class DetailsForm extends Form
    {
        private $gridModelName;
        
        /**
         * Creates a new instance of this class
         * 
         * @param string $name
         * @param string $caption
         * @param string $gridModelName
         */
        public function __construct($name, $caption, $gridModelName)
        {
            parent::__construct($name, $caption);
            
            $this->gridModelName = $gridModelName;
        }
        
        public function getGridModelName()
        {
            return $this->gridModelName;
        }
    }
    
    /**
     * Represents the standard web TabView model implementation.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class TabView extends ContainerModelBase
    {
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
        }
         
        /**
         * Adds a tab
         *
         * @param Tab $tab
         */
        public function addTab(&$tab)
        {
            $this->addContainer($tab);
        }
    }
    
    /**
     * Represents the standard web AccordionForm model implementation.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class AccordionForm extends Form
    {
        public function addContainer(&$container)
        {
            parent::addContainer($container);

            if (count($this->getChildren()->getControlNames($searchRecursive, 'AccordionItem')) == 1)
                $container->setCurrent(true);
        }
        
        public function setCurrentItem($name)
        {
            $items = $this->getChildren()->getControlNames($searchRecursive, 'AccordionItem');
            
            foreach($items as $item)
            {
                if ($name == $item)
                    $this->getChildren()->getControl($item)->setCurrent(true);
                else
                    $this->getChildren()->getControl($item)->setCurrent(false);
            }
        }
    }
    
    /**
     * Represents the standard web WizardForm model implementation.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class WizardForm extends Form
    {
        private $currentPage = 0;
        
        /**
         * Gets all pages names
         *
         * @return array
         */
        private function getPages()
        {
           return $this->getChildren()->getControlNames(true, 'WizardPage');
        }
        
        /**
         * Binds all IBindingTargetMember controls within the form to the given data source.
         * Also, sets IEventTrigger controls by looking for the following key pattern in the collection
         * IEventTrigger->getEventName() . '_eventValue'
         *
         * @param array $dataSource
         *
         */
        public function dataBind(&$dataSource)
        {
            parent::dataBind($dataSource);
            
            $eventValueKey = 'wizard_' . self::getName() . '_page';
     
            if (key_exists($eventValueKey, $dataSource))
               self::setCurrentPage($dataSource[$eventValueKey]);
            
            foreach(self::getPages() as $pageName)
            {
                $page = $this->getContainer($pageName);
                $eventValueKey = 'formWizardPage_' . $page->getName() . '_lock';
    
                if (key_exists($eventValueKey, $dataSource))
                    $page->setLocked($dataSource[$eventValueKey]);
            }
        }
        
        /**
         * Lock current page
         *
         */
        public function lockCurrentPage()
        {
            $pages = self::getPages();
            
            $this->getContainer($pages[$this->currentPage])->setLocked(true);
        }
        
        /**
         * Returns true if current page is the first one
         *
         * @return bool
         */
        public function isFirstPage()
        {
            return ($this->currentPage == 0);
        }
        
        /**
         * Returns true if current page is the last one
         *
         * @return bool
         */
        public function isLastPage()
        {
            return ($this->currentPage + 1 == count(self::getPages()));
        }
        
        /**
         * Gets current page
         *
         * @return int
         */
        public function getCurrentPage()
        {
            return $this->currentPage;
        }
        
        /**
         * Sets current page
         *
         * @param int $page
         */
        public function setCurrentPage($page)
        {
            $this->currentPage = $page;
        }
        
        /**
         * Moves to next page
         *
         */
        public function nextPage()
        {
            if (self::isLastPage() === false)
                self::setCurrentPage($this->currentPage + 1);
        }
        
        /**
         * Moves to previous page
         *
         */
        public function previousPage()
        {
            if (self::isFirstPage() === false)
                self::setCurrentPage($this->currentPage - 1);
        }
        
        /**
         * Validates all the IValidatable controls within this form.
         *
         * @return bool
         */
        public function validate()
        {
            $result = true;
            
            $pages = self::getPages();
            
            $fieldNames = $this->getContainer($pages[$this->currentPage])->getChildren()->getControlNames(true, 'IValidatable');
            
            foreach($fieldNames as $fieldName)
            {
                if($this->getChildren()->getControl($fieldName, true)->validate() === false)
                    $result = false;
            }
     
            if ($result === true)
                $this->setErrorMessage('');
            else
                $this->setErrorMessage("Debe corregir los errores antes de continuar.");
     
            return $result;
        }
    }
    
    /**
     * Represents a tab container.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class Tab extends ContainerModelBase implements IValidatable 
    {
        private $selected;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         * @param bool $selected
         */
        public function __construct($name, $caption, $selected = false)
        {
            parent::__construct($name, $caption);
            
            $this->selected = $selected;
        }
        
        public function validate()
        {
            $result = true;
         
            $fieldNames = $this->getChildren()->getControlNames(true, 'IValidatable');
            
            foreach($fieldNames as $fieldName)
            {
                if($this->getChildren()->getControl($fieldName, true)->validate() === false)
                    $result = false;
            }
     
            if ($result === true)
                $this->setErrorMessage('');
            else
                $this->setErrorMessage("You must correct the errors in the marked fields in order to continue.");
     
            return $result;
        }
        
        /**
         * Gets whether the tab is selected.
         *
         * @return bool
         */
        public function getIsSelected()
        {
          return $this->selected;
        }
        
        /**
         * Sets whether the tab is selected.
         *
         * @param bool $value
         */
        public function setIsSelected($value)
        {
          $this->selected = $value;
        }
    }
    
    /**
     * Represents a action bar for Form Section
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class FormSectionActionBar extends ContainerModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption = '')
        {
            parent::__construct($name, $caption);
        }
        
        /**
         * Adds a button to the buttonContainer subcontainer
         *
         * @param ButtonModelBase $button
         */
        public function addButton(&$button)
        {
           $this->getChildren()->addControl($button);
        }
     
        /**
         * Gets a button, given its name by searching for it recursively.
         *
         * @param str $buttonName
         * @return ButtonModelBase
         */
        public function getButton($buttonName)
        {
           return $this->getChildren()->getControl($buttonName, true, 'ButtonModelBase');
        }
    }
    
    /**
     * Represents a control container with a caption within a Form.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class FormSection extends ContainerModelBase
    {
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
        }
        
        public function validate()
        {
            $result = true;
            $fieldNames = $this->getChildren()->getControlNames(true, 'IValidatable');
            
            foreach($fieldNames as $fieldName)
            {
               if($this->getChildren()->getControl($fieldName, true)->validate() === false)
                  $result = false;
            }
     
            if ($result === true)
                $this->setErrorMessage('');
            else
                $this->setErrorMessage("Debe corregir los errores antes de continuar.");
     
            return $result;
        }
    }
    
    /**
     * Represents a control container with a caption within a Accordion.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class AccordionItem extends ContainerModelBase
    {
        private $isCurrent;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
        }
        
        public function validate()
        {
            $result = true;
         
            $fieldNames = $this->getChildren()->getControlNames(true, 'IValidatable');
            
            foreach($fieldNames as $fieldName)
            {
                if($this->getChildren()->getControl($fieldName, true)->validate() === false)
                    $result = false;
            }
     
            if ($result === true)
                $this->setErrorMessage('');
            else
                $this->setErrorMessage("Debe corregir los errores antes de continuar.");
     
            return $result;
        }
        
        /**
         * Sets if its the current item
         *
         * @param bool $value
         */
        public function setCurrent($value)
        {
            $this->isCurrent = $value;
        }
        
        public function getCurrent()
        {
            return $this->isCurrent;
        }
    }
    
    /**
     * Represents a control container page for wizard forms.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class WizardPage extends ContainerModelBase
    {
        protected $locked = false;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
        }
        
        /**
         * Sets is locked
         *
         * @param bool $value
         */
        public function setLocked($value)
        {
           $this->locked = $value;
        }
        
        /**
         * Returns TRUE if is locked
         *
         * @return bool
         */
        public function getLocked()
        {
           return $this->locked;
        }
    }
    
    /**
     * Represents a control container page for trees.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class Tree extends ContainerModelBase
    {
        private $dataItems;
        private $currentItem;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            
            $this->dataItems = array();
            
            if (isset($_REQUEST['tree_' . $this->getName() . '_selected']))
                $this->currentItem = $_REQUEST['tree_' . $this->getName() . '_selected'];
        }
        
        /**
         * Sets current item
         *
         * @param string $value
         */
        public function setCurrentItem($value)
        {
            $this->currentItem = $value;
        }
        
        /**
         * Gets current item
         *
         * @return string
         */
        public function getCurrentItem()
        {
            return $this->currentItem;
        }
        
        /**
         * Binds tree's nodes
         *
         * @param array $dataSource
         */
        public function dataBind(&$dataSource)
        {
            $this->dataItems = $dataSource;
        }
        
        public function getRootNode()
        {
            return $this->dataItems;
        }
    }
    
    class Gallery extends ContainerModelBase
    {
        protected $isDeletable;
        protected $isSelectable;
        
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->isDeletable = false;
            $this->isSelectable = false;
            $this->getChildren()->addControl(new PersistorField($name . 'Persistor', $_REQUEST[$name . 'Persistor']));
        }
        
        public function getImagesNames()
        {
            return $this->getChildren()->getControlNames(true, 'ImageField');
        }
        
        public function setIsDeletable($value)
        {
            $this->isDeletable = $value;
        }
        
        public function getIsDeletable()
        {
            return $this->isDeletable;
        }
        
        public function setIsSelectable($value)
        {
            $this->isSelectable = $value;
        }
        
        public function getIsSelectable()
        {
            return $this->isSelectable;
        }
        
        public function getValue()
        {
            return explode('|', $this->getChildren()->getControl($this->name . 'Persistor')->getValue());
        }

        public function setValue($value)
        {
            if (is_array($value)) $value = implode('|', $value);
            
            $this->getChildren()->getControl($this->name . 'Persistor')->setValue($value);
        }
    }
?>