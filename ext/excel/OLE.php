<?php
/**
* Constants for OLE package
*/
define('OLE_PPS_TYPE_ROOT',        5);
define('OLE_PPS_TYPE_DIR',         1);
define('OLE_PPS_TYPE_FILE',        2);
define('OLE_DATA_SIZE_SMALL', 0x1000);
define('OLE_LONG_INT_SIZE',        4);
define('OLE_PPS_SIZE',          0x80);

/**
* Array for storing OLE instances that are accessed from
* OLE_ChainedBlockStream::stream_open().
* @var  array
*/
$GLOBALS['_OLE_INSTANCES'] = array();

/**
* OLE package base class.
*
* @category Structures
* @package  OLE
* @author   Xavier Noguer <xnoguer@php.net>
* @author   Christian Schmidt <schmidt@php.net>
*/
class OLE
{
    /**
    * The file handle for reading an OLE container
    * @var resource
    */
    var $_file_handle;

    /**
    * Array of PPS's found on the OLE container
    * @var array
    */
    var $_list;

    /**
    * Root directory of OLE container
    * @var OLE_PPS_Root
    */
    var $root;

    /**
    * Big Block Allocation Table
    * @var array  (blockId => nextBlockId)
    */
    var $bbat;

    /**
    * Short Block Allocation Table
    * @var array  (blockId => nextBlockId)
    */
    var $sbat;

    /**
    * Size of big blocks. This is usually 512.
    * @var  int  number of octets per block.
    */
    var $bigBlockSize;

    /**
    * Size of small blocks. This is usually 64.
    * @var  int  number of octets per block
    */
    var $smallBlockSize;

    /**
    * Creates a new OLE object
    * @access public
    */
    function OLE()
    {
        $this->_list = array();
    }

    /**
    * Destructor (using PEAR)
    * Just closes the file handle on the OLE file.
    *
    * @access private
    */
    function _OLE()
    {
        fclose($this->_file_handle);
    }

    /**
    * Reads an OLE container from the contents of the file given.
    *
    * @access public
    * @param string $file
    * @return mixed true on success, PEAR_Error on failure
    */
    function read($file)
    {
        $fh = @fopen($file, "r");
        if (!$fh) {
            throw new Exception("Can't open file $file");
        }
        $this->_file_handle = $fh;

        $signature = fread($fh, 8);
        if ("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" != $signature) {
            throw new Exception("File doesn't seem to be an OLE container.");
        }
        fseek($fh, 28);
        if (fread($fh, 2) != "\xFE\xFF") {
            // This shouldn't be a problem in practice
            throw new Exception("Only Little-Endian encoding is supported.");
        }
        // Size of blocks and short blocks in bytes
        $this->bigBlockSize   = pow(2, $this->_readInt2($fh));
        $this->smallBlockSize = pow(2, $this->_readInt2($fh));

        // Skip UID, revision number and version number
        fseek($fh, 44);
        // Number of blocks in Big Block Allocation Table
        $bbatBlockCount = $this->_readInt4($fh);

        // Root chain 1st block
        $directoryFirstBlockId = $this->_readInt4($fh);

        // Skip unused bytes
        fseek($fh, 56);
        // Streams shorter than this are stored using small blocks
        $this->bigBlockThreshold = $this->_readInt4($fh);
        // Block id of first sector in Short Block Allocation Table
        $sbatFirstBlockId = $this->_readInt4($fh);
        // Number of blocks in Short Block Allocation Table
        $sbbatBlockCount = $this->_readInt4($fh);
        // Block id of first sector in Master Block Allocation Table
        $mbatFirstBlockId = $this->_readInt4($fh);
        // Number of blocks in Master Block Allocation Table
        $mbbatBlockCount = $this->_readInt4($fh);
        $this->bbat = array();

        // Remaining 4 * 109 bytes of current block is beginning of Master
        // Block Allocation Table
        $mbatBlocks = array();
        for ($i = 0; $i < 109; $i++) {
            $mbatBlocks[] = $this->_readInt4($fh);
        }

        // Read rest of Master Block Allocation Table (if any is left)
        $pos = $this->_getBlockOffset($mbatFirstBlockId);
        for ($i = 0; $i < $mbbatBlockCount; $i++) {
            fseek($fh, $pos);
            for ($j = 0; $j < $this->bigBlockSize / 4 - 1; $j++) {
                $mbatBlocks[] = $this->_readInt4($fh);
            }
            // Last block id in each block points to next block
            $pos = $this->_getBlockOffset($this->_readInt4($fh));
        }

        // Read Big Block Allocation Table according to chain specified by
        // $mbatBlocks
        for ($i = 0; $i < $bbatBlockCount; $i++) {
            $pos = $this->_getBlockOffset($mbatBlocks[$i]);
            fseek($fh, $pos);
            for ($j = 0 ; $j < $this->bigBlockSize / 4; $j++) {
                $this->bbat[] = $this->_readInt4($fh);
            }
        }

        // Read short block allocation table (SBAT)
        $this->sbat = array();
        $shortBlockCount = $sbbatBlockCount * $this->bigBlockSize / 4;
        $sbatFh = $this->getStream($sbatFirstBlockId);
        for ($blockId = 0; $blockId < $shortBlockCount; $blockId++) {
            $this->sbat[$blockId] = $this->_readInt4($sbatFh);
        }
        fclose($sbatFh);

        $this->_readPpsWks($directoryFirstBlockId);

        return true;
    }

    /**
     * @param int $blockId block id
     * @return int byte offset from beginning of file
     * @access private
     */
    function _getBlockOffset($blockId)
    {
        return 512 + $blockId * $this->bigBlockSize;
    }

