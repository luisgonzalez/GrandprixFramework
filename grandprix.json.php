<?php
    define('SERVICES_JSON_SLICE',   1);
    define('SERVICES_JSON_IN_STR',  2);
    define('SERVICES_JSON_IN_ARR',  3);
    define('SERVICES_JSON_IN_OBJ',  4);
    define('SERVICES_JSON_IN_CMT', 5);
    define('SERVICES_JSON_LOOSE_TYPE', 16);
    define('SERVICES_JSON_SUPPRESS_ERRORS', 32);
    
    /**
     * Encoder for JSON
     * 
     * @package Grandprix
     * @subpackage Utils
     */
    class JSON
    {      
       private static function name_value($name, $value)
       {
          $encoded_value = self::encode($value);
          return self::encode(strval($name)) . ':' . $encoded_value;
       }
     
       private static function reduce_string($str)
       {
         $str = preg_replace(array(
                '#^\s*//(.+)$#m',
                '#^\s*/\*(.+)\*/#Us',
                '#/\*(.+)\*/\s*$#Us'), '', $str);
 
          return trim($str);
       }
     
        /**
         * Encodes a variable to JSON format
         *
         * @param object $var
         * @return string
         */
        public static function encode($var)
        {
          switch (gettype($var))
          {
             case 'boolean':
                 return $var ? 'true' : 'false';
             case 'NULL':
                 return 'null';
             case 'integer':
                 return (int) $var;
             case 'double':
             case 'float':
                 return (float) $var;
             case 'string':
                 $ascii = '';
                 $strlen_var = strlen($var);
 
                 for ($c = 0; $c < $strlen_var; ++$c) {
 
                     $ord_var_c = ord($var{$c});
 
                     switch (true) {
                         case $ord_var_c == 0x08:
                             $ascii .= '\b';
                             break;
                         case $ord_var_c == 0x09:
                             $ascii .= '\t';
                             break;
                         case $ord_var_c == 0x0A:
                             $ascii .= '\n';
                             break;
                         case $ord_var_c == 0x0C:
                             $ascii .= '\f';
                             break;
                         case $ord_var_c == 0x0D:
                             $ascii .= '\r';
                             break;
 
                         case $ord_var_c == 0x22:
                         case $ord_var_c == 0x2F:
                         case $ord_var_c == 0x5C:
                             // double quote, slash, slosh
                             $ascii .= '\\'.$var{$c};
                             break;
 
                         case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                             // characters U-00000000 - U-0000007F (same as ASCII)
                             $ascii .= $var{$c};
                             break;
                     }
                 }
 
                 return '"'.$ascii.'"';
 
             case 'array':
                 if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                     $properties = array_map(array('self', 'name_value'),
                                             array_keys($var),
                                             array_values($var));
 
                     return '{' . join(',', $properties) . '}';
                 }
 
                 // treat it like a regular array
                 $elements = array_map(array('self', 'encode'), $var);
 
                 return '[' . join(',', $elements) . ']';
 
             case 'object':
                 $vars = get_object_vars($var);
 
                 $properties = array_map(array('self', 'name_value'),
                                         array_keys($vars),
                                         array_values($vars));
 
                 return '{' . join(',', $properties) . '}';
 
             default:
                 return 'null';
         }
       }
       
       public static function decode($str, $assoc = false)
       {
          $str = self::reduce_string($str);
          
          switch (strtolower($str)) {
             case 'true':
                return true;
             case 'false':
                return false;
             case 'null':
                return null;
             default:
                $m = array();
         
                if (is_numeric($str))
                {
                  return ((float)$str == (integer)$str) ? (integer)$str : (float)$str;
                }
                elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2])
                {
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);
         
                    for ($c = 0; $c < $strlen_chrs; ++$c)
                    {
                      $substr_chrs_c_2 = substr($chrs, $c, 2);
                      $ord_chrs_c = ord($chrs{$c});
         
                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;
         
                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;
         
                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;
         
                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;
         
                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;
         
                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;
         
                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;
         
                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;
                        }
                    }
      
                    return $utf8;
                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                  // array, or object notation
       
                  if ($str{0} == '[') {
                      $stk = array(SERVICES_JSON_IN_ARR);
                      $arr = array();
                  } else {
                      if ($assoc) {
                          $stk = array(SERVICES_JSON_IN_OBJ);
                          $obj = array();
                      } else {
                          $stk = array(SERVICES_JSON_IN_OBJ);
                          $obj = new stdClass();
                      }
                  }
       
                  array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                                         'where' => 0,
                                         'delim' => false));
       
                  $chrs = substr($str, 1, -1);
                  $chrs = self::reduce_string($chrs);
       
                  if ($chrs == '') {
                      if (reset($stk) == SERVICES_JSON_IN_ARR) {
                          return $arr;
       
                      } else {
                          return $obj;
       
                      }
                  }
       
                  $strlen_chrs = strlen($chrs);
       
                  for ($c = 0; $c <= $strlen_chrs; ++$c)
                  {
                   $top = end($stk);
                   $substr_chrs_c_2 = substr($chrs, $c, 2);
    
                   if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE)))
                   {
                       $slice = substr($chrs, $top['where'], ($c - $top['where']));
                       array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                   
                       if (reset($stk) == SERVICES_JSON_IN_ARR) {
                           array_push($arr, self::decode($slice, $assoc));
                       } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                           $parts = array();
                           
                           if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                               // "name":value pair
                               $key = self::decode($parts[1], $assoc);
                               $val = self::decode($parts[2], $assoc);
    
                               if ($assoc) {
                                   $obj[$key] = $val;
                               } else {
                                   $obj->$key = $val;
                               }
                           } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                               // name:value pair, where name is unquoted
                               $key = $parts[1];
                               $val = self::decode($parts[2], $assoc);
    
                               if ($assoc) {
                                   $obj[$key] = $val;
                               } else {
                                   $obj->$key = $val;
                               }
                           }
                       }
                   } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
                       // found a quote, and we are not inside a string
                       array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                   } elseif (($chrs{$c} == $top['delim']) &&
                            ($top['what'] == SERVICES_JSON_IN_STR) &&
                            (($chrs{$c - 1} != '\\') ||
                            ($chrs{$c - 1} == '\\' && $chrs{$c - 2} == '\\'))) {
                       // found a quote, we're in a string, and it's not escaped
                       array_pop($stk);
                   } elseif (($chrs{$c} == '[') &&
                            in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                       // found a left-bracket, and we are in an array, object, or slice
                       array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                   } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
                       // found a right-bracket, and we're in an array
                       array_pop($stk);
                   } elseif (($chrs{$c} == '{') &&
                            in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                       // found a left-brace, and we are in an array, object, or slice
                       array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                   } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
                       // found a right-brace, and we're in an object
                       array_pop($stk);
                   } elseif (($substr_chrs_c_2 == '/*') &&
                            in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                       // found a comment start, and we are in an array, object, or slice
                       array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                       $c++;
                   } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
                       // found a comment end, and we're in one now
                       array_pop($stk);
                       $c++;
    
                       for ($i = $top['where']; $i <= $c; ++$i)
                           $chrs = substr_replace($chrs, ' ', $i, 1);
                   }
                }
     
                if (reset($stk) == SERVICES_JSON_IN_ARR)
                    return $arr;
                elseif (reset($stk) == SERVICES_JSON_IN_OBJ)
                    return $obj;
             }
          }
       }
    }
?>