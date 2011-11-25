<?php
define('NUM_BIG_BLOCK_DEPOT_BLOCKS_POS', 0x2c);
define('SMALL_BLOCK_DEPOT_BLOCK_POS', 0x3c);
define('ROOT_START_BLOCK_POS', 0x30);
define('BIG_BLOCK_SIZE', 0x200);
define('SMALL_BLOCK_SIZE', 0x40);
define('EXTENSION_BLOCK_POS', 0x44);
define('NUM_EXTENSION_BLOCK_POS', 0x48);
define('PROPERTY_STORAGE_BLOCK_SIZE', 0x80);
define('BIG_BLOCK_DEPOT_BLOCKS_POS', 0x4c);
define('SMALL_BLOCK_THRESHOLD', 0x1000);
define('SIZE_OF_NAME_POS', 0x40);
define('TYPE_POS', 0x42);
define('START_BLOCK_POS', 0x74);
define('SIZE_POS', 0x78);
define('IDENTIFIER_OLE', pack("CCCCCCCC",0xd0,0xcf,0x11,0xe0,0xa1,0xb1,0x1a,0xe1));

/**
 * Represents a Excel OLE Reader
 *
 * @package    Grandprix
 * @subpackage Excel
 */
class OLERead
{
    private $data = '';    
    
    private function GetInt4d($data, $pos)
    {
        $value = ord($data[$pos]) | (ord($data[$pos+1])	<< 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24);
        
        if ($value>=4294967294)
        {
            $value=-2;
        }
        return $value;
    }
    
    /**
     * Reads a file
     *
     * @param string $sFileName
     */
    public function read($sFileName)
    {
    	if(!is_readable($sFileName))
        {
    		$this->error = 1;
    		return false;
    	}
    	
    	$this->data = @file_get_contents($sFileName);
        
    	if (!$this->data)
        { 
    		$this->error = 1; 
    		return false; 
   		}
        
   		if (substr($this->data, 0, 8) != IDENTIFIER_OLE)
        {
            $this->error = 1; 
    		return false; 
   		}
        
        $this->numBigBlockDepotBlocks = $this->GetInt4d($this->data, NUM_BIG_BLOCK_DEPOT_BLOCKS_POS);
        $this->sbdStartBlock = $this->GetInt4d($this->data, SMALL_BLOCK_DEPOT_BLOCK_POS);
        $this->rootStartBlock = $this->GetInt4d($this->data, ROOT_START_BLOCK_POS);
        $this->extensionBlock = $this->GetInt4d($this->data, EXTENSION_BLOCK_POS);
        $this->numExtensionBlocks = $this->GetInt4d($this->data, NUM_EXTENSION_BLOCK_POS);

        $bigBlockDepotBlocks = array();
        $pos = BIG_BLOCK_DEPOT_BLOCKS_POS;
        $bbdBlocks = $this->numBigBlockDepotBlocks;
        
        if ($this->numExtensionBlocks != 0) {
            $bbdBlocks = (BIG_BLOCK_SIZE - BIG_BLOCK_DEPOT_BLOCKS_POS)/4; 
        }
        
        for ($i = 0; $i < $bbdBlocks; $i++)
        {
            $bigBlockDepotBlocks[$i] = $this->GetInt4d($this->data, $pos);
            $pos += 4;
        }
        
        for ($j = 0; $j < $this->numExtensionBlocks; $j++)
        {
            $pos = ($this->extensionBlock + 1) * BIG_BLOCK_SIZE;
            $blocksToRead = min($this->numBigBlockDepotBlocks - $bbdBlocks, BIG_BLOCK_SIZE / 4 - 1);

            for ($i = $bbdBlocks; $i < $bbdBlocks + $blocksToRead; $i++)
            {
                $bigBlockDepotBlocks[$i] = $this->GetInt4d($this->data, $pos);
                $pos += 4;
            }   

            $bbdBlocks += $blocksToRead;
            
            if ($bbdBlocks < $this->numBigBlockDepotBlocks)
                $this->extensionBlock = $this->GetInt4d($this->data, $pos);
        }

        $pos = 0;
        $index = 0;
        $this->bigBlockChain = array();
        
        for ($i = 0; $i < $this->numBigBlockDepotBlocks; $i++)
        {
            $pos = ($bigBlockDepotBlocks[$i] + 1) * BIG_BLOCK_SIZE;

            for ($j = 0 ; $j < BIG_BLOCK_SIZE / 4; $j++)
            {
                $this->bigBlockChain[$index] = $this->GetInt4d($this->data, $pos);
                $pos += 4 ;
                $index++;
            }
        }

        $pos = 0;
	    $index = 0;
	    $sbdBlock = $this->sbdStartBlock;
	    $this->smallBlockChain = array();
	
	    while ($sbdBlock != -2)
        {
            $pos = ($sbdBlock + 1) * BIG_BLOCK_SIZE;
        
            for ($j = 0; $j < BIG_BLOCK_SIZE / 4; $j++)
            {
                $this->smallBlockChain[$index] = $this->GetInt4d($this->data, $pos);
                $pos += 4;
                $index++;
            }
      
            $sbdBlock = $this->bigBlockChain[$sbdBlock];
	    }

        $block = $this->rootStartBlock;
        $pos = 0;
        $this->entry = $this->__readData($block);

        $this->__readPropertySets();
        
        return true;
    }
    