    /**
     * Returns a stream for use with fread() etc. External callers should
     * use OLE_PPS_File::getStream().
     * @param int|PPS $blockIdOrPps block id or PPS
     * @return resource read-only stream
     */
    function getStream($blockIdOrPps)
    {
        include_once 'OLE/ChainedBlockStream.php';
        static $isRegistered = false;
        if (!$isRegistered) {
            stream_wrapper_register('ole-chainedblockstream',
                                    'OLE_ChainedBlockStream');
            $isRegistered = true;
        }

        // Store current instance in global array, so that it can be accessed
        // in OLE_ChainedBlockStream::stream_open().
        // Object is removed from self::$instances in OLE_Stream::close().
        $GLOBALS['_OLE_INSTANCES'][] = $this;
        $instanceId = end(array_keys($GLOBALS['_OLE_INSTANCES']));

        $path = 'ole-chainedblockstream://oleInstanceId=' . $instanceId;
        if (is_a($blockIdOrPps, 'OLE_PPS')) {
            $path .= '&blockId=' . $blockIdOrPps->_StartBlock;
            $path .= '&size=' . $blockIdOrPps->Size;
        } else {
            $path .= '&blockId=' . $blockIdOrPps;
        }
        return fopen($path, 'r');
    }

    /**
     * Reads a signed char.
     * @param resource $fh file handle
     * @return int
     * @access private
     */
    function _readInt1($fh)
    {
        list(, $tmp) = unpack("c", fread($fh, 1));
        return $tmp;
    }

    /**
     * Reads an unsigned short (2 octets).
     * @param resource $fh file handle
     * @return int
     * @access private
     */
    function _readInt2($fh)
    {
        list(, $tmp) = unpack("v", fread($fh, 2));
        return $tmp;
    }

    /**
     * Reads an unsigned long (4 octets).
     * @param   resource  file handle
     * @return  int
     * @access private
     */
    function _readInt4($fh)
    {
        list(, $tmp) = unpack("V", fread($fh, 4));
        return $tmp;
    }

    /**
    * Gets information about all PPS's on the OLE container from the PPS WK's
    * creates an OLE_PPS object for each one.
    *
    * @access private
    * @param integer $blockId the block id of the first block
    * @return mixed true on success, PEAR_Error on failure
    */
    function _readPpsWks($blockId)
    {
        $fh = $this->getStream($blockId);
        for ($pos = 0; ; $pos += 128) {
            fseek($fh, $pos, SEEK_SET);
            $nameUtf16 = fread($fh, 64);
            $nameLength = $this->_readInt2($fh);
            $nameUtf16 = substr($nameUtf16, 0, $nameLength - 2);
            // Simple conversion from UTF-16LE to ISO-8859-1
            $name = str_replace("\x00", "", $nameUtf16);
            $type = $this->_readInt1($fh);
            switch ($type) {
            case OLE_PPS_TYPE_ROOT:
                $pps = new OLE_PPS_Root(null, null, array());
                $this->root = $pps;
                break;
            case OLE_PPS_TYPE_DIR:
                $pps = new OLE_PPS(null, null, null, null, null,
                                   null, null, null, null, array());
                break;
            case OLE_PPS_TYPE_FILE:
                $pps = new OLE_PPS_File($name);
                break;
            default:
                continue;
            }
            fseek($fh, 1, SEEK_CUR);
            $pps->Type    = $type;
            $pps->Name    = $name;
            $pps->PrevPps = $this->_readInt4($fh);
            $pps->NextPps = $this->_readInt4($fh);
            $pps->DirPps  = $this->_readInt4($fh);
            fseek($fh, 20, SEEK_CUR);
            $pps->Time1st = OLE::OLE2LocalDate(fread($fh, 8));
            $pps->Time2nd = OLE::OLE2LocalDate(fread($fh, 8));
            $pps->_StartBlock = $this->_readInt4($fh);
            $pps->Size = $this->_readInt4($fh);
            $pps->No = count($this->_list);
            $this->_list[] = $pps;

            // check if the PPS tree (starting from root) is complete
            if (isset($this->root) &&
                $this->_ppsTreeComplete($this->root->No)) {

                break;
            }
        }
        fclose($fh);

        // Initialize $pps->children on directories
        foreach ($this->_list as $pps) {
            if ($pps->Type == OLE_PPS_TYPE_DIR || $pps->Type == OLE_PPS_TYPE_ROOT) {
                $nos = array($pps->DirPps);
                $pps->children = array();
                while ($nos) {
                    $no = array_pop($nos);
                    if ($no != -1) {
                        $childPps = $this->_list[$no];
                        $nos[] = $childPps->PrevPps;
                        $nos[] = $childPps->NextPps;
                        $pps->children[] = $childPps;
                    }
                }
            }
        }

        return true;
    }

    /**
    * It checks whether the PPS tree is complete (all PPS's read)
    * starting with the given PPS (not necessarily root)
    *
    * @access private
    * @param integer $index The index of the PPS from which we are checking
    * @return boolean Whether the PPS tree for the given PPS is complete
    */
    function _ppsTreeComplete($index)
    {
        return isset($this->_list[$index]) &&
               ($pps = $this->_list[$index]) &&
               ($pps->PrevPps == -1 ||
                $this->_ppsTreeComplete($pps->PrevPps)) &&
               ($pps->NextPps == -1 ||
                $this->_ppsTreeComplete($pps->NextPps)) &&
               ($pps->DirPps == -1 ||
                $this->_ppsTreeComplete($pps->DirPps));
    }

    /** 
    * Checks whether a PPS is a File PPS or not.
    * If there is no PPS for the index given, it will return false.
    * @param integer $index The index for the PPS
    * @return bool true if it's a File PPS, false otherwise
    * @access public
    */
    function isFile($index)
    {
        if (isset($this->_list[$index])) {
            return ($this->_list[$index]->Type == OLE_PPS_TYPE_FILE);
        }
        return false;
    }

    /** 
    * Checks whether a PPS is a Root PPS or not.
    * If there is no PPS for the index given, it will return false.
    * @param integer $index The index for the PPS.
    * @return bool true if it's a Root PPS, false otherwise
    * @access public
    */
    function isRoot($index)
    {
        if (isset($this->_list[$index])) {
            return ($this->_list[$index]->Type == OLE_PPS_TYPE_ROOT);
        }
        return false;
    }

