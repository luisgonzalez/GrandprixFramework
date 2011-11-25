<?php
    /**
     * Singleton to compress HTML
     * and resources
     *
     * @package Grandprix
     * @subpackage Compression
     */
    class HttpCompressor
    {
        /**
         * Compress output to GZIP
         *
         * @param str output
         * @return str
         */
        public static function compressOutput($output)
        {
            if(strlen($output) >= 1000) 
            {
                $compressed_out = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
                $compressed_out .= substr(gzcompress($output, 2), 0, -4);
                $encoding = "gzip";
                 
                if(strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "x-gzip"))
                     $encoding = "x-gzip";
                 
                header("Content-Encoding: ". $encoding);
                return $compressed_out;
            }
             
            return $output;
        }
        
        /**
         * Compress current content
         * 
         */
        public static function compressContent()
        {
            if (strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") || 
                strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "x-gzip"))
            {
              if(function_exists("gzcompress"))
                ob_start("HttpCompressor::compressOutput");
              else
                ob_start ("ob_gzhandler");	
            }
            
            $offset = 6000000 * 60;
            header("Cache-Control: max-age=2592000");
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
        }
        
        /**
         * Compress a resource in server
         *
         * @param string $fileName
         */
        public static function compressResource($fileName)
        {
            $ext = "js";
            
            if (strstr($fileName, "css"))
                $ext = "css";
                
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            {
                if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == gmdate('D, d M Y H:i:s', filectime($fileName)) . " GMT")
                {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
                    exit();
                }
            }
            
            header("Content-Type: text/" . $ext);
            header("Last-modified: " .  gmdate('D, d M Y H:i:s', filectime($fileName)) . " GMT");
            HttpCompressor::compressContent();
            
            echo file_get_contents($fileName);
        }
    }
    
    if (isset($_REQUEST["resource"]))
    {
        $resourceId = $_REQUEST["resource"];
        
        switch ($resourceId)
        {
            case 'utilities':
                $resourceId = "../yui/utilities.js";
                break;
            case 'yuibutton':
                $resourceId = "../yui/button-min.js";
                break;
            case 'yuimenu':
                $resourceId = "../yui/menu-min.js";
                break;
            case 'yuicontainer':
                $resourceId = "../yui/container-min.js";
                break;
            default:
                exit();
        }
        
        HttpCompressor::compressResource($resourceId);
    }
?>