    function __readData($bl)
    {
        $block = $bl;
        $pos = 0;
        $data = '';
        
        while ($block != -2)
        {
            $pos = ($block + 1) * BIG_BLOCK_SIZE;
            $data = $data.substr($this->data, $pos, BIG_BLOCK_SIZE);
            $block = $this->bigBlockChain[$block];
        }
        
        return $data;
    }
        
    function __readPropertySets()
    {
        $offset = 0;
        
        while ($offset < strlen($this->entry))
        {
            $d = substr($this->entry, $offset, PROPERTY_STORAGE_BLOCK_SIZE);
          
            $nameSize = ord($d[SIZE_OF_NAME_POS]) | (ord($d[SIZE_OF_NAME_POS+1]) << 8);
            
            $type = ord($d[TYPE_POS]);
        
            $startBlock = $this->GetInt4d($d, START_BLOCK_POS);
            $size = $this->GetInt4d($d, SIZE_POS);
            $name = '';
            
            for ($i = 0; $i < $nameSize ; $i++)
                $name .= $d[$i];
            
            $name = str_replace("\x00", "", $name);
            
            $this->props[] = array ('name' => $name, 
                'type' => $type,
                'startBlock' => $startBlock,
                'size' => $size);

            if (($name == "Workbook") || ($name == "Book"))
                $this->wrkbook = count($this->props) - 1;

            if ($name == "Root Entry")
                $this->rootentry = count($this->props) - 1;
            
            $offset += PROPERTY_STORAGE_BLOCK_SIZE;
        }   
        
    }

    function getWorkBook()
    {
    	if ($this->props[$this->wrkbook]['size'] < SMALL_BLOCK_THRESHOLD)
        {
			$rootdata = $this->__readData($this->props[$this->rootentry]['startBlock']);
	        
			$streamData = '';
	        $block = $this->props[$this->wrkbook]['startBlock'];
	        $pos = 0;
            
		    while ($block != -2)
            {
                $pos = $block * SMALL_BLOCK_SIZE;
                $streamData .= substr($rootdata, $pos, SMALL_BLOCK_SIZE);
                $block = $this->smallBlockChain[$block];
		    }
			
		    return $streamData;
    	}
        else
        {
	        $numBlocks = $this->props[$this->wrkbook]['size'] / BIG_BLOCK_SIZE;
            
	        if ($this->props[$this->wrkbook]['size'] % BIG_BLOCK_SIZE != 0)
                $numBlocks++;
	        
	        if ($numBlocks == 0) return '';
	        
	        $streamData = '';
	        $block = $this->props[$this->wrkbook]['startBlock'];
	        
	        $pos = 0;
	        
	        while ($block != -2)
            {
                $pos = ($block + 1) * BIG_BLOCK_SIZE;
                $streamData .= substr($this->data, $pos, BIG_BLOCK_SIZE);
                $block = $this->bigBlockChain[$block];
	        }   
	        
	        return $streamData;
    	}
    }
}

define('SPREADSHEET_EXCEL_READER_BIFF8',             0x600);
define('SPREADSHEET_EXCEL_READER_BIFF7',             0x500);
define('SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS',   0x5);
define('SPREADSHEET_EXCEL_READER_WORKSHEET',         0x10);

define('SPREADSHEET_EXCEL_READER_TYPE_BOF',          0x809);
define('SPREADSHEET_EXCEL_READER_TYPE_EOF',          0x0a);
define('SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET',   0x85);
define('SPREADSHEET_EXCEL_READER_TYPE_DIMENSION',    0x200);
define('SPREADSHEET_EXCEL_READER_TYPE_ROW',          0x208);
define('SPREADSHEET_EXCEL_READER_TYPE_DBCELL',       0xd7);
define('SPREADSHEET_EXCEL_READER_TYPE_FILEPASS',     0x2f);
define('SPREADSHEET_EXCEL_READER_TYPE_NOTE',         0x1c);
define('SPREADSHEET_EXCEL_READER_TYPE_TXO',          0x1b6);
define('SPREADSHEET_EXCEL_READER_TYPE_RK',           0x7e);
define('SPREADSHEET_EXCEL_READER_TYPE_RK2',          0x27e);
define('SPREADSHEET_EXCEL_READER_TYPE_MULRK',        0xbd);
define('SPREADSHEET_EXCEL_READER_TYPE_MULBLANK',     0xbe);
define('SPREADSHEET_EXCEL_READER_TYPE_INDEX',        0x20b);
define('SPREADSHEET_EXCEL_READER_TYPE_SST',          0xfc);
define('SPREADSHEET_EXCEL_READER_TYPE_EXTSST',       0xff);
define('SPREADSHEET_EXCEL_READER_TYPE_CONTINUE',     0x3c);
define('SPREADSHEET_EXCEL_READER_TYPE_LABEL',        0x204);
define('SPREADSHEET_EXCEL_READER_TYPE_LABELSST',     0xfd);
define('SPREADSHEET_EXCEL_READER_TYPE_NUMBER',       0x203);
define('SPREADSHEET_EXCEL_READER_TYPE_NAME',         0x18);
define('SPREADSHEET_EXCEL_READER_TYPE_ARRAY',        0x221);
define('SPREADSHEET_EXCEL_READER_TYPE_STRING',       0x207);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA',      0x406);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMULA2',     0x6);
define('SPREADSHEET_EXCEL_READER_TYPE_FORMAT',       0x41e);
define('SPREADSHEET_EXCEL_READER_TYPE_XF',           0xe0);
define('SPREADSHEET_EXCEL_READER_TYPE_BOOLERR',      0x205);
define('SPREADSHEET_EXCEL_READER_TYPE_UNKNOWN',      0xffff);
define('SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR', 0x22);
define('SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS',  0xE5);