    /** 
    * Gives the total number of PPS's found in the OLE container.
    * @return integer The total number of PPS's found in the OLE container
    * @access public
    */
    function ppsTotal()
    {
        return count($this->_list);
    }

    /**
    * Gets data from a PPS
    * If there is no PPS for the index given, it will return an empty string.
    * @param integer $index    The index for the PPS
    * @param integer $position The position from which to start reading
    *                          (relative to the PPS)
    * @param integer $length   The amount of bytes to read (at most)
    * @return string The binary string containing the data requested
    * @access public
    * @see OLE_PPS_File::getStream()
    */
    function getData($index, $position, $length)
    {
        // if position is not valid return empty string
        if (!isset($this->_list[$index]) ||
            $position >= $this->_list[$index]->Size ||
            $position < 0) {

            return '';
        }
        $fh = $this->getStream($this->_list[$index]);
        $data = stream_get_contents($fh, $length, $position);
        fclose($fh);
        return $data;
    }

    /**
    * Gets the data length from a PPS
    * If there is no PPS for the index given, it will return 0.
    * @param integer $index The index for the PPS
    * @return integer The amount of bytes in data the PPS has
    * @access public
    */
    function getDataLength($index)
    {
        if (isset($this->_list[$index])) {
            return $this->_list[$index]->Size;
        }
        return 0;
    }

    /**
    * Utility function to transform ASCII text to Unicode
    *
    * @access public
    * @static
    * @param string $ascii The ASCII string to transform
    * @return string The string in Unicode
    */
    function Asc2Ucs($ascii)
    {
        $rawname = '';
        for ($i = 0; $i < strlen($ascii); $i++) {
            $rawname .= $ascii{$i} . "\x00";
        }
        return $rawname;
    }

    /**
    * Utility function
    * Returns a string for the OLE container with the date given
    *
    * @access public
    * @static
    * @param integer $date A timestamp 
    * @return string The string for the OLE container
    */
    function LocalDate2OLE($date = null)
    {
        if (!isset($date)) {
            return "\x00\x00\x00\x00\x00\x00\x00\x00";
        }

        // factor used for separating numbers into 4 bytes parts
        $factor = pow(2, 32);

        // days from 1-1-1601 until the beggining of UNIX era
        $days = 134774;
        // calculate seconds
        $big_date = $days * 24 * 3600 +
            gmmktime(date("H",$date),date("i",$date),date("s",$date),
                     date("m",$date),date("d",$date),date("Y",$date));
        // multiply just to make MS happy
        $big_date *= 10000000;

        $high_part = floor($big_date / $factor);
        // lower 4 bytes
        $low_part = floor((($big_date / $factor) - $high_part) * $factor);

        // Make HEX string
        $res = '';

        for ($i = 0; $i < 4; $i++) {
            $hex = $low_part % 0x100;
            $res .= pack('c', $hex);
            $low_part /= 0x100;
        }
        for ($i = 0; $i < 4; $i++) {
            $hex = $high_part % 0x100;
            $res .= pack('c', $hex);
            $high_part /= 0x100;
        }
        return $res;
    }

    /**
    * Returns a timestamp from an OLE container's date
    * @param integer $string A binary string with the encoded date
    * @return string The timestamp corresponding to the string
    * @access public
    * @static
    */
    function OLE2LocalDate($string)
    {
        if (strlen($string) != 8) {
            throw new Exception("Expecting 8 byte string");
        }

        // factor used for separating numbers into 4 bytes parts
        $factor = pow(2,32);
        $high_part = 0;
        for ($i = 0; $i < 4; $i++) {
            list(, $high_part) = unpack('C', $string{(7 - $i)});
            if ($i < 3) {
                $high_part *= 0x100;
            }
        }
        $low_part = 0;
        for ($i = 4; $i < 8; $i++) {
            list(, $low_part) = unpack('C', $string{(7 - $i)});
            if ($i < 7) {
                $low_part *= 0x100;
            }
        }
        $big_date = ($high_part * $factor) + $low_part;
        // translate to seconds
        $big_date /= 10000000;
        
        // days from 1-1-1601 until the beggining of UNIX era
        $days = 134774;
        
        // translate to seconds from beggining of UNIX era
        $big_date -= $days * 24 * 3600;
        return floor($big_date);
    }
}

class OLE_PPS
{
    /**
    * The PPS index
    * @var integer
    */
    var $No;

    /**
    * The PPS name (in Unicode)
    * @var string
    */
    var $Name;
 
    /**
    * The PPS type. Dir, Root or File
    * @var integer
    */
    var $Type;
 
    /**
    * The index of the previous PPS
    * @var integer
    */
    var $PrevPps;
 
    /**
    * The index of the next PPS
    * @var integer
    */
    var $NextPps;
 
    /**
    * The index of it's first child if this is a Dir or Root PPS
    * @var integer
    */
    var $DirPps;
 
    /**
    * A timestamp
    * @var integer
    */
    var $Time1st;

    /**
    * A timestamp
    * @var integer
    */
    var $Time2nd;

    /**
    * Starting block (small or big) for this PPS's data  inside the container
    * @var integer
    */
    var $_StartBlock;

    /**
    * The size of the PPS's data (in bytes)
    * @var integer
    */
    var $Size;

    /**
    * The PPS's data (only used if it's not using a temporary file)
    * @var string
    */
    var $_data;

    /**
    * Array of child PPS's (only used by Root and Dir PPS's)
    * @var array
    */
    var $children = array();

    /**
    * Pointer to OLE container
    * @var OLE
    */
    var $ole;

