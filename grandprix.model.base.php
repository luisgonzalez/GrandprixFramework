<?php
    require_once "grandprix.view.php";
    
    /**
     * Defines methods for an object to be able able to raise events in the controller.
     *
     * @package Grandprix
     * @subpackage Model
     */
    interface IEventTrigger
    {
        public function getEventName();
        public function setEventName($value);
        public function getEventValue();
        public function setEventValue($value);
    }
    
    /**
     * Provides a Interface to manage binding targets
     *
     * @package Grandprix
     * @subpackage Model
     */
    interface IBindingTargetMember
    {
        public function getBindingMemberName();
        public function setBindingMemberName($value);
        public function getIsBindable();
        public function setIsBindable($value);
        public function setValue($value);
    }
    
    /**
     * Represents a field inside a form. The form can be an input form or a display form
     *
     * @package Grandprix
     * @subpackage Model
     */
    abstract class ControlModelBase
    {
        protected $visible;
        protected $name;
    
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         */
        protected function __construct($name, $isVisible)
        {
           $this->name = $name;   
           $this->visible = $isVisible;
        }
        
        /**
         * Gets control name
         *
         * @return str
         */
        public function getName()
        {
            return $this->name;
        }
     
        /**
         * Sets whether the field is visible
         *
         * @param bool $value
         */
        public function setVisible($value)
        {
            $this->visible = $value;
        }
     
        /**
         * Gets whether the field is visible
         *
         * @return bool
         */
        public function getVisible()
        {
            return $this->visible;
        }
    }
    
    /**
     * Represents a collection of ControlModel objects (Containers and Fields)
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ControlModelCollection
    {
        protected $controls;
        
        /**
         * Gets control array
         *
         * @return array
         */
        public function getControls()
        {
           return $this->controls;
        }
        
        /**
         * Creates a new instance of this class
         *
         */
        public function __construct()
        {
            $this->controls = array();
        }
     
        /**
         * Adds a control to the collection
         *
         * @param ControlModelBase $control
         */
        public function addControl(&$control)
        {
            if (is_a($control,'ControlModelBase') === true)
            {
                if ($this->controlExists($control->getName(), $this->controls) === false)
                    $this->controls[$control->getName()] = $control;
                else
                    throw new Exception('The control object must have a unique name accross all containers.');
            }
            else
                throw new Exception('The control object must inherit from FieldModelBase');
        }
        
        /**
         * Determines if a control exists, given its name and base type name.
         *
         * @param str $controlName
         * @param str $controlBaseType
         * @return bool
         */
        public function controlExists($controlName, $controlBaseType = 'ControlModelBase')
        {
            return !is_null($this->getControl($controlName,$controlBaseType));
        }
     
        /**
         * Gets the control names given a type name or interface
         *
         * @param bool $searchRecursive
         * @param str $controlBaseType
         * @return array
         */
        public function &getControlNames($searchRecursive, $controlBaseType = 'ControlModelBase')
        {
            $controlNames = array();

            foreach($this->controls as $controlName => $currentControl)
            {
                if (is_a($currentControl, $controlBaseType)) $controlNames[] = $controlName;
                
                if($searchRecursive === true && is_a($currentControl, 'ContainerModelBase')){
                    $addedNames = $currentControl->getChildren()->getControlNames(true, $controlBaseType);
                    $controlNames = array_merge($controlNames, $addedNames);
                }
            }

            return $controlNames;
        }
     
        /**
         * Gets a control given its name and type name.
         *
         * @param str $controlName
         * @param bool $searchRecursive
         * @param str $controlBaseType
         * @return ControlModelBase
         */
        public function getControl($controlName, $searchRecursive = true, $controlBaseType = 'ControlModelBase')
        {
            // Search within the immediate collection
            if(array_key_exists($controlName, $this->controls) === true)
                if(is_a($this->controls[$controlName], $controlBaseType))
                    return $this->controls[$controlName];

            // Search within subcontainers
            if($searchRecursive === true)
            {
                $containerNames = $this->getControlNames(false, 'ContainerModelBase');
                foreach($containerNames as $containerName)
                {
                    $control = $this->getControl($containerName,false,'ContainerModelBase')->getChildren()->getControl($controlName, $searchRecursive, $controlBaseType);
                    if(!is_null($control)) return $control;
                }
            }
            return null;
        }
     
        /**
         * Gets an array of controls that implement the IEventTrigger interface
         *
         * @return array
         */
        public function &getEventTriggerControlNames()
        {
            return $this->getControlNames(true, 'IEventTrigger');
        }
    }
    
    /**
     * Provides a base implementation for a control implementing the IBindingTargetMember interface
     *
     * @package Grandprix
     * @subpackage Model
     */
    abstract class BoundControlModelBase extends ControlModelBase implements IBindingTargetMember
    {
        protected $bindingMemberName;
        protected $isBindable;
        protected $value;
     
        /**
         * Creates a new instance of this class.
         *
         * @param str $name
         * @paran str $value
         * @param bool $isBindable
         */
        protected function __construct($name, $value, $isBindable)
        {
            parent::__construct($name, true);
            $this->value = $value;
            $this->isBindable = $isBindable;
            $this->bindingMemberName = $name;
        }
     
        /**
         * Gets whether the filed is bindable.
         *
         * @return bool
         */
        public function getIsBindable()
        {
            return $this->isBindable;
        }
        
        /**
         * Sets whether the field is bindable.
         *
         * @param bool $value
         */
        public function setIsBindable($value)
        {
            $this->isBindable = $value;
        }
        
        /**
         * Gets the name of the member to bind to in the parent bindingSourceName
         *
         * @return str
         */
        public function getBindingMemberName()
        {
            return $this->bindingMemberName;
        }
        
        /**
         * Sets the name of the member to bind to in the parent bindingSourceName
         * If the value is an empty string, it will automatically set the isBindable property to false.
         * If the value is a non-empty string, it will automatically set the isBindable property to true.
         *
         * @param str $value
         */
        public function setBindingMemberName($value)
        {
            if (trim($value) === '')
            {
                $this->bindingMemberName = '';
                $this->setIsBindable(false);
            }
            else
            {
                $this->bindingMemberName = trim($value);
                $this->setIsBindable(true);
            }
        }
     
        /**
         * Gets the value of the field.
         *
         * @return mixed
         */
        public function &getValue()
        {
            return $this->value;
        }
        
        /**
         * Sets the value of the field.
         *
         * @param mixed $value
         */
        public function setValue($value)
        {
            $this->value = $value;
        }
    }
     
    /**
     * Defines the methods that are necessary for a model to be validatable.
     *
     * @package Grandprix
     * @subpackage Model
     */
    interface IValidatable
    {
        /**
         * Validates this instce.
         *
         * @return bool
         */
        public function validate();
    }
    
    abstract class FieldModelBase extends BoundControlModelBase implements IValidatable
    {
        /**
         * Represents the validator object that will perform the validation
         *
         * @var IValidator
         */
        protected $validator;
        protected $caption;
        protected $isRequired;
        protected $errorMessage;
        protected $helpMessage;
        protected $isReadOnly;
        protected $visible;
    
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         * @param mixed $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        protected function __construct($name, $caption, $value, $isRequired, $helpMessage)
        {
            parent::__construct($name, $value, true);
           
            $this->validator = new BasicFieldValidator();
            $this->isRequired = $isRequired;
            $this->caption = $caption;
            $this->errorMessage = '';
            $this->helpMessage = $helpMessage;
            $this->isReadOnly = false;
            $this->visible = true;
        }
    
        /**
         * Gets whether the filed is read-only.
         *
         * @return bool
         */
        public function getIsReadOnly()
        {
            return $this->isReadOnly;
        }
        
        /**
         * Sets whether the field is read-only.
         *
         * @param bool $value
         */
        public function setIsReadOnly($value)
        {
            $this->isReadOnly = $value;
        }
    
        /**
         * Gets the validator object used to validate the value in the field.
         *
         * @return IValidator
         */
        public function getValidator()
        {
            return $this->validator;
        }
        
        /**
         * Sets the validator object used to validate the value in the field
         *
         * @param IValidator $value
         */
        public function setValidator($value)
        {
            $this->validator = $value;
        }
    
        /**
         * Validates the current value of the model using the associated validator.
         *
         * @return bool
         */
        public function validate()
        {
            if (is_null($this->validator)) return true;
            
            return $this->validator->validate($this, '');
        }
    
        /**
         * Gets whether a value is required for the control.
         *
         * @return bool
         */
        public function getIsRequired()
        {
            return $this->isRequired;
        }
        
        /**
         * Sets whether a value is required for the control.
         *
         * @param bool $value
         */
        public function setIsRequired($value)
        {
            $this->isRequired = $value;
        }
    
        /**
         * Gets the caption for the field
         *
         * @return str
         */
        public function getCaption()
        {
            return $this->caption;
        }
        /**
         * Sets the caption to display for the field.
         *
         * @param str $value
         */
        public function setCaption($value)
        {
            $this->caption = $value;
        }
    
        /**
         * Gets whether the control field has errors.
         *
         * @return bool
         */
        public function hasErrorMessage()
        {
            return  (trim($this->getErrorMessage()) == '') ? false : true;
        }
        
        /**
         * Gets the current error message to display.
         *
         * @return str
         */
        public function getErrorMessage()
        {
            return $this->errorMessage;
        }
        
        /**
         * Sets the current error message to display. If the value is specified as an empty string,
         * the hasErrorMessage property will be set to false. It will be set to true otherwise.
         *
         * @param str $value
         */
        public function setErrorMessage($value)
        {
            $this->errorMessage = $value;
        }
        
        /**
         * Gets the current help message to display.
         *
         * @return str
         */
        public function getHelpMessage()
        {
            return $this->helpMessage;
        }
        
        /**
         * Sets the current help message to display.
         *
         * @param str $value
         */
        public function setHelpMessage($value)
        {
            $this->helpMessage = $value;
        }
        
        public function getIsVisible()
        {
            return $this->visible;
        }
        
        public function setIsVisible($value)
        {
            $this->visible = $value;
        }
    }
    
    /**
     * Represents a container control
     *
     * @package Grandprix
     * @subpackage Model
     */
    abstract class ContainerModelBase extends ControlModelBase
    {
        /**
         * @var ControlModelCollection
         */
        protected $children;
        
        /**
         * Creates a new instance of this class
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
           parent::__construct($name, true);
           
           $this->caption = $caption;
           $this->children = new ControlModelCollection();
           $this->errorMessage = '';
           $this->notificationMessage = '';
        }
        
        /**
         * Gets the caption for the field
         *
         * @return str
         */
        public function getCaption()
        {
           return $this->caption;
        }
        
        /**
         * Sets the caption to display for the field.
         *
         * @param str $value
         */
        public function setCaption($value)
        {
           $this->caption = $value;
        }
        
        /**
         * Gets the child controls within this container
         *
         * @return ControlModelCollection
         */
        public function &getChildren()
        {
           return $this->children;
        }
        
        /**
         * Determines whether the filed name exists by searching recursively.
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param str $fieldName
         * @return bool
         */
        public function fieldExists($fieldName)
        {
           return $this->getChildren()->controlExists($fieldName, 'ContainerModelBase');
        }
        
        /**
         * Determines whether the container name exists by searching recursively.
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param str $containerName
         * @return bool
         */
        public function containerExists($containerName)
        {
           return $this->getChildren()->controlExists($containerName, 'ContainerModelBase');
        }
        
        /**
         * Adds a field model to the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param FieldModelBase $field
         */
        public function addField(&$field)
        {
            if (is_a($field,'FieldModelBase') === true)
                $this->getChildren()->addControl($field);
            else
                throw new Exception('The field object must inherit from FieldModelBase');
        }
        
        /**
         * Gets a field model within the collection.
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param str $fieldName
         * @param bool $searchRecursive
         * @return FieldModelBase
         */
        public function getField($fieldName, $searchRecursive = true)
        {
           return $this->getChildren()->getControl($fieldName, $searchRecursive, 'FieldModelBase');
        }
        
        /**
         * Returns an array of field names within the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param bool $searchRecursive
         * @return array
         */
        public function getFieldNames($searchRecursive)
        {
            return $this->getChildren()->getControlNames($searchRecursive, 'FieldModelBase');
        }
        
        /**
         * Adds a container model to the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param ContainerModelBase $container
         */
        public function addContainer(&$container)
        {
            if (is_a($container,'ContainerModelBase') === true)
                $this->getChildren()->addControl($container);
            else
                throw new Exception('The container object must inherit from ContainerModelBase');
        }
        
        /**
         * Gets a container model within the immediate collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param str $containerName
         * @param bool $searchRecursive
         * @return ContainerModelBase
         */
        public function getContainer($containerName, $searchRecursive = true)
        {
            return $this->getChildren()->getControl($containerName, $searchRecursive, 'ContainerModelBase');
        }
        
        /**
         * Returns an array of container names within the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param bool $searchRecursive
         * @return array
         */
        public function getContainerNames($searchRecursive = true)
        {
            return $this->getChildren()->getControlNames($searchRecursive, 'ContainerModelBase');
        }
        
        /**
         * Gets whether the control field has errors.
         *
         * @return bool
         */
        public function hasErrorMessage()
        {
            return (trim($this->getErrorMessage()) == '') ? false : true;
        }
        
        /**
         * Gets the current error message to display.
         *
         * @return str
         */
        public function getErrorMessage()
        {
            return $this->errorMessage;
        }
        
        /**
         * Sets the current error message to display. If the value is specified as an empty string,
         * the HasErrors property will be set to false. It will be set to true otherwise.
         *
         * @param str $value
         */
        public function setErrorMessage($value)
        {
           $this->errorMessage = $value;
        }
        
        /**
         * Gets whether the control field has a notification.
         *
         * @return bool
         */
        public function hasNotificationMessage()
        {
            return (trim($this->getNotificationMessage()) == '') ? false : true;
        }
        
        /**
         * Gets the current error message to display.
         *
         * @return str
         */
        public function getNotificationMessage()
        {
           return $this->notificationMessage;
        }
        
        /**
         * Sets the current notification message to display. If the value is specified as an empty string,
         * the hasNotificationMessage property will be set to false. It will be set to true otherwise.
         *
         * @param str $value
         */
        public function setNotificationMessage($value)
        {
            $this->notificationMessage = $value;
        }
    }
    
    /**
     * Represents a compound field control
     *
     * @package Grandprix
     * @subpackage Model
     */
    abstract class CompoundFieldModelBase extends ContainerModelBase implements IValidatable
    {
        protected $isRequired;
        protected $errorMessage;
        protected $helpMessage;
        protected $isReadOnly;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $isRequired, $helpMessage)
        {
           parent::__construct($name, $caption);
        
           $this->isRequired = $isRequired;
           $this->errorMessage = '';
           $this->helpMessage = $helpMessage;
           $this->isReadOnly = false;
        }
        
        /**
         * Gets whether the filed is read-only.
         *
         * @return bool
         */
        public function getIsReadOnly()
        {
           return $this->isReadOnly;
        }
        
        /**
         * Sets whether the field is read-only.
         *
         * @param bool $value
         */
        public function setIsReadOnly($value)
        {
            $this->isReadOnly = $value;
            
            foreach($this->getChildren()->getControlNames(true, 'FieldModelBase') as $controlName)
               $this->getChildren()->getControl($controlName, true)->setIsReadOnly($value);
        }
        
        /**
         * Gets whether a value is required for the control.
         *
         * @return bool
         */
        public function getIsRequired()
        {
           return $this->isRequired;
        }
        
        /**
         * Sets whether a value is required for the control.
         *
         * @param bool $value
         */
        public function setIsRequired($value)
        {
           $this->isRequired = $value;
        }
        
        /**
         * Gets whether the control field has errors.
         *
         * @return bool
         */
        public function hasErrorMessage()
        {
             return (trim($this->getErrorMessage()) == '') ? false : true;
        }
        
        /**
         * Gets the current error message to display.
         *
         * @return str
         */
        public function getErrorMessage()
        {
            return $this->errorMessage;
        }
        
        /**
        * Sets the current error message to display. If the value is specified as an empty string,
        * the hasErrorMessage property will be set to false. It will be set to true otherwise.
        *
        * @param str $value
        */
        public function setErrorMessage($value)
        {
            $this->errorMessage = $value;
        }
        
        /**
        * Gets the current help message to display.
        *
        * @return str
        */
        public function getHelpMessage()
        {
           return $this->helpMessage;
        }
         
        /**
         * Sets the current help message to display.
         *
         * @param str $value
         */
        public function setHelpMessage($value)
        {
           $this->helpMessage = $value;
        }
    }
    
    /**
     * Represents a base implementation of a data source
     *
     * @package GrandPrix
     * @subpackage Model
     */
    abstract class DataSourceControlModelBase extends ControlModelBase
    {
        /**
         * @var ControlModelCollection
         */
        protected $children;
        protected $caption;
        /**
         * @var array
         */
        protected $dataSource = null;
        protected $hasTotals;
        
        public function __construct($name, $caption)
        {
            parent::__construct($name, true);
            $this->caption = $caption;
            $this->children = new ControlModelCollection();
            $this->hasTotals = false;
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
            $this->dataSource = $dataSource;
            $columns = $this->getColumnNames(false);
            $i = 0;
            
            foreach($this->dataSource as $dataRow)
            {
                $row = new Row("row_" . $i);

                foreach($columns as $column)
                {
                    $columnModel = $this->getColumn($column);
                    $row->addColumn($columnModel);
                }

                $row->dataBind($dataRow);
                $this->getChildren()->addControl($row);
                $i++;
            }
            
        }
        
        /**
         * Gets the child controls within this container
         *
         * @return ControlModelCollection
         */
        public function &getChildren()
        {
           return $this->children;
        }
        
        /**
         * Adds a column model to the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param BoundColumnModelBase $column
         */
        public function addColumn(&$column)
        {
            $this->getChildren()->addControl($column);
        }
    
        /**
         * Gets a column model within the collection.
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param str $columnName
         * @param bool $searchRecursive
         * @return BoundColumnModelBase
         */
        public function getColumn($columnName, $searchRecursive = true)
        {
            return $this->getChildren()->getControl($columnName, $searchRecursive, 'BoundColumnModelBase');
        }
        
        /**
         * Returns an array of column names within the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param bool $searchRecursive
         * @return array
         */
        public function getColumnNames($searchRecursive)
        {
            return $this->getChildren()->getControlNames($searchRecursive, 'BoundColumnModelBase');
        }
        
        /**
         * Returns an array of column names within the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param bool $searchRecursive
         * @return array
         */
        public function getRowNames($searchRecursive)
        {
            return $this->getChildren()->getControlNames($searchRecursive, 'Row');
        }
        
        /**
         * Gets the caption for the field
         *
         * @return str
         */
        public function getCaption()
        {
           return $this->caption;
        }
        
        /**
         * Sets the caption to display for the field.
         *
         * @param str $value
         */
        public function setCaption($value)
        {
           $this->caption = $value;
        }
        
        /**
         * Gets the key name
         *
         * @return string
         */
        public function getKeyName()
        {
            return $this->keyName;
        }
    
        /**
         * Sets the key name
         *
         * @param string $value
         */
        public function setKeyName($value)
        {
            $this->keyName = $value;
        }

        /**
         * Sets wether the datasource includes totals in the final row
         *
         * @param bool $value
         */
        public function setHasTotals($value)
        {
            $this->hasTotals = $value;
        }

        /**
         * Gets wether the datasource includes totals in the final row
         *
         * @return bool
         */
        public function getHasTotals()
        {
            return $this->hasTotals;
        }
    }
    
    /**
     * Represents a base implementation of a column
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    abstract class BoundColumnModelBase extends BoundControlModelBase
    {
        const COLUMN_TYPE_STRING = 'string';
        const COLUMN_TYPE_NUMBER = 'number';
        const COLUMN_TYPE_DATE = 'date';
        const COLUMN_TYPE_ACTION = 'action';
        
        protected $columnType;
        protected $caption;
    
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        protected function __construct($name, $caption)
        {
            parent::__construct($name, '', true);
    
            $this->caption = $caption;
        }
    
        /**
         * Gets the caption for the column
         *
         * @return str
         */
        public function getCaption()
        {
            return $this->caption;
        }
    
        /**
         * Sets the caption for the column
         *
         * @param str $caption
         */
        public function setCaption($caption)
        {
            $this->caption = $caption;
        }
    
        /**
        * Gets whether the filed is bindable.
        *
        * @return bool
        */
        public function getIsBindable()
        {
            return $this->isBindable;
        }
        /**
        * Sets whether the field is bindable.
        *
        * @param bool $value
        */
        public function setIsBindable($value)
        {
            $this->isBindable = $value;
        }
        
        /**
        * Gets the name of the member to bind to in the parent bindingSourceName
        *
        * @return str
        */
        public function getBindingMemberName()
        {
            return $this->bindingMemberName;
        }
        
        /**
        * Sets the name of the member to bind to in the parent bindingSourceName
        * If the value is an empty string, it will automatically set the isBindable property to false.
        * If the value is a non-empty string, it will automatically set the isBindable property to true.
        *
        * @param str $value
        */
        public function setBindingMemberName($value)
        {
            if (trim($value) === '')
            {
                $this->bindingMemberName = '';
                $this->setIsBindable(false);
            }
            else
            {
                $this->bindingMemberName = trim($value);
                $this->setIsBindable(true);
            }
        }
        
        public function getColumnType()
        {
            if (is_null($this->columnType))
                throw new WorshipException(WorshipException::EX_INVALIDEMETHODCALL, 'Column type should be initialized on instantiation');
            return $this->columnType;
        }
    }
    
    /**
     * Represents a editable column model
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    abstract class EditableBoundColumnModelBase extends BoundColumnModelBase
    {
        const COLUMN_TYPE_TEXTBOX = 'textbox';
        const COLUMN_TYPE_NUMBERBOX = 'numberbox';
        const COLUMN_TYPE_COMBOBOX = 'combobox';
    }

    /**
     * Represents a row
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    class Row extends ContainerModelBase
    {
        protected $keyValue;
        /**
         * @var array
         */
        protected $dataSource;
    
        public function __construct($name)
        {
            parent::__construct($name, $name);
            $this->dataSource = array();
        }
    
        /**
         * Binds all IBindingTargetMember controls within the row to the given data source.
         * Also, sets IEventTrigger controls by looking for the following key pattern in the collection
         * IEventTrigger->getEventName() . '_eventValue'
         *
         * @param array $dataSource
         */
        public function dataBind(&$dataSource)
        {
            $this->dataSource = $dataSource;
        }
    
        /**
         * Apply a data item to current row
         *
         * @param string $keyName
         */
        public function applyData($keyName)
        {
            if (key_exists($keyName, $this->dataSource))
                $this->setKeyValue($this->dataSource[$keyName]);
    
            $controlNames = $this->getChildren()->getControlNames(true, 'IBindingTargetMember');
            
            foreach($controlNames as $controlName)
            {
                $control = $this->getChildren()->getControl($controlName, true);
                
                if ($control->getIsBindable() &&
                    key_exists($control->getBindingMemberName(), $this->dataSource))
                {
                    $control->setValue($this->dataSource[$control->getBindingMemberName()]);
                }
            }
        }
        
        /**
         * Adds a column model to the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param BoundColumnModelBase $column
         */
        public function addColumn(&$column)
        {
            $this->getChildren()->addControl($column);
        }
    
        /**
        * Gets a row model within the collection.
        * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
        *
        * @param str $columnName
        * @param bool $searchRecursive
        * @return BoundColumnModelBase
        */
        public function getColumn($columnName, $searchRecursive = true)
        {
            return $this->getChildren()->getControl($columnName, $searchRecursive, 'ColumnModelBase');
        }
    
        /**
         * Returns an array of column names within the collection
         * This is a shortcut method. Use the getChildren() method to access the ControlModelCollection.
         *
         * @param bool $searchRecursive
         * @return array
         */
        public function getColumnNames($searchRecursive = true)
        {
            return $this->getChildren()->getControlNames($searchRecursive, 'BoundColumnModelBase');
        }
    
        /**
         * Gets the key value
         *
         * @return mixed
         */
        public function getKeyValue()
        {
            return $this->keyValue;
        }
    
        /**
         * Sets the key value
         *
         * @param mixed $value
         */
        public function setKeyValue($value)
        {
            $this->keyValue = $value;
        }
    }

?>