define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS' ,    25569);
define('SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904', 24107);
define('SPREADSHEET_EXCEL_READER_MSINADAY',          86400);

define('SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT',    "%s");

/**
 * Represents a Excel Reader
 *
 * @package    Grandprix
 * @subpackage Excel
 */
class Spreadsheet_Excel_Reader
{
    /**
     * Array of worksheets found
     *
     * @var array
     */
    public $boundsheets = array();

    /**
     * Array of format records found
     * 
     * @var array
     */
    public $formatRecords = array();
    public $sst = array();

    /**
     * Array of worksheets
     *
     * The data is stored in 'cells' and the meta-data is stored in an array
     * called 'cellsInfo'
     *
     * Example:
     *
     * $sheets  -->  'cells'  -->  row --> column --> Interpreted value
     *          -->  'cellsInfo' --> row --> column --> 'type' - Can be 'date', 'number', or 'unknown'
     *                                            --> 'raw' - The raw data that Excel stores for that data cell
     *
     * @var array
     */
    public $sheets = array();

    /**
     * The data returned by OLE
     *
     * @var string
     */
    public $data;

    /**
     * OLE object for reading the file
     *
     * @var OLERead object
     */
    private $_ole;

    private $_defaultEncoding;
    private $_defaultFormat = SPREADSHEET_EXCEL_READER_DEF_NUM_FORMAT;
    private $_columnsFormat = array();
    private $_rowoffset = 1;
    private $_coloffset = 1;

    /**
     * List of default date formats used by Excel
     *
     * @var array
     */
    public $dateFormats = array (
        0xe => "d/m/Y",
        0xf => "d-M-Y",
        0x10 => "d-M",
        0x11 => "M-Y",
        0x12 => "h:i a",
        0x13 => "h:i:s a",
        0x14 => "H:i",
        0x15 => "H:i:s",
        0x16 => "d/m/Y H:i",
        0x2d => "i:s",
        0x2e => "H:i:s",
        0x2f => "i:s.S");

    /**
     * Default number formats used by Excel
     *
     * @var array
     */
    public $numberFormats = array(
        0x1 => "%1.0f",     // "0"
        0x2 => "%1.2f",     // "0.00",
        0x3 => "%1.0f",     //"#,##0",
        0x4 => "%1.2f",     //"#,##0.00",
        0x5 => "%1.0f",     /*"$#,##0;($#,##0)",*/
        0x6 => '$%1.0f',    /*"$#,##0;($#,##0)",*/
        0x7 => '$%1.2f',    //"$#,##0.00;($#,##0.00)",
        0x8 => '$%1.2f',    //"$#,##0.00;($#,##0.00)",
        0x9 => '%1.0f%%',   // "0%"
        0xa => '%1.2f%%',   // "0.00%"
        0xb => '%1.2f',     // 0.00E00",
        0x25 => '%1.0f',    // "#,##0;(#,##0)",
        0x26 => '%1.0f',    //"#,##0;(#,##0)",
        0x27 => '%1.2f',    //"#,##0.00;(#,##0.00)",
        0x28 => '%1.2f',    //"#,##0.00;(#,##0.00)",
        0x29 => '%1.0f',    //"#,##0;(#,##0)",
        0x2a => '$%1.0f',   //"$#,##0;($#,##0)",
        0x2b => '%1.2f',    //"#,##0.00;(#,##0.00)",
        0x2c => '$%1.2f',   //"$#,##0.00;($#,##0.00)",
        0x30 => '%1.0f');   //"##0.0E0";

    /**
     * Constructor
     *
     * Some basic initialisation
     */ 
    public function __construct()
    {
        $this->_ole = new OLERead();
        $this->setUTFEncoder('iconv');
    }

