<?php
    /**
     * Represents an exception.
     * The exception code constants are contained within this class.
     * Messages are automatically set, given the exception code.
     * An optional, user-defined message can also be set in the constructor of the exception.
     * 
     * @package Grandprix
     * @subpackage Exception
     */
    class GrandprixException extends Exception
    {
        #region ExceptionCodes

        const EX_KEYNOTFOUND = 902;
        const EX_NODBCONNECTION = 903;
        const EX_INVALIDPARAMMETHOD = 904;
        const EX_INVALIDPARAMTYPE = 905;
        const EX_PARAMETERNOTOPTIONAL = 906;
        const EX_DBQUERY = 907;
        const EX_CHECKOUT = 908;
        const EX_LOGIN = 909;
        const EX_GDATALOGIN = 910;
        const EX_MAILCONNECTION = 1401;
        const EX_MAILLOGIN = 1402;
        
        #endregion

        /**
         * Creates a new instance of this class
         *
         * @param int $exceptionCode
         * @param str $extendedMessage
         */
        public function __construct($exceptionCode, $extendedMessage = '')
        {
            $message = "";
            switch ($exceptionCode)
            {
                case self::EX_KEYNOTFOUND :
                    $message = "The key was not found.";
                    break;
                case self::EX_NODBCONNECTION :
                    $message = "Could not connect to the database.";
                    break;
                case self::EX_INVALIDPARAMTYPE :
                    $message = "The request parameter is of an invalid type.";
                    break;
                case self::EX_INVALIDPARAMMETHOD :
                    $message = "The request parameter was inappropriately passed in the request.";
                    break;
                case self::EX_PARAMETERNOTOPTIONAL :
                    $message = "The request parameter is required.";
                    break;
                case self::EX_DBQUERY :
                    $message = "Could not execute SQL query.";
                    break;
                case self::EX_CHECKOUT :
                    $message = "Check out operation throw an exception.";
                    break;
                case self::EX_LOGIN :
                    $message = "Login operation fails";
                    break;
                case self::EX_GDATALOGIN :
                    $message = "Login at Google fails";
                    break;
                default :
                    $message = "An unknown exception was thrown.";
                    break;
            }

            parent::__construct($message. " " . $extendedMessage, $exceptionCode);
        }

        public function __toString()
        {
            $code = $this->getCode();
            $message = $this->getMessage();
            $line = $this->getLine();
            return "{" . __CLASS__ . " [Code = " . $this->getCode() . ", Message = " . $this->getMessage() . ", File = " . $this->getFile() . ", Line = " . $this->getLine() . ", StackTrace = " . $this->getTraceAsString() . "]}";
        }

    }
    
    class StringEncodingHelper
    {
        private static function encodeStringRecursive(&$value, $charsetIn, $charsetOut)
        {
            if (is_array($value) === false && is_object($value) === false)
                $value = iconv($charsetIn, $charsetOut, $value);
                //$value = mb_convert_encoding($value, $charsetOut);
            else
                foreach ($value as $key => &$v)
                {
                    self::encodeStringRecursive($v, $charsetIn, $charsetOut);
                }
        }
        
        public static function UTF8decode(&$value)
        {
            self::encodeStringRecursive($value, "UTF-8", "ISO-8859-1//TRANSLIT");
        }
    
        public static function UTF8encode(&$value)
        {
            self::encodeStringRecursive($value, "ISO-8859-1//TRANSLIT", "UTF-8");
        }
    }

    //StringEncodingHelper::UTF8decode($_REQUEST);

    require_once "grandprix.settings.php";
    require_once "grandprix.network.php";
    require_once "grandprix.json.php";
    require_once "grandprix.logging.php";
    require_once "grandprix.data.php";
    require_once "grandprix.controller.php";
    require_once "grandprix.model.php";
    require_once "grandprix.validation.php";
    require_once "grandprix.view.form.php";
    require_once "grandprix.view.datasource.php";
    require_once "grandprix.mail.php";
    
    set_error_handler(array("LogManager", "handleError"));
    set_exception_handler(array("LogManager", "handleException"));
?>