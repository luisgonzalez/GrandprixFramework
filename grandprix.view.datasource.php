<?php
    require_once "grandprix.view.php";
    
    /**
     * Provides a standard DataSource object renderer base.
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    abstract class HtmlDataSourceView extends HtmlView
    {
        /**
         * Creates a new instance of this class
         *
         * @param IModel $model
         */
        public function __construct(&$model)
        {
            parent::__construct($model);
            
            $this->renderCallbacks['DataTable'] = array('HtmlDataSourceRenderCallbacks', 'renderDataTable');
            $this->renderCallbacks['LineChart'] = array('HtmlDataSourceRenderCallbacks', 'renderChart');
            $this->renderCallbacks['ActionColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderActionColumn');
            $this->renderCallbacks['TextColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderTextColumn');
            $this->renderCallbacks['NumericColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderTextColumn');
            $this->renderCallbacks['DateColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderTextColumn');
            $this->renderCallbacks['TextBoxColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderTextColumn');
            $this->renderCallbacks['ComboBoxColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderTextColumn');
            $this->renderCallbacks['NumberBoxColumn'] = array('HtmlDataSourceRenderCallbacks', 'renderTextColumn');
        }
        
        public static function registerDependencies()
        {
            self::registerCommonDependencies();
            
            $commonCssPath = 'lib/css/common.css';
            $commonPath = 'lib/javascript/common.js';
            
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'CommonCSS', $commonCssPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'Common', $commonPath);
        }
    }
    
    /**
     * Provides a standard DataTable object renderer.
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    class HtmlDataTableView extends HtmlDataSourceView
    {
        public function __construct(&$model)
        {
            parent::__construct($model);
            $this->cssClass = 'datatableview';
            
            self::registerDependencies();
        }
        
        public static function registerDependencies()
        {
            parent::registerDependencies();
            
            $yuiDtPath = 'lib/yui/datatable-min.js';
            $paginatorPath = 'lib/yui/paginator-min.js';

            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIDt', $yuiDtPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIPaginator', $paginatorPath);
        }
    }
    
    /**
     * Provides a standard Chart object renderer.
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    abstract class HtmlChartView extends HtmlDataSourceView
    {
        protected static $isFlashLocationRendered = false;
        protected static $specificCssClass;
        
        public function __construct(&$model, $cssClass)
        {
            parent::__construct($model);
            $this->cssClass = 'chartview';
            $this->specificCssClass = $cssClass;
            self::registerDependencies();
        }
        
        public static function registerDependencies()
        {
            parent::registerDependencies();
            
            $yuiChartPath = 'lib/yui/charts-min.js';
            $jsChart = 'lib/javascript/chart.js';
            
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUICharts', $yuiChartPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'JsChart', $jsChart);
        }
        
        public function render()
        {
            if($this->getIsFlashLocationRendered() === false)
            {
                $tw = HtmlWriter::getInstance();
                $tw->openScript();
                $tw->addAttribute('language','javascript');
                $tw->writeRaw('YAHOO.widget.Chart.SWFURL="../lib/yui/charts.swf";');
                $tw->closeScript();
                
                $this->setIsFlashLocationRendered(true);
            }
            $tw->openDiv();
            $tw->addAttribute('class', $this->specificCssClass);
            
            parent::render();
            
            $tw->closeDiv();
        }
        
        public function getIsFlashLocationRendered()
        {
            return self::$isFlashLocationRendered;
        }
        
        protected function setIsFlashLocationRendered($value)
        {
            self::$isFlashLocationRendered = (bool)$value;
        }
        
        public function getSpecificCssClass()
        {
            return $this->specificCssClass;
        }
    }
    
    class HtmlLineChartView extends HtmlChartView
    {
        public function __construct(&$model)
        {
            parent::__construct($model, 'linechart');
        }
    }
    
    class HtmlBarChartView extends HtmlChartView
    {
        public function __construct(&$model)
        {
            parent::__construct($model, 'barchart');
        }
    }
    /**
     * Contains static callback methods to render datatable HTML controls
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    class HtmlDataSourceRenderCallbacks
    {
        /**
         * Renders a DataTable
         * 
         * @param DataTable $model
         * @param HtmlDataTableView $view
         */
        static public function renderDataTable(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
            
            $tw->openForm();
            $tw->addAttribute('id', 'datatable_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('class', $view->getCssClass());
            $tw->addAttribute('action', $_SERVER['PHP_SELF']);
            $tw->addAttribute('method', 'post');
            $tw->addAttribute('clickable', $model->getIsClickable() ? 'true' : 'false');
            $tw->addAttribute('sortable', $model->getIsSortable() ? 'true' : 'false');
            $tw->addAttribute('scrollable', $model->getIsScrollable() ? 'true' : 'false');
            $tw->addAttribute('hastotals', $model->getHasTotals() ? 'true' : 'false');
            $tw->addAttribute('editable', $model->getIsEditable() ? 'true' : 'false');
            $tw->addAttribute('paged', $model->getIsPaged() ? 'true' : 'false');
            $tw->addAttribute('keyname', $model->getKeyName());
            $tw->addAttribute('totalRecords', $model->getTotalRows());
            $tw->addAttribute('pageSize', $model->getPageSize());
            
            // Add the postback hidden field to signal postbacks
            $postBackIdentifier = 'postBackFlag_' . $model->getName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $postBackIdentifier);
            $tw->addAttribute('name', $postBackIdentifier);
            $tw->addAttribute('value', '1');
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', 'datatable_' . $model->getName() . "_offset");
            $tw->addAttribute('name', 'datatable_' . $model->getName() . "_offset");
            $tw->addAttribute('value', $model->getCurrentPage());
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', 'datatable_' . $model->getName() . "_sortColumn");
            $tw->addAttribute('name', 'datatable_' . $model->getName() . "_sortColumn");
            $tw->addAttribute('value', $model->getSortColumn());
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', 'datatable_' . $model->getName() . "_sortDir");
            $tw->addAttribute('name', 'datatable_' . $model->getName() . "_sortDir");
            $tw->addAttribute('value', $model->getSortDir());
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('class', $view->getCssClass() . '-table-group');
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('value', $model->getGroupColumn());
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('class', $view->getCssClass() . '-table-loaded');
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('value', 'false');
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('class', $view->getCssClass() . '-table-selected');
            $tw->addAttribute('id', 'datatable_' . $model->getName() . '_selected');
            $tw->addAttribute('name', 'datatable_' . $model->getName() . '_selected');
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('value', $model->getSelectedRow());
            $tw->closeInput();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getMasterCssClass() . '-caption');
            $tw->writeContent($model->getCaption());
            $tw->closeDiv();
            
            if ($model->getIsEditable())
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getMasterCssClass() . '-subcaption');
                $tw->openInput();
                $tw->addAttribute('type', 'button');
                $tw->addAttribute('value', 'Add New');
                $tw->closeInput();
                
                $tw->openInput();
                $tw->addAttribute('type', 'button');
                $tw->addAttribute('value', 'Save All');
                $tw->closeInput();
                $tw->closeDiv();
            }

            self::renderDataSource($model, $view);
            
            $labelsControls = $model->getChildren()->getControlNames(false, 'RawField');
            $buttonControls = $model->getChildren()->getControlNames(false, 'ButtonModelBase');
            $persistorControls = $model->getChildren()->getControlNames(false, 'PersistorField');
            
            foreach ($persistorControls as $persistor)
            {
                HtmlRenderCallbacks::renderPersistorField($model->getChildren()->getControl($persistor), $view);
            }
            
            if ($model->getIsPaged() || $model->getIsExportable() != false ||
                (count($buttonControls) > 0 || count($labelsControls) > 0))
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-tools');
                
                if ($model->getIsPaged())
                {
                    $tw->openDiv();
                    $tw->addAttribute('id', 'datatable_' . $model->getName() . '_paginator');
                    $tw->closeDiv(true);
                }
                
                foreach ($labelsControls as $labelName)
                {
                    $currentControl = $model->getChildren()->getControl($labelName);
                    if ($currentControl->getVisible() === false) continue;
                    
                    HtmlRenderCallbacks::renderRawField($currentControl, $view);
                }
                
                foreach ($buttonControls as $buttonName)
                {
                    $currentControl = $model->getChildren()->getControl($buttonName);
                    if ($currentControl->getVisible() === false) continue;
                    
                    HtmlRenderCallbacks::renderButton($currentControl, $view);
                }
                
                if ($model->getIsExportable() != false)
                {
                    $xls = false;
                    $pdf = false;
                    
                    if ($model->getIsExportable() === true)
                    {
                        $xls = true;
                        $pdf = true;
                    }
                    
                    if ($model->getIsExportable() == 'xls') $xls = true;
                    if ($model->getIsExportable() == 'pdf') $pdf = true;
                    
                    $tw->openInput();
                    $tw->addAttribute('type', 'hidden');
                    $tw->addAttribute('id', 'datatable_' . $model->getName() . '_export');
                    $tw->addAttribute('name', 'datatable_' . $model->getName() . '_export');
                    $tw->addAttribute('class', $view->getCssClass() . '-export');
                    $tw->addAttribute('value', '');
                    $tw->closeInput();
                    
                    if ($xls)
                    {
                        $tw->openDiv();
                        $tw->addAttribute('class', $view->getCssClass() . '-export-excel button-toolbar');
                        $tw->addAttribute('export', 'xls');
                        $tw->writeContent('Exportar a Excel');
                        $tw->closeDiv(true);
                    }
                    
                    if ($pdf)
                    {
                        $tw->openDiv();
                        $tw->addAttribute('class', $view->getCssClass() . '-export-pdf button-toolbar');
                        $tw->addAttribute('export', 'pdf');
                        $tw->writeContent('Exportar a PDF');
                        $tw->closeDiv(true);
                    }
                }

                $tw->closeDiv();
            }
            
            $tw->closeForm();
        }
        
        /**
         * Renders a Chart
         *
         * @param $model LineChart
         * @param $view HtmlChartView
         */
        public static function renderChart(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();

            $tw->openDiv();
            $tw->addAttribute('class', $view->getMasterCssClass() . '-caption');
            $tw->writeContent($model->getCaption());
            $tw->closeDiv();

            $tw->openForm();
            $tw->addAttribute('id', 'form_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('class', $view->getCssClass());
            $tw->addAttribute('isAsync', ($view->getIsAsync()) ? 'true' : 'false');
            $tw->addAttribute('action', $_SERVER['PHP_SELF']);
            $tw->addAttribute('method', 'post');
            $tw->addAttribute('enctype', 'multipart/form-data');
            
            $postBackIdentifier = 'postBackFlag_' . $model->getName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $postBackIdentifier);
            $tw->addAttribute('name', $postBackIdentifier);
            $tw->addAttribute('value', '1');
            $tw->closeInput();

            $tw->openDiv();
            $tw->addAttribute('id', 'chart_' . $model->getName() . '-container');
            $tw->addAttribute('class', $view->getCssClass() . '-container');
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-datafield');
            $tw->addAttribute('style','display:none');
            
            $tw->openSpan();
            $tw->addAttribute('class', $view->getCssClass() . '-datafield');
            $tw->addAttribute('xfield', '1');
            $tw->writeContent($model->getBaseField());
            $tw->closeSpan();

            foreach($model->getDataFields() as $dataField)
            {
                $tw->openSpan();
                $tw->addAttribute('class', $view->getCssClass() . '-datafield');
                $tw->addAttribute('xfield', '0');
                $tw->writeContent($dataField);
                $tw->closeSpan();
            }
            
            $tw->closeDiv();

            self::renderDataSource($model, $view);
            
            $tw->closeDiv();
            
            $tw->closeForm();
        }
        
        /**
         * Renders a datasource
         *
         * @param DataSource $model
         * @param HtmlDataSourceView $model
         */
        protected static function renderDataSource(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-table');

            $tw->openTable();
            $tw->addAttribute('class', $view->getCssClass() . '-table');
            $tw->addAttribute('summary', '');
            $tw->addAttribute('keyname', $model->getKeyName());
    
            $tw->openTHead();
            $tw->openTr();
    
            foreach($model->getColumnNames(false) as $currentControl)
            {
                $control = $model->getChildren()->getControl($currentControl, true, 'BoundColumnModelBase');
                
                $tw->openTh();
                $tw->addAttribute('class', $view->getCssClass() . '-table-header');
                $tw->addAttribute('colname', $currentControl);
                $tw->addAttribute('coltype', $control->getColumnType());
                $tw->addAttribute('visible', $control->getVisible() === false || $currentControl == $model->getKeyName() ? 'false' : 'true');
                if (is_a($control, 'ComboBoxColumn'))   
                    $tw->addAttribute('options', $control->getOptions());
                    
                $tw->writeContent($control->getCaption());
                $tw->closeTh();
            }
            
            $tw->closeTr();
            $tw->closeTHead();
            
            $tw->openTBody();
            
            $hasTotals = $model->getHasTotals();
            $rowNames = $model->getRowNames(false);
            $totalRows = count($rowNames);
            $lastRowName = '';
            $i = 0;

            foreach($rowNames as $rowName)
            {
                if($hasTotals && ++$i == $totalRows)
                {
                    $lastRowName = $rowName;
                    break;
                }
                
                $row = $model->getChildren()->getControl($rowName, false);
                $row->applyData($model->getKeyName());
                HtmlDataSourceRenderCallbacks::renderCells($row, $view, $model->getName());
            }
            
            $tw->closeTBody();

            if ($hasTotals)
            {
                $tw->openTFoot();

                $row = $model->getChildren()->getControl($lastRowName, false);
                $row->applyData($model->getKeyName());
                HtmlDataSourceRenderCallbacks::renderCells($row, $view);

                $tw->closeTFoot();
            }
            
            $tw->closeTable();
            $tw->closeDiv();
        }
        
        /**
         * Helper method to render cells controls within a row model.
         *
         * @param RowModelBase $model
         * @param HtmlDataTableView $view
         */
        public static function renderCells(&$model, &$view, $class = '')
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openTr();
            $tw->addAttribute('key', $model->getKeyValue());
            
            if ($model->getKeyValue() != '' &&
                isset($_REQUEST['datatable_' . $class . '_selected']) &&
                $model->getKeyValue() == $_REQUEST['datatable_' . $class . '_selected'])
            {
                $tw->addAttribute('class', $view->getCssClass() . '-table-row selected');
            }
            else
            {
                $tw->addAttribute('class', $view->getCssClass() . '-table-row');
            }
            
            $callbacks = $view->getRenderCallbacks();
            
            foreach($model->getChildren()->getControls() as $currentControl)
            {
                $controlClassName = get_class($currentControl);
                
                if (key_exists($controlClassName, $callbacks))
                {
                    $renderCallback = $callbacks[$controlClassName];
                    
                    if (is_callable($renderCallback, false))
                    {
                        call_user_func_array($renderCallback, array(&$currentControl, &$view));
                        continue;
                    }
                }
     
                throw new Exception("Invalid render callback for model of type '" . $controlClassName . "'");
            }
    
            $tw->closeTr();
        }
        
        /**
         *
         * @param ActionColumn $model
         * @param HtmlDataTableView $view
         */
        static public function renderActionColumn(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openTd();
            $tw->addAttribute('class', $view->getCssClass() . '-table-cell');
            $tw->writeContent($model->getCaption());
            $tw->closeTd();
        }
        
        /**
         *
         * @param TextColumn $model
         * @param HtmlDataTableView $view
         */
        static public function renderTextColumn(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openTd();
            $tw->addAttribute('class', $view->getCssClass() . '-table-cell');
            $tw->writeContent($model->getValue(), true, true, false);
            $tw->closeTd();
        }
    }
    
    /**
     * Provides a standard Tree object renderer.
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    class HtmlTreeView extends HtmlView
    {
        /**
         * Creates a new instance of this class
         *
         * @param IModel $model
         */
        public function __construct(&$model)
        {
            parent::__construct($model);
            
            $this->cssClass = 'treeview';
            $this->frameWidth = '300px';
            
            $this->renderCallbacks['Tree'] = array('HtmlTreeRenderCallbacks', 'renderTree');
            $this->renderCallbacks['SearchField'] = array('HtmlTreeRenderCallbacks', 'renderSearchField');
            $this->renderCallbacks['CheckBox'] = array('HtmlRenderCallbacks', 'renderCheckBox');
            $this->renderCallbacks['ToggleButton'] = array('HtmlRenderCallbacks', 'renderToggleButton');
            
            self::registerDependencies();
        }
        
        public static function registerDependencies()
        {
            self::registerCommonDependencies();
            
            $treeCSSPath = 'lib/yui/sam/treeview.css';
            $treePath = 'lib/yui/treeview-min.js';
            $commonPath = 'lib/javascript/common.js';
            $commonCssPath = 'lib/css/common.css';
            
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'YUITreeCSS', $treeCSSPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUITree', $treePath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'Common', $commonPath);
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'CommonCSS', $commonCssPath);
        }
    }
    
    /**
     * Contains static callback methods to render tree HTML controls
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    class HtmlTreeRenderCallbacks
    {
        /**
         * Helper method to render a tree.
         *
         * @param Tree $model
         * @param HtmlTreeView $view
         */
        public static function renderTree(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openForm();
            $tw->addAttribute('id', 'tree_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('class', 'formview');
            $tw->addAttribute('action', $_SERVER['PHP_SELF']);
            $tw->addAttribute('method', 'post');
            $tw->addAttribute('type', 'tree');
            
            // Add the postback hidden field to signal postbacks
            $postBackIdentifier = 'postBackFlag_' . $model->getName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $postBackIdentifier);
            $tw->addAttribute('name', $postBackIdentifier);
            $tw->addAttribute('value', '1');
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('id', 'tree_' . $model->getName() . '_selected');
            $tw->addAttribute('name', 'tree_' . $model->getName() . '_selected');
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('value', $model->getCurrentItem());
            $tw->closeInput();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getMasterCssClass() . '-caption');
            $tw->writeContent($model->getCaption());
            $tw->closeDiv();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getMasterCssClass() . '-content');
            
            HtmlFormView::renderChildren($model, $view);
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-tree');
            $tw->addAttribute('id', $model->getName());
            
            self::renderNode($model->getRootNode(), $model->getCurrentItem());
            
            $tw->closeDiv();
            
            $tw->closeDiv();
            
            $tw->closeForm();
        }
        
        /**
         * Helper method to render a search field.
         *
         * @param SearchField $model
         * @param HtmlTreeView $view
         */
        public static function renderSearchField(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'view-searchfield-container');
            
            $tw->openInput();
            $tw->addAttribute('class', 'view-searchfield');
            $tw->addAttribute('type', 'text');
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('overtext', $model->getCaption());
            
            if (isset($_REQUEST[$model->getName()]))
                $tw->addAttribute('value', $_REQUEST[$model->getName()]);
            else
                $tw->addAttribute('value', $model->getCaption());
                
            $tw->closeInput();
            
            $tw->closeDiv();
        }
        
        /**
         * Helper method to render a tree node.
         *
         * @param array $node
         * @param string $selectedValue
         */
        protected static function renderNode($node, $selectedValue)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openUl();
            
            foreach($node as $key => $value)
            {
                $tw->openLi();
                
                if (is_array($value))
                {
                    if (key_exists($selectedValue, $value))
                        $tw->addAttribute("class", "expanded");
                    
                    $tw->addAttribute('yuiConfig', '{"type":"menu"}');
                    
                    $tw->writeContent($key);
                    self::renderNode($value, $selecteValue);
                }
                else
                {
                    $tw->addAttribute('yuiConfig', '{"type":"menu", "nodeId" : "' . $key . '"}');
                    $tw->writeContent($value);
                }
                
                $tw->closeLi();
            }
            
            $tw->closeUl(true);
        }
    }
    
    /**
     * Abstract class to creates Excel Views
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    abstract class ExcelView implements IRenderable
    {
        /**
         * @var IModel
         */
        protected $model;
        protected $renderCallbacks;
        /**
         * @var Spreadsheet_Excel_Writer
         */
        protected $writer;
        /**
         * @var Spreadsheet_Excel_Writer_Worksheet
         */
        protected $worksheet;
        
        /**
         * Constructs an Instance of ExcelView
         *
         * @param DataSourceControlModelBase $model
         */
        public function __construct(&$model)
        {
            require_once("grandprix.excel.php");
            
            $this->model = $model;

            $this->writer = new Spreadsheet_Excel_Writer();
            $this->worksheet =& $this->writer->addWorksheet();

            $this->renderCallbacks = array();
        }
        
        public function &getModel()
        {
            return $this->model;
        }
        
        /**
         * Gets the Worksheet to write on
         *
         * @return Spreadsheet_Excel_Writer_Worksheet
         */
        public function &getWorksheet()
        {
            return $this->worksheet;
        }
        
        /**
         * Gets the Excel Writer
         *
         * @return Spreadsheet_Excel_Writer
         */
        public function &getWriter()
        {
            return $this->writer;
        }
        
        public function &getRenderCallbacks()
        {
            return $this->renderCallbacks;
        }

        /**
         * Sends and renders the Excel File
         *
         * @param string $filename
         */
        public function render($filename = '')
        {
            if ($filename == '')
                $filename = $this->model->getCaption();

            call_user_func_array($this->renderCallbacks[get_class($this->model)], array(&$this->model, &$this));

            $this->writer->send($filename . '.xls');
            $this->writer->close();
        }
    }
    
    /**
     * Represents a Excel View with DataSource
     * 
     * @package WorshipKitchen
     * @subpackage View
     */
    class ExcelDataSourceView extends ExcelView
    {
        protected $captionFormat;
        protected $headersFormat;
        protected $totalsFormat;
        protected $dataFormat;
        
        public function __construct(&$model)
        {
            parent::__construct($model);

            $this->renderCallbacks['DataTable'] = array('ExcelDataSourceRenderCallbacks', 'renderDataTable');
            $this->renderCallbacks['TextColumn'] = array('ExcelDataSourceRenderCallbacks', 'renderTextColumn');
            $this->renderCallbacks['NumericColumn'] = array('ExcelDataSourceRenderCallbacks', 'renderGenericColumn');
            $this->renderCallbacks['DateColumn'] = array('ExcelDataSourceRenderCallbacks', 'renderGenericColumn');
            
            $this->captionFormat =& $this->writer->addFormat();
            $this->captionFormat->setBold();
            $this->captionFormat->setSize(12);
            
            $this->headersFormat =& $this->writer->addFormat();
            $this->headersFormat->setBold();
            $this->headersFormat->setSize(10);
            
            $this->dataFormat = $this->writer->addFormat();
            $this->dataFormat->setSize(10);
            
            $this->totalsFormat =& $this->writer->addFormat();
            $this->totalsFormat->setBold();
            $this->totalsFormat->setItalic();
        }
        
        public function &getCaptionFormat()
        {
            return $this->captionFormat;
        }

        public function &getHeadersFormat()
        {
            return $this->headersFormat;
        }

        public function &getTotalsFormat()
        {
            return $this->totalsFormat;
        }
        
        public function &getDataFormat()
        {
            return $this->dataFormat;
        }
    }
    
    /**
     * Contains static callback methods to render datatable Excel controls
     *
     * @package WorshipKitchen
     * @subpackage View
     */
    class ExcelDataSourceRenderCallbacks
    {
        /**
         * Renders a DataTable
         * 
         * @param DataTable $model
         * @param ExcelDataSourceView $view
         */
        public static function renderDataTable(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $view->getWorksheet()->write(0, 0, $model->getCaption(), $view->getCaptionFormat());
            
            self::renderDataSource($model, $view, 0, 1);
        }
        
        /**
         * Renders a DataSource
         *
         * @param DataSourceControlModelBase $model
         * @param ExcelDataSourceView $view
         * @param int $colsOffset
         * @param int $rowsOffset
         */
        protected static function renderDataSource(&$model, &$view, $colsOffset = 0, $rowsOffset = 0)
        {
            $worksheet =& $view->getWorksheet();
            
            $currentColumn = $colsOffset;
            foreach($model->getColumnNames(false) as $currentControl)
            {
                /**
                 * @var BoundColumnModelBase
                 */
                $control = $model->getChildren()->getControl($currentControl, true, 'BoundColumnModelBase');
                $worksheet->write($rowsOffset, $currentColumn++, $control->getCaption(), $view->getHeadersFormat());
            }
            
            $hasTotals = $model->getHasTotals();
            $rowNames = $model->getRowNames(false);
            $totalRows = count($rowNames);
            $lastRowName = '';

            foreach($rowNames as $rowName)
            {
                if($hasTotals && ++$i == $totalRows)
                {
                    $lastRowName = $rowName;
                    break;
                }
                $row = $model->getChildren()->getControl($rowName, false);
                $row->applyData($model->getKeyName());
                ExcelDataSourceRenderCallbacks::renderCells($row, $view, $colsOffset, ++$rowsOffset);
            }
            
            if ($hasTotals)
            {
                $row = $model->getChildren()->getControl($lastRowName, false);
                $row->applyData($model->getKeyName());
                ExcelDataSourceRenderCallbacks::renderCells($row, $view, $colsOffset, ++$rowsOffset, true);
            }
        }
        
        /**
         * Helper method to render cells controls within a row model.
         *
         * @param RowModelBase $model
         * @param HtmlDataTableView $view
         * @param int $colsOffset
         * @param int $rowsOffset
         * @param bool $isTotal
         */
        public function renderCells(&$model, &$view, $colsOffset, $rowsOffset, $isTotal = false)
        {
            $callbacks = $view->getRenderCallbacks();
            
            foreach($model->getChildren()->getControls() as $currentControl)
            {
                if ($currentControl->getVisible() === false) continue;
                
                $controlClassName = get_class($currentControl);
                
                if (key_exists($controlClassName, $callbacks))
                {
                    $renderCallback = $callbacks[$controlClassName];
                    
                    if (is_callable($renderCallback, false))
                    {
                        call_user_func_array($renderCallback, array(&$currentControl, &$view, $colsOffset++, $rowsOffset, $isTotal));
                        continue;
                    }
                }
     
                throw new Exception("Invalid render callback for model of type '" . $controlClassName . "'");
            }
        }
        
        /**
         * 
         * @param TextColumn $model
         * @param ExcelDataSourceView $view
         * @param int $colsOffset
         * @param int $rowsOffset
         * @param bool $isTotal
         */
        public static function renderTextColumn(&$model, &$view, $colsOffset, $rowsOffset, $isTotal)
        {
            $format = $isTotal ? $view->getTotalsFormat() : $view->getDataFormat();

            $view->getWorksheet()->writeString($rowsOffset, $colsOffset, $model->getValue(), $format);
        }

        /**
         * 
         * @param BoundColumnModelBase $model
         * @param ExcelDataSourceView $view
         * @param int $colsOffset
         * @param int $rowsOffset
         * @param bool $isTotal
         */
        public static function renderGenericColumn(&$model, &$view, $colsOffset, $rowsOffset, $isTotal)
        {
            $format = $isTotal ? $format = $view->getTotalsFormat() : $view->getDataFormat();

            $view->getWorksheet()->write($rowsOffset, $colsOffset, $model->getValue(), $format);
        }
    }
    
    /**
     * Class to create PDF views
     * 
     * @package WorshipKitchen
     * @subpackage View
     */
    class PdfDataSourceView extends HtmlDataSourceView
    {
        /**
         * @var DOMPDF
         */
        protected $pdfWriter;
        
        /**
         * @var string
         */
        protected $htmlContent;
        
        /**
         * @var string. Html containing the header, such as an image
         */
        protected $header;

        public function __construct(&$model)
        {
            parent::__construct($model);

            $this->cssClass = 'pdfdatasource';
        }
        
        public static function renderDependencies()
        {
            $pdfCssPath = 'lib/css/pdf.css';

            echo "<style>" . file_get_contents($pdfCssPath) . "</style>";
        }
        
        /**
         * Sends and renders the Pdf File
         *
         * @param string $filename
         */
        public function render($filename = '')
        {
            if ($filename == '') $filename = $this->model->getCaption();

            ob_start();
            self::renderDependencies();
            
            call_user_func_array($this->renderCallbacks[get_class($this->model)], array(&$this->model, &$this));

            $this->htmlContent = ob_get_clean();

            ini_set('memory_limit','32M'); // Render is a little memory demanding
            
            $this->pdfWriter = new DOMPDF();
            $this->pdfWriter->set_paper('legal', 'landscape');
            $this->pdfWriter->load_html($this->htmlContent);
            $this->pdfWriter->render();
            $this->pdfWriter->stream($filename . '.pdf');

            exit(0);
        }
    }
?>