<?php
    require_once "grandprix.model.base.php";
    
    /**
     * Represents a textbox column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class TextBoxColumn extends EditableBoundColumnModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_TEXTBOX;
        }
    }
    
    /**
     * Represents a numberbox column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class NumberBoxColumn extends EditableBoundColumnModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_NUMBERBOX;
        }
    }
    
    /**
     * Represents a combobox column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ComboBoxColumn extends EditableBoundColumnModelBase
    {
        private $options;
        
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_COMBOBOX;
            $this->options = array('test', 'test0', 'test1');
        }
        
        /**
         * Sets the options available for the ComboBox
         *
         * @param array $options
         */
        public function setOptions($options)
        {
            $this->options = $options;
        }
        
        /**
         * Gets the options for the ComboBox
         * If $asJson equals true, the return value is a Json encoded string
         * other wise, it will return an array
         *
         * @param bool $asJson
         *
         * @return array
         */
        public function getOptions($asJson = true)
        {
            return $asJson ? JSON::encode($this->options) : $this->options;
        }
    }
    
    /**
     * Represents a text column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class TextColumn extends BoundColumnModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_STRING;
        }
    }
    
    /**
     * Represents a numeric column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class NumericColumn extends BoundColumnModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_NUMBER;
        }
    }
    
    /**
     * Represents a numeric column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class DateColumn extends BoundColumnModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_DATE;
        }
    }
    
    /**
     * Represents a action column
     *
     * @package Grandprix
     * @subpackage Model
     */
    class ActionColumn extends BoundColumnModelBase
    {
        /**
         * Creates a new instance of the class
         *
         * @param str $name
         * @param str $caption
         */
        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->columnType = self::COLUMN_TYPE_ACTION;
        }
    }
    
    /**
     * Represents a data table
     *
     * @package Grandprix
     * @subpackage Model
     */
    class DataTable extends DataSourceControlModelBase
    {
        const DEFAULT_PAGE_SIZE = 50;
        const SORT_ASC = 'asc';
        const SORT_DESC = 'desc';
        
        protected $totalRows;
        protected $pageSize;
        protected $currentPage;
        protected $keyName;
        protected $keyValue;
        protected $whereClause;
        protected $isPaged;
        protected $groupBy;
        protected $selectedRow;
        protected $isClickable;
        protected $isExportable;
        protected $isSortable;
        protected $isScrollable;
        protected $isEditable;
        protected $sortColumn;
        protected $sortDir;
        
        /**
         * @var array
         */
        protected $dataSource = null;
        
        public function __construct($name, $caption, $keyName = 'id')
        {
            parent::__construct($name, $caption);
            
            $this->keyName = $keyName;
            $this->totalRows = 0;
            $this->pageSize = self::DEFAULT_PAGE_SIZE;
            $this->currentPage = 1;
            $this->whereClause = '';
            $this->isPaged = false;
            $this->selectedRow = null;
            $this->groupBy = '';
            $this->isClickable = true;
            $this->isExportable = false;
            $this->isSortable = true;
            $this->isScrollable = true;
            $this->isEditable = false;
            
            if (isset($_REQUEST['datatable_' . $name . '_key']))
                $this->keyValue = $_REQUEST['datatable_' . $name . '_key'];
    
            if (isset($_REQUEST['datatable_' . $name . '_selected']))
                $this->selectedRow = $_REQUEST['datatable_' . $name . '_selected'];
            
            $this->currentPage = isset($_REQUEST['datatable_' . $name . '_offset']) ? $_REQUEST['datatable_' . $name . '_offset'] : 1;
            $this->sortColumn = isset($_REQUEST['datatable_' . $name . '_sortColumn']) ? $_REQUEST['datatable_' . $name . '_sortColumn'] : '';
            $this->sortDir = isset($_REQUEST['datatable_' . $name . '_sortDir']) ? $_REQUEST['datatable_' . $name . '_sortDir'] : self::SORT_ASC;
        }
        
        /**
         * Gets the currently defined column name for sorting
         *
         * return string;
         */
        public function getSortColumn()
        {
            return $this->sortColumn;
        }
        
        /**
         * Sets the column name for sorting
         *
         * @param string $value
         */
        public function setSortColumn($value)
        {
            $this->sortColumn = $value;
        }
        
        /**
         * Gets the current sorting direction
         *
         * @return string
         */
        public function getSortDir()
        {
            return $this->sortDir;
        }
        
        /**
         * Sets the sorting direction
         *
         * @param string $value
         */
        public function setSortDir($value)
        {
            $this->sortDir = $value;
        }
        
        /**
         * Gets the column name defined as the key
         *
         * @return string
         */
        public function getKeyName()
        {
            return $this->keyName;
        }
        
        /**
         * Sets the column name defined as key
         *
         * @param string $value
         */
        public function setKeyName($value)
        {
            $this->keyName = $value;
        }
        
        /**
         * Gets whether the DataTable rows are editable or not
         *
         * @return bool
         */
        public function getIsEditable()
        {
            return $this->isEditable;
        }
        
        /**
         * Gets whether the DataTable rows are editable or not
         *
         * @param bool $value
         */
        public function setIsEditable($value)
        {
            $this->isEditable = $value;    
        }
        
        /**
         * Sets the total rows in the DataSet
         *
         * @param int $value
         */
        public function setTotalRows($total)
        {
            $this->totalRows = $total;
        }
        
        /**
         * Gets the total rows in the DataSet
         *
         * @return int
         */
        public function getTotalRows()
        {
            return $this->totalRows;
        }
        
        /**
         * Gets wheter the DataTable is clickable or not
         *
         * @return bool
         */
        public function getIsClickable()
        {
            return $this->isClickable;
        }
        
        /**
         * Sets wheter the DataTable is clickable or not
         *
         * @param bool $value
         */
        public function setIsClickable($value)
        {
            $this->isClickable = $value;    
        }
        
        /**
         * Gets selected row value defined by the keyname
         *
         * @return mixed
         */
        public function getSelectedRow()
        {
            return $this->selectedRow;
        }
        
        /**
         * Gets group column
         *
         * @return string
         */
        public function getGroupColumn()
        {
            return $this->groupBy;
        }
    
        /**
         * Sets group column
         *
         * @param string $value
         */
        public function setGroupColumn($value)
        {
            $this->groupBy = $value;
        }
        
        /**
         * Gets is paged
         *
         * @return bool
         */
        public function getIsPaged()
        {
            return $this->isPaged;
        }
    
        /**
         * Sets is paged
         *
         * @param bool $value
         */
        public function setIsPaged($value)
        {
            $this->isPaged = $value;
        }
        
        /**
         * Gets the current page size
         *
         * @return int
         */
        public function getPageSize()
        {
            return $this->pageSize;
        }
        
        /**
         * Sets the current page size
         *
         * @param int $value
         */
        public function setPageSize($value)
        {
            $this->pageSize = $value;
        }
        
        /**
         * Gets the current page
         *
         * @return int
         */
        public function getCurrentPage()
        {
            return $this->currentPage;
        }
    
        /**
         * Sets the current page
         *
         * @param int $value
         */
        public function setCurrentPage($value)
        {
            $this->currentPage = $value;
        }
        
        /**
         * Gets wether the datatable is exportable or not
         *
         * @return bool
         */
        public function getIsExportable()
        {
            return $this->isExportable;
        }
        
        /**
         * Sets wether the datatable is exportable or not
         *
         * @param bool
         */
        public function setIsExportable($value)
        {
            $this->isExportable = $value;    
        }

        /**
         * Gets wether the datatable is sortable or not
         *
         * @return bool
         */
        public function getIsSortable()
        {
            return $this->isSortable;
        }
        
        /**
         * Sets wether the datatable is sortable or not
         *
         * @param bool
         */
        public function setIsSortable($value)
        {
            $this->isSortable = $value;    
        }

        /**
         * Gets wether the datatable is scrollable or not
         *
         * @return bool
         */
        public function getIsScrollable()
        {
            return $this->isScrollable;
        }
        
        /**
         * Sets wether the datatable is scrollable or not
         *
         * @param bool
         */
        public function setIsScrollable($value)
        {
            $this->isScrollable = $value;    
        }
    }
    
    /**
     * Represents a Line Chart
     *
     * @package Grandprix
     * @subpackage Model
     */
    class Chart extends DataSourceControlModelBase
    {
        /**
         * @var array
         */
        protected $dataFields;
        protected $baseField;

        public function __construct($name, $caption)
        {
            parent::__construct($name, $caption);
            $this->dataFields = array();
        }
        
        /**
         * Sets the column name to use as the x axis field
         *
         * @param string name;
         */
        public function setBaseField($name)
        {
            $this->baseField = $name;
        }
        
        /**
         * Gets the column name that is used as the x axis field
         *
         * @return string
         */
        public function getBaseField()
        {
            return $this->baseField;
        }

        /**
         * Adds a Data Field to be displayed in the chart
         *
         * @param string $name
         */
        public function addDataField($name)
        {
            if (array_search($name, $this->dataFields, true) === true)
                throw new WorshipException(WorshipException::EX_INVALIDPARAMMETHOD, "data field '$name' already exists");
            
            $this->dataFields[$name] = $name;
        }
        
        /**
         * Gets the list of Data Fields added to the chart
         *
         * @return array
         */
        public function getDataFields()
        {
            return $this->dataFields;
        }
        
        /**
         * Removes a Data Field from the Data Fields
         *
         * @param string $name
         */
        public function removeDataField($name)
        {
            unset($this->dataFields[$name]);
        }
    }

    class LineChart extends Chart { }

    class BarChart extends Chart { }
?>