    /**
     * Sets the encoding method
     *
     * @param string Encoding to use
     */
    public function setOutputEncoding($encoding)
    {
        $this->_defaultEncoding = $encoding;
    }

    /**
     *  $encoder = 'iconv' or 'mb'
     *  set iconv if you would like use 'iconv' for encode UTF-16LE to your encoding
     *  set mb if you would like use 'mb_convert_encoding' for encode UTF-16LE to your encoding
     *
     * @param string Encoding type to use.  Either 'iconv' or 'mb'
     */
    public function setUTFEncoder($encoder = 'iconv')
    {
        $this->_encoderFunction = '';

        if ($encoder == 'iconv') {
            $this->_encoderFunction = function_exists('iconv') ? 'iconv' : '';
        } elseif ($encoder == 'mb') {
            $this->_encoderFunction = function_exists('mb_convert_encoding') ?
                                      'mb_convert_encoding' :
                                      '';
        }
    }

    /**
     * Sets rows cols offset
     *
     * @param offset
     */
    public function setRowColOffset($iOffset)
    {
        $this->_rowoffset = $iOffset;
        $this->_coloffset = $iOffset;
    }

    /**
     * Set the default number format
     *
     * @param Default format
     */
    public function setDefaultFormat($sFormat)
    {
        $this->_defaultFormat = $sFormat;
    }

    /**
     * Force a column to use a certain format
     *
     * @param integer Column number
     * @param string Format
     */
    public function setColumnFormat($column, $sFormat)
    {
        $this->_columnsFormat[$column] = $sFormat;
    }

    /**
     * Read the spreadsheet file using OLE, then parse
     *
     * @param filename
     */
    public function read($sFileName)
    {
        $res = $this->_ole->read($sFileName);
        
        if ($res === false)
        {
            if($this->_ole->error == 1)
                die('The filename ' . $sFileName . ' is not readable');
        }

        $this->data = $this->_ole->getWorkBook();

        return $this->_parse();
    }