    /**
    * The constructor
    *
    * @access public
    * @param integer $No   The PPS index
    * @param string  $name The PPS name
    * @param integer $type The PPS type. Dir, Root or File
    * @param integer $prev The index of the previous PPS
    * @param integer $next The index of the next PPS
    * @param integer $dir  The index of it's first child if this is a Dir or Root PPS
    * @param integer $time_1st A timestamp
    * @param integer $time_2nd A timestamp
    * @param string  $data  The (usually binary) source data of the PPS
    * @param array   $children Array containing children PPS for this PPS
    */
    function OLE_PPS($No, $name, $type, $prev, $next, $dir, $time_1st, $time_2nd, $data, $children)
    {
        $this->No      = $No;
        $this->Name    = $name;
        $this->Type    = $type;
        $this->PrevPps = $prev;
        $this->NextPps = $next;
        $this->DirPps  = $dir;
        $this->Time1st = $time_1st;
        $this->Time2nd = $time_2nd;
        $this->_data      = $data;
        $this->children   = $children;
        if ($data != '') {
            $this->Size = strlen($data);
        } else {
            $this->Size = 0;
        }
    }

    /**
    * Returns the amount of data saved for this PPS
    *
    * @access private
    * @return integer The amount of data (in bytes)
    */
    function _DataLen()
    {
        if (!isset($this->_data)) {
            return 0;
        }
        if (isset($this->_PPS_FILE)) {
            fseek($this->_PPS_FILE, 0);
            $stats = fstat($this->_PPS_FILE);
            return $stats[7];
        } else {
            return strlen($this->_data);
        }
    }

    /**
    * Returns a string with the PPS's WK (What is a WK?)
    *
    * @access private
    * @return string The binary string
    */
    function _getPpsWk()
    {
        $ret = $this->Name;
        for ($i = 0; $i < (64 - strlen($this->Name)); $i++) {
            $ret .= "\x00";
        }
        $ret .= pack("v", strlen($this->Name) + 2)  // 66
              . pack("c", $this->Type)              // 67
              . pack("c", 0x00) //UK                // 68
              . pack("V", $this->PrevPps) //Prev    // 72
              . pack("V", $this->NextPps) //Next    // 76
              . pack("V", $this->DirPps)  //Dir     // 80
              . "\x00\x09\x02\x00"                  // 84
              . "\x00\x00\x00\x00"                  // 88
              . "\xc0\x00\x00\x00"                  // 92
              . "\x00\x00\x00\x46"                  // 96 // Seems to be ok only for Root
              . "\x00\x00\x00\x00"                  // 100
              . OLE::LocalDate2OLE($this->Time1st)       // 108
              . OLE::LocalDate2OLE($this->Time2nd)       // 116
              . pack("V", isset($this->_StartBlock)? 
                        $this->_StartBlock:0)        // 120
              . pack("V", $this->Size)               // 124
              . pack("V", 0);                        // 128
        return $ret;
    }

    /**
    * Updates index and pointers to previous, next and children PPS's for this
    * PPS. I don't think it'll work with Dir PPS's.
    *
    * @access private
    * @param array &$pps_array Reference to the array of PPS's for the whole OLE
    *                          container 
    * @return integer          The index for this PPS
    */
    function _savePpsSetPnt(&$pps_array) 
    {
        $pps_array[count($pps_array)] = &$this;
        $this->No = count($pps_array) - 1;
        $this->PrevPps = 0xFFFFFFFF;
        $this->NextPps = 0xFFFFFFFF;
        if (count($this->children) > 0) {
            $this->DirPps = $this->children[0]->_savePpsSetPnt($pps_array);
        } else {
            $this->DirPps = 0xFFFFFFFF;
        }
        return $this->No;
    }
}

class OLE_PPS_Root extends OLE_PPS
{
    /**
    * The temporary dir for storing the OLE file
    * @var string
    */
    var $_tmp_dir;
    
    /**
    * Constructor
    *
    * @access public
    * @param integer $time_1st A timestamp
    * @param integer $time_2nd A timestamp
    */
    function OLE_PPS_Root($time_1st, $time_2nd, $raChild)
    {
        $this->_tmp_dir = sys_get_temp_dir();
        $this->OLE_PPS(
           null, 
           OLE::Asc2Ucs('Root Entry'),
           OLE_PPS_TYPE_ROOT,
           null,
           null,
           null,
           $time_1st,
           $time_2nd,
           null,
           $raChild);
    }

    /**
    * Sets the temp dir used for storing the OLE file
    *
    * @access public
    * @param string $dir The dir to be used as temp dir
    * @return true if given dir is valid, false otherwise
    */
    function setTempDir($dir)
    {
        if (is_dir($dir)) {
            $this->_tmp_dir = $dir;
            return true;
        }
        return false;
    }

    /**
    * Method for saving the whole OLE container (including files).
    * In fact, if called with an empty argument (or '-'), it saves to a
    * temporary file and then outputs it's contents to stdout.
    *
    * @param string $filename The name of the file where to save the OLE container
    * @access public
    * @return mixed true on success, throws Exception on failure
    */
    function save($filename)
    {
        // Initial Setting for saving
        $this->_BIG_BLOCK_SIZE  = pow(2,
                      ((isset($this->_BIG_BLOCK_SIZE))? $this->_adjust2($this->_BIG_BLOCK_SIZE)  : 9));
        $this->_SMALL_BLOCK_SIZE= pow(2, 
                      ((isset($this->_SMALL_BLOCK_SIZE))?  $this->_adjust2($this->_SMALL_BLOCK_SIZE): 6));
 
        // Open temp file if we are sending output to stdout
        if (($filename == '-') || ($filename == '')) {
            $this->_tmp_filename = tempnam($this->_tmp_dir, "OLE_PPS_Root");
            $this->_FILEH_ = @fopen($this->_tmp_filename,"w+b");
            if ($this->_FILEH_ == false) {
                throw new Exception("Can't create temporary file.");
            }
        } else {
            $this->_FILEH_ = @fopen($filename, "wb");
            if ($this->_FILEH_ == false) {
                throw new Exception("Can't open $filename. It may be in use or protected.");
            }
        }
        // Make an array of PPS's (for Save)
        $aList = array();
        $this->_savePpsSetPnt($aList);
        // calculate values for header
        list($iSBDcnt, $iBBcnt, $iPPScnt) = $this->_calcSize($aList); //, $rhInfo);
        // Save Header
        $this->_saveHeader($iSBDcnt, $iBBcnt, $iPPScnt);
  
        // Make Small Data string (write SBD)
        $this->_data = $this->_makeSmallData($aList);
  
        // Write BB
        $this->_saveBigData($iSBDcnt, $aList);
        // Write PPS
        $this->_savePps($aList);
        // Write Big Block Depot and BDList and Adding Header informations
        $this->_saveBbd($iSBDcnt, $iBBcnt, $iPPScnt);
        // Close File, send it to stdout if necessary
        if (($filename == '-') || ($filename == '')) {
            fseek($this->_FILEH_, 0);
            fpassthru($this->_FILEH_);
            @fclose($this->_FILEH_);
            // Delete the temporary file.
            @unlink($this->_tmp_filename);
        } else {
            @fclose($this->_FILEH_);
        }

        return true;
    }

