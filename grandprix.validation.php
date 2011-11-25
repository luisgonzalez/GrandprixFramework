<?php
    /**
     * Provides a basic implementation for a field validator.
     * The implementation takes into account the isReadOnly and isRequired fields only
     * Further validation might be needed in a derived class.
     *
     * @package Grandprix
     * @subpackage Model
     */
    class BasicFieldValidator
    {
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param IValidatable $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if ($sender->getIsRequired() === true && $sender->getIsReadOnly() === false)
            {
                if ($sender instanceof ComboBox && $sender->getValue() === '0'
                    || is_string($sender->getValue()) && trim($sender->getValue()) === '')
                {
                   $sender->setErrorMessage('Campo requerido');
                   return false;
                }
            }
            
            $sender->setErrorMessage('');
            
            return true;
        }
    }
    
    /**
     * Provides a basic implementation for an password field validator.
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    class PasswordFieldValidator extends BasicFieldValidator
    {
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param FieldModelBase $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if (!parent::validate($sender, $eventArgs)) return false;
            if ($sender->getIsReadOnly()) return true;
            
            $value = $sender->getValue();
            
            if (strlen($value) == 0 && $sender->getIsRequired() === false) return true;
            
            if (strlen($value) < 5)
            {
                $sender->setErrorMessage('Contraseña demasiado corta, minimo 5 caracteres');
                return false;
            }
            
            $sender->setErrorMessage('');
            
            return true;
        }
    }
    
    /**
     * Provides a basic implementation for an regex field validator.
     *
     * @package WorshipKitchen
     * @subpackage Model
     */
    class RegExFieldValidator extends BasicFieldValidator
    {
        private $regex;
        private $errorMessage;
        
        public function __construct($regex, $errorMessage)
        {
            $this->regex = $regex;
            $this->errorMessage = $errorMessage;
        }
        
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param FieldModelBase $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if (!parent::validate($sender, $eventArgs)) return false;
            
            $sender->setValue(trim($sender->getValue()));
            
            if ($sender->getValue() == '' && !$sender->getIsRequired())
            {
                $sender->setErrorMessage('');
                return true;
            }
            
            $valid = preg_match($this->regex, $sender->getValue());
            
            if (!$valid)
            {
                $sender->setErrorMessage($this->errorMessage);
                return false;
            }
            
            $sender->setErrorMessage('');
            
            return true;
        }
    }
    
    /**
    * Provides a basic implementation for an credit card field validator.
    *
    * @package Grandprix
    * @subpackage Model
    */
    class CreditCardFieldValidator extends BasicFieldValidator
    {
        private $type = null;
        
        public function setType($value)
        {
            $this->type = $value;
        }
        
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param FieldModelBase $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if (!parent::validate($sender, $eventArgs)) return false;
            if ($sender->getIsRequired() === false) return true;
            
            $number = $sender->getValue();
            $number = ereg_replace('[^0-9]','', $number);
            
            if ($number == '')
            {
                $sender->setErrorMessage("Credit Card is invalid");
                return false;
            }
   
            $numberLeft = substr($number, 0, 4);
            $numberRight = substr($number, -4);
            $numberLength = strlen($number);
       
            if (($numberLeft>= 3400) && ($numberLeft<= 3499))
            {
               $type='American Express';
               $shouldLength=15;
            } elseif (($numberLeft>= 3700) && ($numberLeft<= 3799)) {
               $type='American Express';
               $shouldLength=15;
            }
            elseif (($numberLeft>= 4000) && ($numberLeft<= 4999))
            {
                $type='Visa';
                
                if ($numberLength > 14)
                    $shouldLength=16;
                elseif ($numberLength < 14)
                    $shouldLength=13;
            }
            elseif (($numberLeft>= 5100) && ($numberLeft<= 5599))
            {
                $type='MasterCard';
                $shouldLength=16;
            }
            elseif ($numberLeft == 6011)
            {
                $type='Discover';
                $shouldLength=16;
            }
            else
            {
                $sender->setErrorMessage("Credit Card is invalid");
                return false;
            }
        
            if ($this->type != $type)
            {
                $sender->setErrorMessage("Number entered does not match the card type selected");
                return false;
            }
            
            if ($numberLength != $shouldLength)
            {
                $sender->setErrorMessage("Credit Card is invalid");
                return false;
            }
            
            $sender->setErrorMessage('');
            
            return true;
        }
    }
     
    class Ccv2FieldValidator extends BasicFieldValidator
    {
        private $type = null;
        
        public function setType($value)
        {
            $this->type = $value;
        }
        
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param FieldModelBase $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if (!parent::validate($sender, $eventArgs)) return false;
            if ($sender->getIsRequired() === false) return true;
            
            $value = $sender->getValue();
            $valid = preg_match('/^\d+$/', $value);

            if ($valid == 0)
            {
                $sender->setErrorMessage("Invalid CCV2 format");
                return false;
            }
            else
            {
                if ($this->type == 'American Express')
                {
                    if (strlen($value) != 4){
                        $sender->setErrorMessage("CCV2 must be 4 digits long for American Express");
                        return false;
                    }
                }
                else
                {
                    if(strlen($value) != 3)
                    {
                        $sender->setErrorMessage("CCV2 must be 3 digits long");
                        return false;
                    }
                }
            }
        
            $sender->setErrorMessage('');

            return true;
        }
    }

    /**
     * Provides a basic implementation for an file upload field.
     * 
     * @package WorshipKitchen
     * @subpackage Model
     */
    class FileFieldValidator extends BasicFieldValidator
    {
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param FieldModelBase $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if ($sender->getValue instanceof PostedFile)
            {
                $postedFile = $sender->getValue();
    
                if ($postedFile->getErrorCode() != UPLOAD_ERR_OK)
                {
                    $sender->setErrorMessage($postedFile->getErrorMessage());
                    return false;
                }
            }
    
            $sender->setErrorMessage('');
            return true;
        }
    }
    
    /**
     * Provides an implemention of a Numeric validator
     *
     * @package Grandprix
     * @subpackage Model
     */
    class NumericFieldValidator extends BasicFieldValidator
    {
        protected $allowDecimals;
        protected $isMoney;
    
        public function __construct()
        {
            $this->allowDecimals = false;
            $this->isMoney = false;
        }
        
        /**
         * Determines whether decimals are allowed.
         * @return bool;
         */
        public function getAllowDecimals() { return $this->allowDecimals; }
        /**
         * Determines whether decimals are allowed.
         * @param bool $value;
         */
        public function setAllowDecimals($value) { $this->allowDecimals = $value; }
        
        /**
         * Determines whether values must be treated as money
         * @return bool;
         */
        public function getIsMoney() { return $this->isMoney; }
        /**
         * Determines whether values must be treated as money
         * @param bool $value;
         */
        public function setIsMoney($value) { $this->isMoney = $value; }
    
        /**
         * Validates the field taking into account its iRequired and isReadOnly properties.
         *
         * @param FieldModelBase $sender
         * @param mixed $eventArgs
         * @return bool
         */
        public function validate(&$sender, $eventArgs)
        {
            if (!parent::validate($sender, $eventArgs)) return false;
    
            $valueString = $sender->getValue();
            $valueString = str_replace(' ', '', $valueString);
            $valueString = str_replace(',', '', $valueString);
            if ($this->isMoney) $valueString = str_replace('$', '', $valueString);
            $valueString = trim($valueString);
    
            if ($valueString == '' && !$sender->getIsRequired())
            {
                $sender->setValue($valueString); // normalize
                $sender->setErrorMessage('');
                return true;
            }
    
            if (!is_numeric($valueString))
            {
                $sender->setErrorMessage('Formato de numero incorrecto');
                return false;
            }
    
            $numericValue = floatval($valueString);
            if ($this->isMoney) $numericValue = round($numericValue, 2);
            $sender->setValue($numericValue);
    
            $valueParts = explode('.', $valueString);
    
            if (!$this->allowDecimals && count($valueParts) > 1 && intval($valueParts[1]) !== 0)
            {
                $sender->setErrorMessage("No puede agregar decimales");
                return false;
            }
    
            $sender->setErrorMessage('');
            return true;
        }
    }
?>