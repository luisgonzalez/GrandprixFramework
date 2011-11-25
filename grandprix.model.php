<?php
    require_once "grandprix.model.container.php";
    require_once "grandprix.model.datasource.php";
    
    /**
     * Represents a single-line text input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class TextField extends FieldModelBase
    {
        protected $maxChars;
        protected $disabled;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
         
            $this->maxChars = 255;
            $this->disabled = false;
        }
        
        /**
         * Gets the disable status of the Field
         *
         * @return bool
         */
        public function getIsDisabled()
        {
            return $this->disabled;
        }
        
        /**
         * Sets the disable status of the Field
         *
         * @param bool value
         */
        public function setIsDisabled($value)
        {
            $this->disabled = $value;
        }
      
        /**
         * Determines the maximum length of characters to accept
         *
         * @return int
         */
        public function getMaxChars()
        {
            return $this->maxChars;
        }
        
        /**
         * Determines the maximum length of characters to accept
         *
         * @param int $value
         */
        public function setMaxChars($value)
        {
            $this->maxChars = $value;
        }
    }
    
    class AutoCompleteField extends FieldModelBase
    {
        protected $isMultiple;
        protected $dataSource;
        protected $dataSourceIdField;
        protected $dataSourceValueField;
        
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
        }
        
        /**
         * Sets the datasource for the auto complete field
         *
         * @param array $dataSource Array containing keyed arrays
         * @param string $dataSourceIdField
         * @param string $dataSourceDescriptionField
         */
        public function setDataSource($dataSource, $dataSourceIdField, $dataSourceDescriptionField)
        {
            $this->dataSource = $dataSource;
            foreach ($this->dataSource as $key => &$value)
            {
                foreach ($value as $kValue => &$vValue)
                {
                    $vValue = iconv('ISO-8859-1', 'UTF-8', $vValue);
                }
            };
            $this->dataSourceIdField = $dataSourceIdField;
            $this->dataSourceDescriptionField = $dataSourceDescriptionField;
        }
        
        public function getDataSource()
        {
            return $this->dataSource;
        }
        
        public function getDataSourceIdField()
        {
            return $this->dataSourceIdField;
        }
        
        public function getDataSourceDscField()
        {
            return $this->dataSourceDescriptionField;
        }
        
        public function setIsMultiple($value)
        {
            $this->isMultiple = $value;
        }
        
        public function getIsMultiple()
        {
            return $this->isMultiple;
        }
        
        public function setValue($value)
        {
            if ($this->isMultiple && is_string($value))
                parent::setValue(implode('|', $value));
            else
                parent::setValue($value);
        }
        
        public function &getValue()
        {
            $value = parent::getValue();
            if ($this->isMultiple == false)
                return $value;
            else
                return explode('|', $value);
        }
    }
    
    /**
     * Implements a field to upload and download files.
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    class FileField extends FieldModelBase implements IEventTrigger
    {
        protected $isMultiple;
        protected $eventName;
        protected $eventValue;
        
        /**
         * Creates a new instance of the class
         *
         * @param string $name
         * @param string $caption
         * @param string $value
         * @param bool $isRequired
         * @param string $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->isMultiple = false;
            $this->eventName = 'upload';
            $this->eventValue = 'Upload';
            $this->validator = new FileFieldValidator();
        }
        
        /**
         * Sets wether multiple uploads will be handled
         *
         * @param bool $value
         */
        public function setIsMultiple($value)
        {
            $this->isMultiple = $value;
        }
        
        /**
         * Gets if the control accepts multiple uploads
         *
         * @return bool;
         */
        public function getIsMultiple()
        {
            return $this->isMultiple;
        }
        
        /**
         * Gets the eventName
         *
         * @return string
         */
        public function getEventName()
        {
            return $this->eventName;
        }
        
        /**
         * Gets the eventName
         *
         * @return string
         */
        public function setEventName($value)
        {
            $this->eventName = $value;
        }
        
        /**
         * Gets the event value
         *
         * @return string
         */
        public function getEventValue()
        {
            return $this->eventValue;
        }
        
        /**
         * Sets the event value
         */
        public function setEventValue($value)
        {
            $this->eventValue = $value;
        }
    }
    
    
    /**
     * Represents a text input with a file validator
     * 
     * @package WorshipKitchen
     * @subpackage Model
     */
    class FileExistField extends TextField
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param bool $isReadOnly
         */
        public function __construct($name, $caption, $isReadOnly)
        {
            parent::__construct($name, $caption, '', false, '');
            $this->isReadOnly = $isReadOnly;
        }
        
        public function getFileValue()
        {
            $val = explode('|', $this->getValue());
            
            return $val[0];
        }
        
        public function getPreviewValue()
        {
            $val = explode('|', $this->getValue());
            
            return $val[1];
        }
    }
    
    /**
     * Represents a single-line search text input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class SearchField extends TextField
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
        }
    }
    
    /**
     * Model base for buttons.
     *
     * @package Grandprix
     * @subpackage Model
     */ 
    abstract class ButtonModelBase extends ControlModelBase implements IEventTrigger
    {
    
    }
    
    /**
     * Represents a common button.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class Button extends ButtonModelBase
    {
        protected $eventName;
        protected $eventValue;
        protected $caption;
        protected $isEnabled;
     
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $eventName
         */
        public function __construct($name, $caption, $eventName)
        {   
            parent::__construct($name, true);
     
            $this->eventName = $eventName;
            $this->eventValue = '';
            $this->caption = $caption;
            $this->isEnabled = true;
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
         * Gets the name of the event to raise
         *
         * @return str
         */
        public function getEventName()
        {
            return $this->eventName;
        }
        /**
         * Sets the name of the event to raise
         *
         * @param str $value
         */
        public function setEventName($value)
        {
           $this->eventName = $value;
        }
     
        /**
         * Gets the event value.
         *
         * @return str
         */
        public function getEventValue()
        {
            return $this->eventValue;
        }
     
        /**
         * Sets the event value.
         *
         * @param str $value
         */
        public function setEventValue($value)
        {
            $this->eventValue = $value;
        }
     
        /**
         * Gets whether the button is enabled.
         *
         * @return bool
         */
        public function getIsEnabled()
        {
            return $this->isEnabled;
        }
        
        /**
         * Sets whether the button is enabled.
         *
         * @param bool $value
         */
        public function setIsEnabled($value)
        {
            $this->isEnabled = $value;
        }
    }
    
    /**
     * Represents a checkbox input
     *
     * @package Grandprix
     * @subpackage Model
     */
    class CheckBox extends FieldModelBase
    {
        protected $checkedValue;
        protected $uncheckedValue;
     
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param str $checkedValue
         * @param str $uncheckedValue
         * @param str $helpMessage
         * @param bool $isRequired
         */
        public function __construct($name, $caption, $value = '0', $checkedValue = '1', $uncheckedValue = '0', $helpMessage = '', $isRequired = true)
        {
            $this->checkedValue = $checkedValue;
            $this->uncheckedValue = $uncheckedValue;
     
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
        }
        /**
         * Sets the value the checkbox holds when checked.
         *
         * @param str $value
         */
        public function setCheckedValue($value)
        {
            $this->checkedValue = $value;
        }
        /**
         * Gets the value the checkbox holds when checked.
         *
         * @param str $value
         */
        public function getCheckedValue()
        {
            return $this->checkedValue;
        }
        
        /**
         * Sets the value the checkbox holds when unchecked.
         *
         * @param str $value
         */
        public function setUncheckedValue($value)
        {
           $this->uncheckedValue = $value;
        }
        
        /**
         * Sets the value the checkbox holds when checked.
         *
         * @param str $value
         */
        public function getUncheckedValue()
        {
            return $this->uncheckedValue;
        }
        
        /**
         * Gets whether the checkbox is in the checked state.
         *
         * @param bool $value
         */
        public function setIsChecked($value)
        {
            $this->value = ($value === true) ? $this->getCheckedValue() : $this->getUncheckedValue();
        }
        
        /**
         * Sets whether the checkbox is in the checked state.
         *
         * @param bool $value
         */
        public function getIsChecked()
        {
            return ($this->checkedValue == $this->value);
        }
        
        /**
         * Gets the current value of the model.
         *
         * @return str
         */
        public function &getValue()
        {
            $value = ($this->getIsChecked()) ?  $this->getCheckedValue() : $this->getUncheckedValue();
            
            return $value;
        }
        
        /**
         * Sets the current value of the model.
         *
         * @return str
         *
         * @todo Remove this method
         */
        public function setValue($value)
        {
           parent::setValue($value);
        }
    }
    
    /**
     * Represents a ToggleButton input
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ToggleButton extends CheckBox
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param str $checkedValue
         * @param str $uncheckedValue
         * @param str $helpMessage
         * @param bool $isRequired
         */
        public function __construct($name, $caption, $value = '0', $checkedValue = '1', $uncheckedValue = '0', $helpMessage = '', $isRequired = true)
        {
            parent::__construct($name, $caption, $value, $checkedValue, $uncheckedValue, $helpMessage, $isRequired);
        }
    }
    
    /**
     * Represents a ComboBox input
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ComboBox extends FieldModelBase implements IEventTrigger
    {
        protected $eventName;
        protected $eventValue;
        /**
         *@var array
         */
        protected $options;
        protected $isMultiple;
        protected $useButton;
        protected $multipleSeparator;
        
        /**
         * Creates a new instance of this class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param str $eventName Leave empty if not intended for event triggering
         * @param str $eventValue Leave empty if not intended for event triggering
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $eventName = '', $eventValue = '', $helpMessage = '', $isRequired = true)
        {
            $this->eventName = $eventName;
            $this->eventValue = $eventValue;
            $this->options = array();
            $this->isMultiple = false;
            $this->useButton = false;
            $this->multipleSeparator = "|";
            
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
        }
        
        /**
         * Adds an option to the selectable options.
         * @param str $value The option's value
         * @param str $display The string to display in the option
         * @param str $category The category for this option 
         */
        public function addOption($value, $display, $category = '')
        {
            $newOption = array('value' => $value, 'display' => $display, 'category' => $category);
            $this->options[] = $newOption;
        }
        
        public function setValue($value)
        {
            if ($this->isMultiple)
            {
                $data = $value;
                if (is_array($data) === false)
                    $data = explode($this->multipleSeparator, $value);
                
                $newValue = array();
                
                foreach($data as $val) $newValue[] = trim($val);

                parent::setValue(implode($this->multipleSeparator, $newValue));
            }
            else
                parent::setValue($value);
        }
        
        public function &getValue()
        {
            $value = parent::getValue();

            if ($this->isMultiple) return explode($this->multipleSeparator, $value);
            
            return $value;
        }
        /**
         * Adds an array to the selectable options.
         * 
         * @param array $items
         * @param str $valueMember The member of the object or array to be used as the value
         * @param str $displayMember The member of the object or array to be displayed
         * @param str $categoryMemeber The member of the object or array to categorize the option in
         */
        public function addOptions($items, $valueMember = 'value', $displayMember = 'display', $categoryMemeber = 'category')
        {
            if(is_array($items) == false) return;
              
            foreach ($items as $option)
            {
                $newOption = array('value' => '', 'display' => '', 'category' => '');
                while (list($key, $value) = each($option))
                {
                    if ($key === $valueMember) { $newOption['value'] = $value; continue; }
                    if ($key === $displayMember) { $newOption['display'] = $value; continue; }
                    if ($key === $categoryMemeber) { $newOption['category'] = $value; continue; }
                }
                reset($option);
                
                $this->options[] = $newOption;
            }
        }
        
        /**
         * Gets the options that have been added to this model.
         * 
         * @return array
         */
        public function &getOptions()
        {
            return $this->options;
        }
        
        /**
         * Gets the name of the event to raise
         *
         * @return str
         */
        public function getEventName()
        {
            return $this->eventName;
        }
        
        /**
         * Sets the name of the event to raise
         *
         * @param str $value
         */
        public function setEventName($value)
        {
            if (is_string($value) === false)
            throw new SystemException(SystemException::EX_INVALIDPARAMETER, 'Parameter = value');
            if (trim($value) == '')
            throw new SystemException(SystemException::EX_INVALIDPARAMETER, 'Parameter = value');
  
            $this->eventName = $value;
        }
  
        /**
         * Gets the event value.
         *
         * @return str
         */
        public function getEventValue()
        {
            return $this->eventValue;
        }
  
        /**
         * Sets the event value.
         *
         * @param str $value
         */
        public function setEventValue($value)
        {
            $this->eventValue = $value;
        }
        
        /**
         * Gets true is multiple
         *
         * @return bool
         */
        public function getIsMultiple()
        {
            return $this->isMultiple;
        }
  
        /**
         * Sets is it's multiple
         *
         * @param bool $value
         */
        public function setIsMultiple($value)
        {
            $this->isMultiple = $value;
        }
        
        /**
         * Gets multiple value separator
         *
         * @return string
         */
        public function getMultipleSeparator()
        {
            return $this->multipleSeparator;
        }
  
        /**
         * Sets multiple value separator
         *
         * @param string $value
         */
        public function setMultipleSeparator($value)
        {
            $this->multipleSeparator = $value;
        }
    }
    
    /**
     * Represents a ComboBox input using a Button menu
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ButtonComboBox extends ComboBox
    {
    }

    /**
     * Represents a simple image input
     *
     * @package WorshipKitchen
     * @subpackage Model
     */ 
    class ImageField extends TextField implements IEventTrigger
    {
        private $thumbnail;
        private $imageId;
        private $eventName;
        private $eventValue;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value Any valid string for strtotime.
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $eventName = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new RegExFieldValidator('/^[A-Z0-9 \/\.]+$/','');
            $this->eventName = $eventName;
            $this->setThumbnail($value);
        }
        
        public function setImageId($value)
        {
            $this->imageId = $value;
        }
        
        public function getImageId()
        {
            return $this->imageId;
        }
        /**
         * Must be called after setValue
         */
        private function setThumbnail($value)
        {
            if ($value == '') return;

            $imageValue = $this->getImageValue();
            $path = ($this->value == $imageValue ? "" : substr($this->value, 0, -strlen($imageValue)));
            $this->thumbnail = $path . "thumb_" . $imageValue;
        }
        
        public function getImageValue()
        {
            $imageValue = explode("/", $this->value);
            
            return $imageValue[count($imageValue) - 1];
        }
        
        public function setValue($value)
        {
            parent::setValue($value);
            $this->setThumbnail($value);
        }
        
        public function getThumbnail()
        {
            return $this->thumbnail;
        }
        
        public function setEventName($value)
        {
            $this->eventName = $value;
        }
        
        public function getEventName()
        {
            return $this->eventName;
        }
        
        public function setEventValue($value)
        {
            $this->eventValue = $value;
        }
        
        public function getEventValue()
        {
            return $this->eventValue;
        }
    }
    
    /**
     * Represents a simple date input
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    class DateField extends FieldModelBase
    {
        protected $minDate;
        protected $maxDate;

        /**
         * Creates a new instance of the class
         *
         * If no valid string is provided or is not a valid strtotime string,
         * the default value is the current timestamp
         *
         * @param str $name
         * @param str $caption
         * @param str $value Any valid string for strtotime.
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);

            if ($value == '')
                $this->value = null;
            else {
                $tmpValue = strtotime($value);
                if ($tmpValue === false)
                    $tmpValue = time();
                $this->value = $tmpValue;
            }
        }
        
        /**
         * Sets the value for the current DateField
         * 
         * @param string $value any valid string for strtotime
         */
        public function setValue($value)
        {
            if ($value == '') {
                $this->value = null;
            }
            else {
                $tmpValue = strtotime($value);
                if ($tmpValue === false)
                    $tmpValue = time();
                $this->value = $tmpValue;
            }
        }

        /**
         * Returns the value of the DateField. If no format is provided, the value is returned in a Database Friendly format
         *
         * @param string $format
         *
         * @return string
         */
        public function &getValue($format = '')
        {
            if (is_null($this->value))
                return null;
            
            if ($format == '')
                return date('Y-m-d', $this->value);
                
            return date($format, $this->value);
        }
        
        /**
         * Gets the valid startDate for the calendar.
         * If startDate is null, returns empty string
         * Else if format is set, returns the date in the format provided
         * Else it returns the timestamp
         *
         * @param $format
         *
         * @return mixed
         */
        public function getMinDate($format = '')
        {
            if (is_null($this->minDate)) return '';
            if ($format !== '') return date($format, $this->minDate);
            return $this->minDate;
        }

        /**
         * Sets the valid start date for the calendar
         *
         * @param $value
         */
        public function setMinDate($value)
        {
            $tmpValue = strtotime($value);

            if ($tmpValue === false)
                throw new WorshipException(WorshipException::EX_INVALIDPARAMMETHOD, "Parameter = value. '$value' is not a valid date");

            $this->minDate = $tmpValue;
        }

        /**
         * Gets the valid endDate for the calendar.
         * If endDate is null, returns empty string
         * Else if format is set, returns the date in the format provided
         * Else it returns the timestamp
         *
         * @param $format
         *
         * @return mixed
         */
        public function getMaxDate($format = '')
        {
            if (is_null($this->maxDate)) return '';
            if ($format !== '') return date($format, $this->maxDate);
            return $this->maxDate;
        }

        /**
         * Sets the valid end date for the calendar
         *
         * @param $value
         */
        public function setMaxDate($value)
        {
            $tmpValue = strtotime($value);
            
            if ($tmpValue === false)
                throw new WorshipException(WorshipException::EX_INVALIDPARAMMETHOD, "Parameter = value. '$value' is not a valid date");
            
            $this->maxDate = $tmpValue;
        }
    }

    /**
     * Represents a simple multi-line text input
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    class TextArea extends FieldModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
        }   
    }
    
    class RichTextEditor extends TextArea
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
        }
        
        public function setValue($value)
        {
            parent::setValue(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value));
        }
        
        public function &getValue()
        {
            return parent::getValue();
            //return iconv('ISO-8859-1//TRANSLIT', 'UTF-8', parent::getValue());
        }
    }
    
    /**
     * Represents a single-line text input for password input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class PasswordField extends TextField
    {
        private $mustVerify;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->mustVerify = false;
            $this->validator = new PasswordFieldValidator();
        }
        
        /**
         * Sets if must verify
         *
         * @param bool $value
         */
        public function setMustVerify($value)
        {
           $this->mustVerify = $value;
        }
        
        /**
         * Gets if must verify
         *
         * @return bool
         */
        public function getMustVerify()
        {
           return $this->mustVerify;
        }
    }
    
    /**
     * Represents a read-only text
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class LabelField extends TextField
    {
        private $editable;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         * @param bool $editable
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '', $editable = false)
        {
           parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
           $this->editable = $editable;
           $this->isRequired = false;
        }
        
        /**
         * Sets if field is editable
         *
         * @param bool $value
         */
        public function setEditable($value)
        {
           $this->editable = $value;
        }
        
        /**
         * Gets if field is editable
         *
         * @return bool
         */
        public function getEditable()
        {
           return $this->editable;
        }
    }
    
    /**
     * Represents a DeletableLabelField
     *
     * @package Grandprix
     * @subpackage Model
     */
    class DeletableLabelField extends LabelField
    {
        protected $idValue;
        
        public function setId($value)
        {
            $this->text = $value;
        }
        
        public function getId()
        {
            return $this->text;
        }
    }
    
    /**
     * Represents a field with any HTML like value
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class RawField extends TextField implements IEventTrigger
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, false, $helpMessage);
        }
        
        public function getEventName() { return $this->name;}
        public function setEventName($value) { }
        public function getEventValue() { return 1; }
        public function setEventValue($value) {} 
    }
    
    /**
     * Implements a field that is used to persist a value accross events.
     * This field is usually invisible in the view.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class PersistorField extends BoundControlModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $value
         */
        public function __construct($name, $value)
        {
            parent::__construct($name, $value, true);
        }
    }
    
    /**
     * Represents a single-line text input for phone number input
     * 
     * @package WorshipKitchen
     * @subpackage Model
     */
    class PhoneField extends TextField
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new RegExFieldValidator("/^[a-zA-Z0-9 \#\.\+\(|\)\-]+$/i", "Invalid phone number format");
            $this->maxChars = 30;
        }
    }
    
    /**
     * Represents a single-line text input for zip input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class ZipCodeField extends TextField
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new RegExFieldValidator("/^\d{5}$/i", 'Invalid postal code format');
            $this->maxChars = 5;
        }
    }
    
    /**
     * Represents a single-line text input for zip input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class Ccv2Field extends TextField
    {
        private $type;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new Ccv2FieldValidator();
            $this->maxChars = 4;
        }

        /**
         * Gets CC number type
         *
         * @return string
         */
        public function getType()
        {
           return $this->type;
        }

        /**
         * Sets CC number type
         *
         * @param string $value
         */
        public function setType($value)
        {
           $this->type = $value;
           $this->validator->setType($value);
        }
    }

    /**
     * Represents a single-line text input for email input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class EmailField extends TextField
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new RegExFieldValidator("/^[a-zA-Z0-9]+[_a-zA-Z0-9-]*(\.[_a-z0-9-]+)*@[a-z?G0-9]+(-[a-z?G0-9]+)*(\.[a-z?G0-9-]+)*(\.[a-z]{2,4})$/i", "Formato de Email inválido");
            $this->maxChars = 255;
        }
    }
    
    /**
     * Represents a single-line text input for numbers
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class NumericField extends TextField
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new NumericFieldValidator();
        }
    }
    
    /**
     * Represents a single-line text input for credit card input
     * 
     * @package Grandprix
     * @subpackage Model
     */
    class CreditCardField extends TextField
    {
        private $type;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         * @param str $value
         * @param bool $isRequired
         * @param str $helpMessage
         */
        public function __construct($name, $caption, $value = '', $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $value, $isRequired, $helpMessage);
            $this->validator = new CreditCardFieldValidator();
            $this->maxChars = 16;
        }
       
        /**
         * Gets CC number type
         *
         * @return string
         */
        public function getType()
        {
           return $this->type;
        }
        
        /**
         * Sets CC number type
         *
         * @param string $value
         */
        public function setType($value)
        {
           $this->type = $value;
           $this->validator->setType($value);
        }
       
        /**
         * Gets value in with mask to cover complete CC number
         *
         * @return string
         */
        public function getMaskValue()
        {
            $value = parent::getValue();
            
            return str_repeat(str_repeat("X",4) . '-',3) . substr($value, 12, strlen($value)-12);;
        }
    }
    
    /**
     * Represents a Month-Year comboboxes group
     *
     * @package Grandprix
     * @subpackage Model
     */
    class MonthYearComboBox extends CompoundFieldModelBase
    {
        /**
         * Creates a new instance of this class
         *
         * @param string $name
         * @param string $caption
         * @param boolean $isRequired
         * @param string $helpMessage
         */
        public function __construct($name, $caption, $isRequired = true, $helpMessage = '')
        {
            parent::__construct($name, $caption, $isRequired, $helpMessage);
            
            $comboMonth = new ComboBox($this->name . 'Month', $this->caption);
            
            $months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
            $countMonth = 1;
            
            foreach($months as $month)
            {
               $comboMonth->addOption($countMonth, $month);
               $countMonth++;
            }
            
            $comboYear = new ComboBox($this->name . 'Year', $this->caption);
            
            for($i = 0; $i < 15; $i++)
            {
               $year = intval(date("Y")) + $i;
               $comboYear->addOption($year, $year);
            }
            
            $this->getChildren()->addControl($comboMonth);
            $this->getChildren()->addControl($comboYear);
        }
        
        /**
         * Gets user control merged value
         *
         * @return string
         */
        public function getValue()
        {
           $comboMonth = $this->getChildren()->getControl($this->name . 'Month');
           $comboYear = $this->getChildren()->getControl($this->name . 'Year');
           
           return array("year" => intval($comboYear->getValue()),
                        "month" => intval($comboMonth->getValue()));
        }
        
        /**
         * Validates the user control
         *
         * @return bool
         */
        public function validate()
        {
            $comboMonth = $this->getChildren()->getControl($this->name . 'Month');
            $comboYear = $this->getChildren()->getControl($this->name . 'Year');
            
            if ($comboMonth->validate() && $comboYear->validate())
               return true;
            
            $errorMessage = $comboMonth->getErrorMessage();
            
            if ($errorMessage == '')
               $errorMessage = $comboYear->getErrorMessage();
               
            $this->setErrorMessage($errorMessage);
            
            return false;
        }
        
        /**
         * Sets required boolean to children
         *
         * @param bool $value
         */
        public function setIsRequired($value)
        {
            $this->getChildren()->getControl($this->name . 'Month')->setIsRequired($value);
            $this->getChildren()->getControl($this->name . 'Year')->setIsRequired($value);
        }
    }
    
    /**
     * Represents a Credit Card field with CC type validation
     *
     * @package Grandprix
     * @subpackage Model
     */
    class CreditCardCompoundField extends ContainerModelBase implements IValidatable
    {
        private $isRequired;
        
        /**
         * Creates an instance of this class
         *
         * @param string $name
         * @param string $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            
            $types = array(
                            array("value" => "American Express", "display" => "American Express"),
                            array("value" => "Discover", "display" => "Discover"),
                            array("value" => "MasterCard", "display" => "MasterCard"),
                            array("value" => "Visa", "display" => "Visa"));
            $this->isRequired = true;
            
            $comboCC = new ComboBox($this->name . 'Type', $this->caption . ' Type');
            $comboCC->addOptions($types);
            $this->getChildren()->addControl($comboCC);
            
            $this->getChildren()->addControl(new CreditCardField($this->name . 'Number', $this->caption . ' Number'));
            $this->getChildren()->addControl(new LabelField($this->name . 'CurrentNumber', 'Current Number'));
            $this->getChildren()->getControl($this->name . 'CurrentNumber')->setIsRequired(false);
            $this->getChildren()->getControl($this->name . 'CurrentNumber')->setVisible(false);
            
            $fieldCCV2 = new Ccv2Field($this->name . 'CCV2', 'Security Code', '', true,
                                       'MasterCard and Visa credit and debit cards have a 3-digit code printed on the back signature panel of the card.<br/><br/><img src="' . str_repeat('../', HtmlViewManager::getDepth()) . 'lib/images/mini_cvv2.gif" /><br/><br/>American Express cards have a 4-digit code printed on the front side of the card above the number.');
            $fieldCCV2->setMaxChars(4);
            $this->getChildren()->addControl($fieldCCV2);
            $this->getChildren()->addControl(new TextField($this->name . 'Name', 'Name on Card'));
            $this->getChildren()->addControl(new MonthYearComboBox($this->name . 'ExpDate', 'Expiration Date'));
            
            $nameOnCardValidator = new RegExFieldValidator('/^[A-Z0-9 ]+$/i','Name must have letters and/or numbers only');
            $this->getChildren()->getControl($this->name . 'Name')->setValidator($nameOnCardValidator);
        }
        
        /**
         * Gets CC number value
         *
         * @return string
         */
        public function getNumberValue()
        {
            return $this->getChildren()->getControl($this->name . 'Number')->getValue();
        }
        
        /**
         * Sets CC number value
         *
         * @param string $value
         */
        public function setNumberValue($value)
        {
            return $this->getChildren()->getControl($this->name . 'Number')->setValue($value);
        }
        
        /**
         * Validates the user control
         *
         * @return bool
         */
        public function validate()
        {
            if ($this->isRequired === false) return true;
              
            $typeField = $this->getChildren()->getControl($this->name . 'Type');
            $nameField = $this->getChildren()->getControl($this->name . 'Name');
            $ccv2Field = $this->getChildren()->getControl($this->name . 'CCV2');
            $numberField = $this->getChildren()->getControl($this->name . 'Number');
            $dateField = $this->getChildren()->getControl($this->name . 'ExpDate');
            
            $nameState = $nameField->getIsReadOnly();
            $nameField->setIsReadOnly(false);
            
            $this->setErrorMessage('');
            
            $valid = false;
            $numberField->setType($typeField->getValue());
            $ccv2Field->setType($typeField->getValue());
            
            if ($nameField->validate() && $ccv2Field->validate() &&
                $typeField->validate() && $numberField->validate())
            {
                $valid = true;
            }
            
            if ($dateField->validate())
            {
                $date = $dateField->getValue();
                $currentDate = getdate();
                
                if ($currentDate["year"] > $date["year"])
                {
                    $dateField->setErrorMessage('Expiration Date is expired');
                    $valid = false;
                }
                elseif ($date["year"] == $currentDate["year"])
                {
                    if ($currentDate["mon"] > $date["month"])
                    {
                        $dateField->setErrorMessage('Expiration Date is expired');
                        $valid = false;
                    }
                }
            }

            if ($valid == false) $nameState = false;
            
            $nameField->setIsReadOnly($nameState);

            return $valid;
        }
        
        /**
         * Sets read-only boolean to children
         *
         * @param bool $value
         */
        public function setIsReadOnly($value)
        {
            $this->getChildren()->getControl($this->name . 'Type')->setIsReadOnly($value);
            $this->getChildren()->getControl($this->name . 'Number')->setIsReadOnly($value);
            $this->getChildren()->getControl($this->name . 'ExpDate')->setIsReadOnly($value);
            $this->getChildren()->getControl($this->name . 'Name')->setIsReadOnly($value);
            $this->getChildren()->getControl($this->name . 'CCV2')->setIsReadOnly($value);
        }
        
        /**
         * Sets required boolean to children
         *
         * @param bool $value
         */
        public function setIsRequired($value)
        {
            $this->getChildren()->getControl($this->name . 'Type')->setIsRequired($value);
            $this->getChildren()->getControl($this->name . 'Number')->setIsRequired($value);
            $this->getChildren()->getControl($this->name . 'ExpDate')->setIsRequired($value);
            $this->getChildren()->getControl($this->name . 'Name')->setIsRequired($value);
            $this->getChildren()->getControl($this->name . 'CCV2')->setIsRequired($value);
            $this->isRequired = $value;
        }
        
        /**
         * Sets if the Credit Card number will be shown on edit
         */
        public function setShowCurrentNumber($value)
        {
            $this->getChildren()->getControl($this->name . 'CurrentNumber')->setVisible($value);
        }
    }
    
    /**
     * Represents a list box
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ListBox extends ControlModelBase
    {
        private $items;
        private $headers;
        private $eventName;
        private $tooltip;
        private $sortDirection;
        private $highlightedItem;
        private $isPaging;
        private $totalRecords;
        private $currentPage;
        
        public function __construct($name, $visible = true, $eventName = '', $sortDirection = 'asc')
        {
            parent::__construct($name, $visible);
            
            $this->eventName = $eventName;
            $this->items = array();
            $this->headers = array();
            $this->tooltip = true;
            $this->sortDirection = $sortDirection;
            $this->highlightedItem = '';
            $this->totalRecords = 0;
            $this->isPaging = false;
            $this->currentPage = isset($_REQUEST['listbox_' . $name . '_offset']) ? $_REQUEST['listbox_' . $name . '_offset'] : 1;
        }
        
        public function getSortDirection()
        {
            return $this->sortDirection;
        }
        
        public function getIsPaging()
        {
            return $this->isPaging;
        }
        
        public function setIsPaging($value)
        {
            $this->isPaging = $value;
        }
        
        public function getTotalRecords()
        {
            return $this->totalRecords;
        }
        
        public function setTotalRecords($value)
        {
            $this->totalRecords = $value;
        }
        
        public function getTooltip()
        {
            return $this->tooltip;
        }
        
        public function setTooltip($value)
        {
            $this->tooltip = $value;
        }

        /**
         * Gets the name of the event to raise
         *
         * @return str
         */
        public function getEventName()
        {
            return $this->eventName;
        }
        /**
         * Sets the name of the event to raise
         *
         * @param str $value
         */
        public function setEventName($value)
        {
           $this->eventName = $value;
        }
        
        /**
         * Generates the headers of the table based on the items keys
         */
        protected function generateHeaders()
        {
            if (count($this->items) > 0)
            {
                $this->headers = array();
                foreach ($this->items as $item)
                {
                    foreach($item as $key => $value)
                        $this->headers[$key] = $key;
                    break;
                }
            }
        }
        
        /**
         * Sets the items that will fill the list
         *
         * @param array $items An array containing associative arrays as elements 
         */
        public function setItems($items)
        {
            $this->items = $items;
            
            if (count($this->headers) == 0) $this->generateHeaders();
        }
        
        /**
         * Returns the items that will fill the list
         *
         * @return array
         */
        public function getItems()
        {
            return $this->items;
        }
        
        /**
         * Sets the display values for the headers
         *
         * @param array $headers An associative array whose keys correspond to the keys used to associate the items
         */
        public function setHeaders($headers)
        {
            $this->headers = array();
            
            foreach($headers as $key => $value)
            {
                $this->headers[$key] = $value;
            }
        }
        
        /**
         * Returns the headers of the ListBox
         *
         * @return array
         */
        public function getHeaders()
        {
            return $this->headers;
        }
        
        /**
         * Sets the current page for the listbox
         */
        public function setCurrentPage($value)
        {
            $this->currentPage = $value;
        }

        /**
         * Gets the current page for the listbox
         */
        public function getCurrentPage()
        {
            return $this->currentPage;
        }

        /**
         * Sets the highlighted item using its ID (first column value)
         *
         * @param string $value
         */
        public function setHighlightedItem($value)
        {
            $this->highlightedItem = $value;
        }

        /**
         * Gets the highlighted item using its first column value
         *
         * @param string $value
         */
        public function getHighlightedItem()
        {
            return $this->highlightedItem;
        }
    }
   
    /**
     * Represents a message control
     *
     * @package Grandprix
     * @subpackage Model
     */
    class MessageControl extends ControlModelBase
    {
        private $value;
        
        /**
         * Creates an instance of this class
         *
         * @param string $name
         * @param string $value
         */
        public function __construct($name, $value)
        {
            parent::__construct($name, true);
            
            $this->value = $value;
        }
        
        /**
         * Gets content value
         *
         * @return string
         *
         * @todo Remove this method
         */
        public function getValue()
        {
            return $this->value;
        }
        
        /**
         * Sets content value
         *
         * @param string $value
         *
         * @todo Remove this method
         */
        public function setValue($value)
        {
            $this->value = $value;
        }
    }
    
    /**
     * Represents an adspot control
     *
     * @package Grandprix
     * @subpackage Model
     */
    class AdSpotControl extends ControlModelBase
    {
        private $value;
        
        /**
         * Creates an instance of this class
         *
         * @param string $name
         * @param string $value
         */
        public function __construct($name, $value)
        {
            parent::__construct($name, true);
            
            $this->value = $value;
        }
        
        /**
         * Gets content value
         *
         * @return string
         */
        public function getValue()
        {
            return $this->value;
        }
        
        /**
         * Sets content value
         *
         * @param string $value
         */
        public function setValue($value)
        {
            $this->value = $value;
        }
    }
?>