    /**
    * Calculate some numbers
    *
    * @access private
    * @param array $raList Reference to an array of PPS's
    * @return array The array of numbers
    */
    function _calcSize(&$raList) 
    {
        // Calculate Basic Setting
        list($iSBDcnt, $iBBcnt, $iPPScnt) = array(0,0,0);
        $iSmallLen = 0;
        $iSBcnt = 0;
        for ($i = 0; $i < count($raList); $i++) {
            if ($raList[$i]->Type == OLE_PPS_TYPE_FILE) {
                $raList[$i]->Size = $raList[$i]->_DataLen();
                if ($raList[$i]->Size < OLE_DATA_SIZE_SMALL) {
                    $iSBcnt += floor($raList[$i]->Size / $this->_SMALL_BLOCK_SIZE)
                                  + (($raList[$i]->Size % $this->_SMALL_BLOCK_SIZE)? 1: 0);
                } else {
                    $iBBcnt += (floor($raList[$i]->Size / $this->_BIG_BLOCK_SIZE) +
                        (($raList[$i]->Size % $this->_BIG_BLOCK_SIZE)? 1: 0));
                }
            }
        }
        $iSmallLen = $iSBcnt * $this->_SMALL_BLOCK_SIZE;
        $iSlCnt = floor($this->_BIG_BLOCK_SIZE / OLE_LONG_INT_SIZE);
        $iSBDcnt = floor($iSBcnt / $iSlCnt) + (($iSBcnt % $iSlCnt)? 1:0);
        $iBBcnt +=  (floor($iSmallLen / $this->_BIG_BLOCK_SIZE) +
                      (( $iSmallLen % $this->_BIG_BLOCK_SIZE)? 1: 0));
        $iCnt = count($raList);
        $iBdCnt = $this->_BIG_BLOCK_SIZE / OLE_PPS_SIZE;
        $iPPScnt = (floor($iCnt/$iBdCnt) + (($iCnt % $iBdCnt)? 1: 0));
   
        return array($iSBDcnt, $iBBcnt, $iPPScnt);
    }

    /**
    * Helper function for caculating a magic value for block sizes
    *
    * @access private
    * @param integer $i2 The argument
    * @see save()
    * @return integer
    */
    function _adjust2($i2)
    {
        $iWk = log($i2)/log(2);
        return ($iWk > floor($iWk))? floor($iWk)+1:$iWk;
    }

    /**
    * Save OLE header
    *
    * @access private
    * @param integer $iSBDcnt
    * @param integer $iBBcnt
    * @param integer $iPPScnt
    */
    function _saveHeader($iSBDcnt, $iBBcnt, $iPPScnt)
    {
        $FILE = $this->_FILEH_;
  
        // Calculate Basic Setting
        $iBlCnt = $this->_BIG_BLOCK_SIZE / OLE_LONG_INT_SIZE;
        $i1stBdL = ($this->_BIG_BLOCK_SIZE - 0x4C) / OLE_LONG_INT_SIZE;
  
        $iBdExL = 0;
        $iAll = $iBBcnt + $iPPScnt + $iSBDcnt;
        $iAllW = $iAll;
        $iBdCntW = floor($iAllW / $iBlCnt) + (($iAllW % $iBlCnt)? 1: 0);
        $iBdCnt = floor(($iAll + $iBdCntW) / $iBlCnt) + ((($iAllW+$iBdCntW) % $iBlCnt)? 1: 0);
  
        // Calculate BD count
        if ($iBdCnt > $i1stBdL) {
            while (1) {
                $iBdExL++;
                $iAllW++;
                $iBdCntW = floor($iAllW / $iBlCnt) + (($iAllW % $iBlCnt)? 1: 0);
                $iBdCnt = floor(($iAllW + $iBdCntW) / $iBlCnt) + ((($iAllW+$iBdCntW) % $iBlCnt)? 1: 0);
                if ($iBdCnt <= ($iBdExL*$iBlCnt+ $i1stBdL)) {
                    break;
                }
            }
        }
  
        // Save Header
        fwrite($FILE,
                  "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
                  . "\x00\x00\x00\x00"
                  . "\x00\x00\x00\x00"
                  . "\x00\x00\x00\x00"
                  . "\x00\x00\x00\x00"
                  . pack("v", 0x3b)
                  . pack("v", 0x03)
                  . pack("v", -2)
                  . pack("v", 9)
                  . pack("v", 6)
                  . pack("v", 0)
                  . "\x00\x00\x00\x00"
                  . "\x00\x00\x00\x00"
                  . pack("V", $iBdCnt) 
                  . pack("V", $iBBcnt+$iSBDcnt) //ROOT START
                  . pack("V", 0)
                  . pack("V", 0x1000)
                  . pack("V", 0)                  //Small Block Depot
                  . pack("V", 1)
          );
        // Extra BDList Start, Count
        if ($iBdCnt < $i1stBdL) {
            fwrite($FILE,
                      pack("V", -2).      // Extra BDList Start
                      pack("V", 0)        // Extra BDList Count
                  );
        } else {
            fwrite($FILE, pack("V", $iAll+$iBdCnt) . pack("V", $iBdExL));
        }

        // BDList
        for ($i = 0; $i < $i1stBdL && $i < $iBdCnt; $i++) {
            fwrite($FILE, pack("V", $iAll+$i));
        }
        if ($i < $i1stBdL) {
            for ($j = 0; $j < ($i1stBdL-$i); $j++) {
                fwrite($FILE, (pack("V", -1)));
            }
        }
    }

