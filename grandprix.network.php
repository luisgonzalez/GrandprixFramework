<?php
    /**
     * FTP Client using libCURL
     *
     * @package Grandprix
     * @subpackage Network
     */
    class FtpClient
    {
        /**
         * Retrieves a file using libCURL
         *
         * @param string $url
         * @param string $username
         * @param string $password
         * @return string
         */
        public static function retrieveFile($url, $username = '', $password = '')
        {
            if (function_exists('curl_init') === false)
               throw new Exception("CURL mod is not installed");
            
            $curl_handle = curl_init();
            
            if ($username != '' && $password != '')
                curl_setopt($curl_handle, CURLOPT_USERPWD, $username . ':' . $password);
                
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_TIMEOUT, 32000);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 32000);
            
            $output = curl_exec($curl_handle);
            
            if ($output === false) return curl_error($curl_handle);
            
            curl_close($curl_handle);
            
            return trim($output);
        }
        
        /**
         * Puts a file using libCURL
         *
         * @param string $url
         * @param string $filePath
         * @param string $username
         * @param string $password
         * @return bool
         */
        public static function putFile($url, $filePath, $username = '', $password = '')
        {
            if (function_exists('curl_init') === false)
               throw new Exception("CURL mod is not installed");
            
            $fp = fopen($filePath, 'r');
            $curl_handle = curl_init();
            
            if ($username != '' && $password != '')
                curl_setopt($curl_handle, CURLOPT_USERPWD, $username . ':' . $password);
                
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_UPLOAD, 1);
            curl_setopt($curl_handle, CURLOPT_INFILE, $fp);
            curl_setopt($curl_handle, CURLOPT_INFILESIZE, filesize($filePath));
            
            $output = curl_exec($curl_handle);
            
            if ($output === false) return false;
            
            curl_close($curl_handle);
            
            return true;
        }
        
        /**
         * Deletes a file using libCURL
         *
         * @param string $url
         * @param string $fileName
         * @param string $username
         * @param string $password
         * @return bool
         */
        public static function deleteFile($url, $fileName, $username = '', $password = '')
        {
            $quote = array();
            $quote[] = "DELE $fileName";
            
            return self::executeQuote($url, $quote, $username, $password);
        }
        
        /**
         * Renames a file using libCURL
         *
         * @param string $url
         * @param string $fileName
         * @param string $newName
         * @param string $username
         * @param string $password
         * @return bool
         */
        public static function renameFile($url, $fileName, $newName, $username = '', $password = '')
        {
            $quote = array();
            $quote[] = "RNFR $fileName";
            $quote[] = "RNTO $newName";
            
            return self::executeQuote($url, $quote, $username, $password);
        }
        
        /**
         * Execute a quote using libCURL
         *
         * @param string $url
         * @param array $quote
         * @param string $username
         * @param string $password
         * @return bool
         */
        public static function executeQuote($url, $quote, $username = '', $password = '')
        {
            if (function_exists('curl_init') === false)
               throw new Exception("CURL mod is not installed");
            
            $curl_handle = curl_init();
            
            if ($username != '' && $password != '')
                curl_setopt($curl_handle, CURLOPT_USERPWD, $username . ':' . $password);
            
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_QUOTE, $quote); 
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            
            $output = curl_exec($curl_handle);
            
            if ($output === false) return false;
            
            curl_close($curl_handle);
            
            return true;
        }
    }
    
    /**
     * Wraps an item from the $_FILES array
     *
     * @package Grandprix
     * @subpackage Network
     */
    class PostedFile
    {
        private $__fileReference;
    
        /**
         * Creates a new instance of this class
         *
         * @param string $filesKey
         */
        public function __construct($filesKey)
        {
            if (array_key_exists($filesKey, $_FILES) === false)
                throw new Exception('$_FILES key ' . $filesKey . ' does not exist.');
            
            $this->__fileReference =& $_FILES[$filesKey];
        }
    
        /**
         * Gets the filename, without the path
         * Example: myfile.ext
         *
         * @return string
         */
        public function getFileName()
        {
            return basename($this->__fileReference['name']);
        }
    
        /**
         * Gets the filename, without the path or extension
         * Example: myfile
         *
         * @return string
         */
        public function getFileBaseName()
        {
            $fileName = $this->getFileName();
            $pathInfo = pathinfo($fileName);
            return $pathInfo['filename'];
        }
    
        /**
         * Gets the file extension of the uploaded file
         * Example: ext
         *
         * @return string
         */
        public function getFileExtension()
        {
            $fileName= $this->getFileName();
            $pathInfo = pathinfo($fileName);
            return $pathInfo['extension'];
        }
    
        /**
         * Gets the mime-type that the client set when the file was uploaded
         * Example: image/gif
         *
         * @return string
         */
        public function getMimeType()
        {
            return $this->__fileReference['type'];
        }
    
        /**
         * Gets the size, in bytes of the array.
         *
         * @return int
         */
        public function getSize()
        {
            return (int) $this->__fileReference['size'];
        }
    
        /**
         * Gets the full path in the server to the uploaded file
         *
         * @return string
         */
        public function getTempFileName()
        {
            return $this->__fileReference['tmp_name'];
        }
    
        /**
         * Gets whether file was uploaded and can be correcly found in the server.
         *
         * @return bool
         */
        public function isUploaded()
        {
            return is_uploaded_file($this->getTempFileName());
        }
    
        /**
         * Gets the entire contents of the uploaded filed
         *
         * @return string
         */
        public function readAll()
        {
            $pathFileName = $this->getTempFileName();
            return file_get_contents($pathFileName);
        }
    
        /**
         * Gets the error code of the file upload result
         *
         * @return int
         */
        public function getErrorCode()
        {
            return $this->__fileReference['error'];
        }
    
        /**
         * Gets a user-friendly message based on the error code
         *
         * @return string
         */
        public function getErrorMessage()
        {
            $errorCode = $this->getErrorCode();
            
            switch ($errorCode)
            {
                case UPLOAD_ERR_CANT_WRITE:
                    return 'Error al escribir en disco.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    return 'Extension no permitida.';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                case UPLOAD_ERR_INI_SIZE:
                    return 'El archivo excede el tamaño aceptado.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    return 'No se subio ningun archivo.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    return 'No hay directorio temporal.';
                    break;
                case UPLOAD_ERR_OK:
                    return 'OK';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    return 'El archivo solo fue subido parcialmente.';
                    break;
                default:
                    return 'Error al escribir en disco.';
                    break;
            }
        }
    }
?>