    /**
     * Parses a workbook
     *
     * @return bool
     */
    private function _parse()
    {
        $pos = 0;

        $code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
        $length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

        $version = ord($this->data[$pos + 4]) | ord($this->data[$pos + 5])<<8;
        $substreamType = ord($this->data[$pos + 6]) | ord($this->data[$pos + 7])<<8;
        
        if (($version != SPREADSHEET_EXCEL_READER_BIFF8) && ($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
            return false;
        }

        if ($substreamType != SPREADSHEET_EXCEL_READER_WORKBOOKGLOBALS){
            return false;
        }

        $pos += $length + 4;

        $code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
        $length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;

        while ($code != SPREADSHEET_EXCEL_READER_TYPE_EOF)
        {
            switch ($code)
            {
                case SPREADSHEET_EXCEL_READER_TYPE_SST:
                    $spos = $pos + 4;
                    $limitpos = $spos + $length;
                    $uniqueStrings = $this->_GetInt4d($this->data, $spos+4);
                    $spos += 8;
                    
                    for ($i = 0; $i < $uniqueStrings; $i++)
                    {
                        if ($spos == $limitpos)
                        {
                            $opcode = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                            $conlength = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                            
                            if ($opcode != 0x3c)
                                return -1;
                            
                            $spos += 4;
                            $limitpos = $spos + $conlength;
                        }
                        
                        $numChars = ord($this->data[$spos]) | (ord($this->data[$spos+1]) << 8);
                        $spos += 2;
                        $optionFlags = ord($this->data[$spos]);
                        $spos++;
                        $asciiEncoding = (($optionFlags & 0x01) == 0) ;
                        $extendedString = ( ($optionFlags & 0x04) != 0);

                        $richString = ( ($optionFlags & 0x08) != 0);

                        if ($richString)
                        {
                            $formattingRuns = ord($this->data[$spos]) | (ord($this->data[$spos+1]) << 8);
                            $spos += 2;
                        }

                        if ($extendedString) {
                          // Read in cchExtRst
                          $extendedRunLength = $this->_GetInt4d($this->data, $spos);
                          $spos += 4;
                        }

                        $len = ($asciiEncoding)? $numChars : $numChars*2;
                        if ($spos + $len < $limitpos) {
                            $retstr = substr($this->data, $spos, $len);
                            $spos += $len;
                        }
                        else
                        {
                            // found countinue
                            $retstr = substr($this->data, $spos, $limitpos - $spos);
                            $bytesRead = $limitpos - $spos;
                            $charsLeft = $numChars - (($asciiEncoding) ? $bytesRead : ($bytesRead / 2));
                            $spos = $limitpos;

                             while ($charsLeft > 0){
                                $opcode = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                                $conlength = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                                        if ($opcode != 0x3c) {
                                                return -1;
                                        }
                                $spos += 4;
                                $limitpos = $spos + $conlength;
                                $option = ord($this->data[$spos]);
                                $spos += 1;
                                  if ($asciiEncoding && ($option == 0)) {
                                                $len = min($charsLeft, $limitpos - $spos); // min($charsLeft, $conlength);
                                    $retstr .= substr($this->data, $spos, $len);
                                    $charsLeft -= $len;
                                    $asciiEncoding = true;
                                  }elseif (!$asciiEncoding && ($option != 0)){
                                                $len = min($charsLeft * 2, $limitpos - $spos); // min($charsLeft, $conlength);
                                    $retstr .= substr($this->data, $spos, $len);
                                    $charsLeft -= $len/2;
                                    $asciiEncoding = false;
                                  }elseif (!$asciiEncoding && ($option == 0)) {
                                        $len = min($charsLeft, $limitpos - $spos); // min($charsLeft, $conlength);
                                        for ($j = 0; $j < $len; $j++) {
                                 $retstr .= $this->data[$spos + $j].chr(0);
                                }
                                
                                $charsLeft -= $len;
                                    $asciiEncoding = false;
                                      }else{
                                $newstr = '';
                                        for ($j = 0; $j < strlen($retstr); $j++) {
                                          $newstr = $retstr[$j].chr(0);
                                        }
                                        $retstr = $newstr;
                                                    $len = min($charsLeft * 2, $limitpos - $spos); // min($charsLeft, $conlength);
                                        $retstr .= substr($this->data, $spos, $len);
                                        $charsLeft -= $len/2;
                                        $asciiEncoding = false;
                                      }
                              $spos += $len;

                             }
                        }
                        
                        $retstr = ($asciiEncoding) ? $retstr : $this->_encodeUTF16($retstr);
                        
                        if ($richString)
                            $spos += 4 * $formattingRuns;

                        if ($extendedString)
                            $spos += $extendedRunLength;
                        
                        $this->sst[]=$retstr;
                    }
                    break;

                case SPREADSHEET_EXCEL_READER_TYPE_FILEPASS:
                    return false;
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NAME:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMAT:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_XF:
                    $indexCode = ord($this->data[$pos+6]) | ord($this->data[$pos+7]) << 8;
                    
                    if (array_key_exists($indexCode, $this->dateFormats))
                    {
                        $this->formatRecords['xfrecords'][] = array('type' => 'date',
                                'format' => $this->dateFormats[$indexCode]);
                    }
                    elseif (array_key_exists($indexCode, $this->numberFormats))
                    {
                        $this->formatRecords['xfrecords'][] = array('type' => 'number',
                                'format' => $this->numberFormats[$indexCode]);
                    }
                    else
                    {
                        $isdate = FALSE;
                        
                        if ($indexCode > 0)
                        {
                            if (isset($this->formatRecords[$indexCode]))
                            {
                                $formatstr = $this->formatRecords[$indexCode];
                                if (preg_match("/[^hmsday\/\-:\s]/i", $formatstr) == 0) { // found day and time format
                                $isdate = TRUE;
                                $formatstr = str_replace('mm', 'i', $formatstr);
                                $formatstr = str_replace('h', 'H', $formatstr);
                                }
                            }
                        }

                        if ($isdate)
                        {
                            $this->formatRecords['xfrecords'][] = array('type' => 'date',
                                    'format' => $formatstr);
                        }else{
                            $this->formatRecords['xfrecords'][] = array('type' => 'other',
                                    'format' => '',
                                    'code' => $indexCode);
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NINETEENFOUR:
                    $this->nineteenFour = (ord($this->data[$pos+4]) == 1);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOUNDSHEET:
                    $rec_offset = $this->_GetInt4d($this->data, $pos+4);
                    $rec_typeFlag = ord($this->data[$pos+8]);
                    $rec_visibilityFlag = ord($this->data[$pos+9]);
                    $rec_length = ord($this->data[$pos+10]);

                    if ($version == SPREADSHEET_EXCEL_READER_BIFF8)
                    {
                        $chartype =  ord($this->data[$pos+11]);
                        
                        if ($chartype == 0)
                        {
                            $rec_name    = substr($this->data, $pos+12, $rec_length);
                        } else {
                            $rec_name    = $this->_encodeUTF16(substr($this->data, $pos+12, $rec_length*2));
                        }
                    }
                    elseif ($version == SPREADSHEET_EXCEL_READER_BIFF7)
                    {
                        $rec_name    = substr($this->data, $pos+11, $rec_length);
                    }
                    
                    $this->boundsheets[] = array('name'=>$rec_name,
                                                 'offset'=>$rec_offset);

                    break;
            }

            $pos += $length + 4;
            $code = ord($this->data[$pos]) | ord($this->data[$pos+1])<<8;
            $length = ord($this->data[$pos+2]) | ord($this->data[$pos+3])<<8;
        }

        foreach ($this->boundsheets as $key=>$val)
        {
            $this->sn = $key;
            $this->_parsesheet($val['offset']);
        }
        
        return true;
    }

    /**
     * Parse a worksheet
     *
     * @param todo
     * @todo fix return codes
     */
    private function _parsesheet($spos)
    {
        $cont = true;
        // read BOF
        $code = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
        $length = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;

        $version = ord($this->data[$spos + 4]) | ord($this->data[$spos + 5])<<8;
        $substreamType = ord($this->data[$spos + 6]) | ord($this->data[$spos + 7])<<8;

        if (($version != SPREADSHEET_EXCEL_READER_BIFF8) && ($version != SPREADSHEET_EXCEL_READER_BIFF7)) {
            return -1;
        }

        if ($substreamType != SPREADSHEET_EXCEL_READER_WORKSHEET){
            return -2;
        }
        
        $spos += $length + 4;
        
        while($cont)
        {
            $lowcode = ord($this->data[$spos]);
            if ($lowcode == SPREADSHEET_EXCEL_READER_TYPE_EOF) break;
            $code = $lowcode | ord($this->data[$spos+1])<<8;
            $length = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
            $spos += 4;
            $this->sheets[$this->sn]['maxrow'] = $this->_rowoffset - 1;
            $this->sheets[$this->sn]['maxcol'] = $this->_coloffset - 1;
            
            unset($this->rectype);
            $this->multiplier = 1; // need for format with %
            
            switch ($code)
            {
                case SPREADSHEET_EXCEL_READER_TYPE_DIMENSION:
                    if (!isset($this->numRows))
                    {
                        if (($length == 10) ||  ($version == SPREADSHEET_EXCEL_READER_BIFF7)){
                            $this->sheets[$this->sn]['numRows'] = ord($this->data[$spos+2]) | ord($this->data[$spos+3]) << 8;
                            $this->sheets[$this->sn]['numCols'] = ord($this->data[$spos+6]) | ord($this->data[$spos+7]) << 8;
                        } else {
                            $this->sheets[$this->sn]['numRows'] = ord($this->data[$spos+4]) | ord($this->data[$spos+5]) << 8;
                            $this->sheets[$this->sn]['numCols'] = ord($this->data[$spos+10]) | ord($this->data[$spos+11]) << 8;
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MERGEDCELLS:
                    $cellRanges = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    for ($i = 0; $i < $cellRanges; $i++) {
                        $fr =  ord($this->data[$spos + 8*$i + 2]) | ord($this->data[$spos + 8*$i + 3])<<8;
                        $lr =  ord($this->data[$spos + 8*$i + 4]) | ord($this->data[$spos + 8*$i + 5])<<8;
                        $fc =  ord($this->data[$spos + 8*$i + 6]) | ord($this->data[$spos + 8*$i + 7])<<8;
                        $lc =  ord($this->data[$spos + 8*$i + 8]) | ord($this->data[$spos + 8*$i + 9])<<8;
                        
                        if ($lr - $fr > 0) {
                            $this->sheets[$this->sn]['cellsInfo'][$fr+1][$fc+1]['rowspan'] = $lr - $fr + 1;
                        }
                        if ($lc - $fc > 0) {
                            $this->sheets[$this->sn]['cellsInfo'][$fr+1][$fc+1]['colspan'] = $lc - $fc + 1;
                        }
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_RK:
                case SPREADSHEET_EXCEL_READER_TYPE_RK2:
                    $row = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $rknum = $this->_GetInt4d($this->data, $spos + 6);
                    $numValue = $this->_GetIEEE754($rknum);
                    
                    if ($this->isDate($spos)) {
                        list($string, $raw) = $this->createDate($numValue);
                    }
                    else
                    {
                        $raw = $numValue;
                        if (isset($this->_columnsFormat[$column + 1])){
                                $this->curformat = $this->_columnsFormat[$column + 1];
                        }
                        $string = sprintf($this->curformat, $numValue * $this->multiplier);
                    }
                    
                    $this->addcell($row, $column, $string, $raw);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABELSST:
                    $row        = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column     = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $xfindex    = ord($this->data[$spos+4]) | ord($this->data[$spos+5])<<8;
                    $index  = $this->_GetInt4d($this->data, $spos + 6);
                    
                    $this->addcell($row, $column, $this->sst[$index]);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_MULRK:
                    $row        = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $colFirst   = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $colLast    = ord($this->data[$spos + $length - 2]) | ord($this->data[$spos + $length - 1])<<8;
                    $columns    = $colLast - $colFirst + 1;
                    $tmppos = $spos+4;
                    
                    for ($i = 0; $i < $columns; $i++)
                    {
                        $numValue = $this->_GetIEEE754($this->_GetInt4d($this->data, $tmppos + 2));
                        if ($this->isDate($tmppos-4)) {
                            list($string, $raw) = $this->createDate($numValue);
                        }else{
                            $raw = $numValue;
                            if (isset($this->_columnsFormat[$colFirst + $i + 1])){
                                        $this->curformat = $this->_columnsFormat[$colFirst + $i + 1];
                                }
                            $string = sprintf($this->curformat, $numValue * $this->multiplier);
                        }
                        $tmppos += 6;
                        $this->addcell($row, $colFirst + $i, $string, $raw);
                    }

                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_NUMBER:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $tmp = unpack("ddouble", substr($this->data, $spos + 6, 8)); // It machine machine dependent
                    
                    if ($this->isDate($spos))
                    {
                        list($string, $raw) = $this->createDate($tmp['double']);
                    }
                    else
                    {
                        if (isset($this->_columnsFormat[$column + 1]))
                        {
                            $this->curformat = $this->_columnsFormat[$column + 1];
                        }
                        $raw = $this->createNumber($spos);
                        $string = sprintf($this->curformat, $raw * $this->multiplier);
                    }
                    
                    $this->addcell($row, $column, $string, $raw);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA:
                case SPREADSHEET_EXCEL_READER_TYPE_FORMULA2:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    if ((ord($this->data[$spos+6])==0) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
                        //echo "FORMULA $row $column Formula with a string<br>\n";
                    } elseif ((ord($this->data[$spos+6])==1) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
                        //Boolean formula. Result is in +2; 0=false,1=true
                    } elseif ((ord($this->data[$spos+6])==2) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
                        //Error formula. Error code is in +2;
                    } elseif ((ord($this->data[$spos+6])==3) && (ord($this->data[$spos+12])==255) && (ord($this->data[$spos+13])==255)) {
                        //Formula result is a null string.
                    } else {
                        // result is a number, so first 14 bytes are just like a _NUMBER record
                        $tmp = unpack("ddouble", substr($this->data, $spos + 6, 8)); // It machine machine dependent
                        if ($this->isDate($spos)) {
                            list($string, $raw) = $this->createDate($tmp['double']);
                        }else{
                            //$raw = $tmp[''];
                            if (isset($this->_columnsFormat[$column + 1])){
                                    $this->curformat = $this->_columnsFormat[$column + 1];
                            }
                            $raw = $this->createNumber($spos);
                            $string = sprintf($this->curformat, $raw * $this->multiplier);
                        }
                        $this->addcell($row, $column, $string, $raw);
                    }
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_BOOLERR:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    $string = ord($this->data[$spos+6]);
                    $this->addcell($row, $column, $string);
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_ROW:
                case SPREADSHEET_EXCEL_READER_TYPE_DBCELL:
                case SPREADSHEET_EXCEL_READER_TYPE_MULBLANK:
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_LABEL:
                    $row    = ord($this->data[$spos]) | ord($this->data[$spos+1])<<8;
                    $column = ord($this->data[$spos+2]) | ord($this->data[$spos+3])<<8;
                    
                    $this->addcell($row, $column, substr($this->data, $spos + 8, ord($this->data[$spos + 6]) | ord($this->data[$spos + 7])<<8));
                    break;
                case SPREADSHEET_EXCEL_READER_TYPE_EOF:
                    $cont = false;
                    break;
                default:
                    break;
            }
            
            $spos += $length;
        }

        if (!isset($this->sheets[$this->sn]['numRows']))
             $this->sheets[$this->sn]['numRows'] = $this->sheets[$this->sn]['maxrow'];
        if (!isset($this->sheets[$this->sn]['numCols']))
             $this->sheets[$this->sn]['numCols'] = $this->sheets[$this->sn]['maxcol'];

    }

    /**
     * Check whether the current record read is a date
     *
     * @param todo
     * @return boolean True if date, false otherwise
     */
    function isDate($spos)
    {
        $xfindex = ord($this->data[$spos+4]) | ord($this->data[$spos+5]) << 8;
        
        if ($this->formatRecords['xfrecords'][$xfindex]['type'] == 'date') {
            $this->curformat = $this->formatRecords['xfrecords'][$xfindex]['format'];
            $this->rectype = 'date';
            return true;
        } else {
            if ($this->formatRecords['xfrecords'][$xfindex]['type'] == 'number') {
                $this->curformat = $this->formatRecords['xfrecords'][$xfindex]['format'];
                $this->rectype = 'number';
                if (($xfindex == 0x9) || ($xfindex == 0xa)){
                    $this->multiplier = 100;
                }
            }
            else
            {
                $this->curformat = $this->_defaultFormat;
                $this->rectype = 'unknown';
            }
            return false;
        }
    }

    /**
     * Convert the raw Excel date into a human readable format
     *
     * Dates in Excel are stored as number of seconds from an epoch.  On 
     * Windows, the epoch is 30/12/1899 and on Mac it's 01/01/1904
     *
     * @param integer The raw Excel value to convert
     * @return array First element is the converted date, the second element is number a unix timestamp
     */ 
    private function createDate($numValue)
    {
        if ($numValue > 1) {
            $utcDays = $numValue - ($this->nineteenFour ? SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS1904 : SPREADSHEET_EXCEL_READER_UTCOFFSETDAYS);
            $utcValue = round(($utcDays+1) * SPREADSHEET_EXCEL_READER_MSINADAY);
            $string = date ($this->curformat, $utcValue);
            $raw = $utcValue;
        } else {
            $raw = $numValue;
            $hours = floor($numValue * 24);
            $mins = floor($numValue * 24 * 60) - $hours * 60;
            $secs = floor($numValue * SPREADSHEET_EXCEL_READER_MSINADAY) - $hours * 60 * 60 - $mins * 60;
            $string = date ($this->curformat, mktime($hours, $mins, $secs));
        }

        return array($string, $raw);
    }

    function createNumber($spos)
    {
        $rknumhigh = $this->_GetInt4d($this->data, $spos + 10);
        $rknumlow = $this->_GetInt4d($this->data, $spos + 6);
        $sign = ($rknumhigh & 0x80000000) >> 31;
        $exp =  ($rknumhigh & 0x7ff00000) >> 20;
        $mantissa = (0x100000 | ($rknumhigh & 0x000fffff));
        $mantissalow1 = ($rknumlow & 0x80000000) >> 31;
        $mantissalow2 = ($rknumlow & 0x7fffffff);
        $value = $mantissa / pow( 2 , (20- ($exp - 1023)));
        if ($mantissalow1 != 0) $value += 1 / pow (2 , (21 - ($exp - 1023)));
        $value += $mantissalow2 / pow (2 , (52 - ($exp - 1023)));
        if ($sign) {$value = -1 * $value;}
        return  $value;
    }

    private function addcell($row, $col, $string, $raw = '')
    {
        $this->sheets[$this->sn]['maxrow'] = max($this->sheets[$this->sn]['maxrow'], $row + $this->_rowoffset);
        $this->sheets[$this->sn]['maxcol'] = max($this->sheets[$this->sn]['maxcol'], $col + $this->_coloffset);
        $this->sheets[$this->sn]['cells'][$row + $this->_rowoffset][$col + $this->_coloffset] = $string;
        
        if ($raw)
            $this->sheets[$this->sn]['cellsInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['raw'] = $raw;
            
        if (isset($this->rectype))
            $this->sheets[$this->sn]['cellsInfo'][$row + $this->_rowoffset][$col + $this->_coloffset]['type'] = $this->rectype;
    }

    function _GetIEEE754($rknum)
    {
        if (($rknum & 0x02) != 0)
        {
            $value = $rknum >> 2;
        }
        else
        {
            $sign = ($rknum & 0x80000000) >> 31;
            $exp = ($rknum & 0x7ff00000) >> 20;
            $mantissa = (0x100000 | ($rknum & 0x000ffffc));
            $value = $mantissa / pow( 2 , (20- ($exp - 1023)));
            if ($sign) {$value = -1 * $value;}
        }

        if (($rknum & 0x01) != 0)
            $value /= 100;
            
        return $value;
    }

    function _encodeUTF16($string)
    {
        $result = $string;
        
        if ($this->_defaultEncoding)
        {
            switch ($this->_encoderFunction){
                case 'iconv' :     $result = iconv('UTF-16LE', $this->_defaultEncoding, $string);
                                break;
                case 'mb_convert_encoding' :     $result = mb_convert_encoding($string, $this->_defaultEncoding, 'UTF-16LE' );
                                break;
            }
        }
        
        return $result;
    }

    function _GetInt4d($data, $pos)
    {
        $value = ord($data[$pos]) | (ord($data[$pos+1]) << 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24);
        if ($value>=4294967294)
        {
            $value=-2;
        }
        return $value;
    }
}

require_once 'ext/excel/Workbook.php';

/**
* Class for writing Excel Spreadsheets. This class should change COMPLETELY.
*
* @author   Xavier Noguer <xnoguer@rezebra.com>
* @category FileFormats
* @package  Spreadsheet_Excel_Writer
*/
class Spreadsheet_Excel_Writer extends Spreadsheet_Excel_Writer_Workbook
{
    /**
    * The constructor. It just creates a Workbook
    *
    * @param string $filename The optional filename for the Workbook.
    * @return Spreadsheet_Excel_Writer_Workbook The Workbook created
    */
    public function __construct($filename = '')
    {
        $this->_filename = $filename;
        $this->Spreadsheet_Excel_Writer_Workbook($filename);
    }

    /**
    * Send HTTP headers for the Excel file.
    *
    * @param string $filename The filename to use for HTTP headers
    * @access public
    */
    function send($filename)
    {
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
    }

    /**
    * Utility function for writing formulas
    * Converts a cell's coordinates to the A1 format.
    *
    * @access public
    * @static
    * @param integer $row Row for the cell to convert (0-indexed).
    * @param integer $col Column for the cell to convert (0-indexed).
    * @return string The cell identifier in A1 format
    */
    function rowcolToCell($row, $col)
    {
        if ($col > 255)  throw new Exception("Maximum column value exceeded: $col");

        $int = (int)($col / 26);
        $frac = $col % 26;
        $chr1 = '';

        if ($int > 0) $chr1 = chr(ord('A') + $int - 1);

        $chr2 = chr(ord('A') + $frac);
        $row++;

        return $chr1 . $chr2 . $row;
    }
}
?>