    /**
    * Saving big data (PPS's with data bigger than OLE_DATA_SIZE_SMALL)
    *
    * @access private
    * @param integer $iStBlk
    * @param array &$raList Reference to array of PPS's
    */
    function _saveBigData($iStBlk, &$raList)
    {
        $FILE = $this->_FILEH_;
   
        // cycle through PPS's
        for ($i = 0; $i < count($raList); $i++) {
            if ($raList[$i]->Type != OLE_PPS_TYPE_DIR) {
                $raList[$i]->Size = $raList[$i]->_DataLen();
                if (($raList[$i]->Size >= OLE_DATA_SIZE_SMALL) ||
                    (($raList[$i]->Type == OLE_PPS_TYPE_ROOT) && isset($raList[$i]->_data)))
                {
                    // Write Data
                    if (isset($raList[$i]->_PPS_FILE)) {
                        $iLen = 0;
                        fseek($raList[$i]->_PPS_FILE, 0); // To The Top
                        while($sBuff = fread($raList[$i]->_PPS_FILE, 4096)) {
                            $iLen += strlen($sBuff);
                            fwrite($FILE, $sBuff);
                        }
                    } else {
                        fwrite($FILE, $raList[$i]->_data);
                    }
           
                    if ($raList[$i]->Size % $this->_BIG_BLOCK_SIZE) {
                        for ($j = 0; $j < ($this->_BIG_BLOCK_SIZE - ($raList[$i]->Size % $this->_BIG_BLOCK_SIZE)); $j++) {
                            fwrite($FILE, "\x00");
                        }
                    }
                    // Set For PPS
                    $raList[$i]->_StartBlock = $iStBlk;
                    $iStBlk += 
                            (floor($raList[$i]->Size / $this->_BIG_BLOCK_SIZE) +
                                (($raList[$i]->Size % $this->_BIG_BLOCK_SIZE)? 1: 0));
                }
                // Close file for each PPS, and unlink it
                if (isset($raList[$i]->_PPS_FILE)) {
                    @fclose($raList[$i]->_PPS_FILE);
                    $raList[$i]->_PPS_FILE = null;
                    @unlink($raList[$i]->_tmp_filename);
                }
            }
        }
    }

    /**
    * get small data (PPS's with data smaller than OLE_DATA_SIZE_SMALL)
    *
    * @access private
    * @param array &$raList Reference to array of PPS's
    */
    function _makeSmallData(&$raList)
    {
        $sRes = '';
        $FILE = $this->_FILEH_;
        $iSmBlk = 0;
   
        for ($i = 0; $i < count($raList); $i++) {
            // Make SBD, small data string
            if ($raList[$i]->Type == OLE_PPS_TYPE_FILE) {
                if ($raList[$i]->Size <= 0) {
                    continue;
                }
                if ($raList[$i]->Size < OLE_DATA_SIZE_SMALL) {
                    $iSmbCnt = floor($raList[$i]->Size / $this->_SMALL_BLOCK_SIZE)
                                  + (($raList[$i]->Size % $this->_SMALL_BLOCK_SIZE)? 1: 0);
                    // Add to SBD
                    for ($j = 0; $j < ($iSmbCnt-1); $j++) {
                        fwrite($FILE, pack("V", $j+$iSmBlk+1));
                    }
                    fwrite($FILE, pack("V", -2));
                   
                    // Add to Data String(this will be written for RootEntry)
                    if ($raList[$i]->_PPS_FILE) {
                        fseek($raList[$i]->_PPS_FILE, 0); // To The Top
                        while ($sBuff = fread($raList[$i]->_PPS_FILE, 4096)) {
                            $sRes .= $sBuff;
                        }
                    } else {
                        $sRes .= $raList[$i]->_data;
                    }
                    if ($raList[$i]->Size % $this->_SMALL_BLOCK_SIZE) {
                        for ($j = 0; $j < ($this->_SMALL_BLOCK_SIZE - ($raList[$i]->Size % $this->_SMALL_BLOCK_SIZE)); $j++) {
                            $sRes .= "\x00";
                        }
                    }
                    // Set for PPS
                    $raList[$i]->_StartBlock = $iSmBlk;
                    $iSmBlk += $iSmbCnt;
                }
            }
        }
        $iSbCnt = floor($this->_BIG_BLOCK_SIZE / OLE_LONG_INT_SIZE);
        if ($iSmBlk % $iSbCnt) {
            for ($i = 0; $i < ($iSbCnt - ($iSmBlk % $iSbCnt)); $i++) {
                fwrite($FILE, pack("V", -1));
            }
        }
        return $sRes;
    }

    /**
    * Saves all the PPS's WKs
    *
    * @access private
    * @param array $raList Reference to an array with all PPS's
    */
    function _savePps(&$raList) 
    {
        // Save each PPS WK
        for ($i = 0; $i < count($raList); $i++) {
            fwrite($this->_FILEH_, $raList[$i]->_getPpsWk());
        }
        // Adjust for Block
        $iCnt = count($raList);
        $iBCnt = $this->_BIG_BLOCK_SIZE / OLE_PPS_SIZE;
        if ($iCnt % $iBCnt) {
            for ($i = 0; $i < (($iBCnt - ($iCnt % $iBCnt)) * OLE_PPS_SIZE); $i++) {
                fwrite($this->_FILEH_, "\x00");
            }
        }
    }

    /**
    * Saving Big Block Depot
    *
    * @access private
    * @param integer $iSbdSize
    * @param integer $iBsize
    * @param integer $iPpsCnt
    */
    function _saveBbd($iSbdSize, $iBsize, $iPpsCnt) 
    {
        $FILE = $this->_FILEH_;
        // Calculate Basic Setting
        $iBbCnt = $this->_BIG_BLOCK_SIZE / OLE_LONG_INT_SIZE;
        $i1stBdL = ($this->_BIG_BLOCK_SIZE - 0x4C) / OLE_LONG_INT_SIZE;
      
        $iBdExL = 0;
        $iAll = $iBsize + $iPpsCnt + $iSbdSize;
        $iAllW = $iAll;
        $iBdCntW = floor($iAllW / $iBbCnt) + (($iAllW % $iBbCnt)? 1: 0);
        $iBdCnt = floor(($iAll + $iBdCntW) / $iBbCnt) + ((($iAllW+$iBdCntW) % $iBbCnt)? 1: 0);
        // Calculate BD count
        if ($iBdCnt >$i1stBdL) {
            while (1) {
                $iBdExL++;
                $iAllW++;
                $iBdCntW = floor($iAllW / $iBbCnt) + (($iAllW % $iBbCnt)? 1: 0);
                $iBdCnt = floor(($iAllW + $iBdCntW) / $iBbCnt) + ((($iAllW+$iBdCntW) % $iBbCnt)? 1: 0);
                if ($iBdCnt <= ($iBdExL*$iBbCnt+ $i1stBdL)) {
                    break;
                }
            }
        }
      
        // Making BD
        // Set for SBD
        if ($iSbdSize > 0) {
            for ($i = 0; $i < ($iSbdSize - 1); $i++) {
                fwrite($FILE, pack("V", $i+1));
            }
            fwrite($FILE, pack("V", -2));
        }
        // Set for B
        for ($i = 0; $i < ($iBsize - 1); $i++) {
            fwrite($FILE, pack("V", $i+$iSbdSize+1));
        }
        fwrite($FILE, pack("V", -2));
      
        // Set for PPS
        for ($i = 0; $i < ($iPpsCnt - 1); $i++) {
            fwrite($FILE, pack("V", $i+$iSbdSize+$iBsize+1));
        }
        fwrite($FILE, pack("V", -2));
        // Set for BBD itself ( 0xFFFFFFFD : BBD)
        for ($i = 0; $i < $iBdCnt; $i++) {
            fwrite($FILE, pack("V", 0xFFFFFFFD));
        }
        // Set for ExtraBDList
        for ($i = 0; $i < $iBdExL; $i++) {
            fwrite($FILE, pack("V", 0xFFFFFFFC));
        }
        // Adjust for Block
        if (($iAllW + $iBdCnt) % $iBbCnt) {
            for ($i = 0; $i < ($iBbCnt - (($iAllW + $iBdCnt) % $iBbCnt)); $i++) {
                fwrite($FILE, pack("V", -1));
            }
        }
        // Extra BDList
        if ($iBdCnt > $i1stBdL) {
            $iN=0;
            $iNb=0;
            for ($i = $i1stBdL;$i < $iBdCnt; $i++, $iN++) {
                if ($iN >= ($iBbCnt - 1)) {
                    $iN = 0;
                    $iNb++;
                    fwrite($FILE, pack("V", $iAll+$iBdCnt+$iNb));
                }
                fwrite($FILE, pack("V", $iBsize+$iSbdSize+$iPpsCnt+$i));
            }
            if (($iBdCnt-$i1stBdL) % ($iBbCnt-1)) {
                for ($i = 0; $i < (($iBbCnt - 1) - (($iBdCnt - $i1stBdL) % ($iBbCnt - 1))); $i++) {
                    fwrite($FILE, pack("V", -1)); 
                }
            }
            fwrite($FILE, pack("V", -2));
        }
    }
}

/**
* Class for creating File PPS's for OLE containers
*
* @author   Xavier Noguer <xnoguer@php.net>
* @category Structures
* @package  OLE
*/
class OLE_PPS_File extends OLE_PPS
{
    /**
    * The temporary dir for storing the OLE file
    * @var string
    */
    var $_tmp_dir;

    /**
    * The constructor
    *
    * @access public
    * @param string $name The name of the file (in Unicode)
    * @see OLE::Asc2Ucs()
    */
    function OLE_PPS_File($name)
    {
        $this->_tmp_dir = sys_get_temp_dir();
        $this->OLE_PPS(
            null, 
            $name,
            OLE_PPS_TYPE_FILE,
            null,
            null,
            null,
            null,
            null,
            '',
            array());
    }

    /**
    * Sets the temp dir used for storing the OLE file
    *
    * @access public
    * @param string $dir The dir to be used as temp dir
    * @return true if given dir is valid, false otherwise
    */
    function setTempDir($dir)
    {
        if (is_dir($dir)) {
            $this->_tmp_dir = $dir;
            return true;
        }
        return false;
    }

    /**
    * Initialization method. Has to be called right after OLE_PPS_File().
    *
    * @access public
    * @return mixed true on success.
    */
    function init()
    {
        $this->_tmp_filename = tempnam($this->_tmp_dir, "OLE_PPS_File");
        $fh = @fopen($this->_tmp_filename, "w+b");
        if ($fh == false) {
            throw new Exception("Can't create temporary file");
        }
        $this->_PPS_FILE = $fh;
        if ($this->_PPS_FILE) {
            fseek($this->_PPS_FILE, 0);
        }

        return true;
    }
    
    /**
    * Append data to PPS
    *
    * @access public
    * @param string $data The data to append
    */
    function append($data)
    {
        if ($this->_PPS_FILE) {
            fwrite($this->_PPS_FILE, $data);
        } else {
            $this->_data .= $data;
        }
    }

    /**
     * Returns a stream for reading this file using fread() etc.
     * @return  resource  a read-only stream
     */
    function getStream()
    {
        $this->ole->getStream($this);
    }
}

class OLE_ChainedBlockStream
{
    /**
     * The OLE container of the file that is being read.
     * @var OLE
     */
    var $ole;

    /**
     * Parameters specified by fopen().
     * @var array
     */
    var $params;

    /**
     * The binary data of the file.
     * @var  string
     */
    var $data;

    /**
     * The file pointer.
     * @var  int  byte offset
     */
    var $pos;

    /**
     * Implements support for fopen().
     * For creating streams using this wrapper, use OLE_PPS_File::getStream().
     * @param  string  resource name including scheme, e.g.
     *                 ole-chainedblockstream://oleInstanceId=1
     * @param  string  only "r" is supported
     * @param  int     mask of STREAM_REPORT_ERRORS and STREAM_USE_PATH
     * @param  string  absolute path of the opened stream (out parameter)
     * @return bool    true on success
     */
    function stream_open($path, $mode, $options, &$openedPath)
    {
        if ($mode != 'r') {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Only reading is supported', E_USER_WARNING);
            }
            return false;
        }

        // 25 is length of "ole-chainedblockstream://"
        parse_str(substr($path, 25), $this->params);
        if (!isset($this->params['oleInstanceId'],
                   $this->params['blockId'],
                   $GLOBALS['_OLE_INSTANCES'][$this->params['oleInstanceId']])) {

            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('OLE stream not found', E_USER_WARNING);
            }
            return false;
        }
        $this->ole = $GLOBALS['_OLE_INSTANCES'][$this->params['oleInstanceId']];

        $blockId = $this->params['blockId'];
        $this->data = '';
        if (isset($this->params['size']) &&
            $this->params['size'] < $this->ole->bigBlockThreshold &&
            $blockId != $this->ole->root->_StartBlock) {

            // Block id refers to small blocks
            $rootPos = $this->ole->_getBlockOffset($this->ole->root->_StartBlock);
            while ($blockId != -2) {
                $pos = $rootPos + $blockId * $this->ole->bigBlockSize;
                $blockId = $this->ole->sbat[$blockId];
                fseek($this->ole->_file_handle, $pos);
                $this->data .= fread($this->ole->_file_handle, $this->ole->bigBlockSize);
            }
        } else {
            // Block id refers to big blocks
            while ($blockId != -2) {
                $pos = $this->ole->_getBlockOffset($blockId);
                fseek($this->ole->_file_handle, $pos);
                $this->data .= fread($this->ole->_file_handle, $this->ole->bigBlockSize);
                $blockId = $this->ole->bbat[$blockId];
            }
        }
        if (isset($this->params['size'])) {
            $this->data = substr($this->data, 0, $this->params['size']);
        }

        if ($options & STREAM_USE_PATH) {
            $openedPath = $path;
        }

        return true;
    }

    /**
     * Implements support for fclose().
     * @return  string
     */
    function stream_close()
    {
        $this->ole = null;
        unset($GLOBALS['_OLE_INSTANCES']);
    }

    /**
     * Implements support for fread(), fgets() etc.
     * @param   int  maximum number of bytes to read
     * @return  string
     */
    function stream_read($count)
    {
        if ($this->stream_eof()) {
            return false;
        }
        $s = substr($this->data, $this->pos, $count);
        $this->pos += $count;
        return $s;
    }

    /**
     * Implements support for feof().
     * @return  bool  TRUE if the file pointer is at EOF; otherwise FALSE
     */
    function stream_eof()
    {
        $eof = $this->pos >= strlen($this->data);
        // Workaround for bug in PHP 5.0.x: http://bugs.php.net/27508
        if (version_compare(PHP_VERSION, '5.0', '>=') &&
            version_compare(PHP_VERSION, '5.1', '<')) {

           $eof = !$eof;
        }
        return $eof;
    }

    /**
     * Returns the position of the file pointer, i.e. its offset into the file
     * stream. Implements support for ftell().
     * @return  int
     */
    function stream_tell()
    {
        return $this->pos;
    }

    /**
     * Implements support for fseek().
     * @param   int  byte offset
     * @param   int  SEEK_SET, SEEK_CUR or SEEK_END
     * @return  bool
     */
    function stream_seek($offset, $whence)
    {
        if ($whence == SEEK_SET && $offset >= 0) {
            $this->pos = $offset;
        } elseif ($whence == SEEK_CUR && -$offset <= $this->pos) {
            $this->pos += $offset;
        } elseif ($whence == SEEK_END && -$offset <= sizeof($this->data)) {
            $this->pos = strlen($this->data) + $offset;
        } else {
            return false;
        }
        return true;
    }

    /**
     * Implements support for fstat(). Currently the only supported field is
     * "size".
     * @return  array
     */
    function stream_stat()
    {
        return array(
            'size' => strlen($this->data),
            );
    }

    // Methods used by stream_wrapper_register() that are not implemented:
    // bool stream_flush ( void )
    // int stream_write ( string data )
    // bool rename ( string path_from, string path_to )
    // bool mkdir ( string path, int mode, int options )
    // bool rmdir ( string path, int options )
    // bool dir_opendir ( string path, int options )
    // array url_stat ( string path, int flags )
    // string dir_readdir ( void )
    // bool dir_rewinddir ( void )
    // bool dir_closedir ( void )
}

//@todo Delete this code once PHP 5.2 is running
if (function_exists('sys_get_temp_dir') === false)
{
    function sys_get_temp_dir()
    {
        $temp_file = tempnam("?*", ''); //use invalid filesystem chars so it always retuns the system tmp path
        if ($temp_file){
            $temp_dir = realpath(dirname($temp_file));
            unlink($temp_file);
            return $temp_dir;
        }
        else
            return false;
    }
}
?>
