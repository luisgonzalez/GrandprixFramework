<?php
    /**
     * Provides a standard Form object renderer.
     *
     * @package Grandprix
     * @subpackage View
     */
    class HtmlFormView extends HtmlView
    {
        private $isUploaderFlashLocationRendered;
        /**
         * Creates a new instance of this class
         *
         * @param IModel $model
         */
        public function __construct(&$model)
        {
            parent::__construct($model);
            $this->isUploaderFlashLocationRendered = false;
            $this->cssClass = 'formview';
     
            // Setup the callbacks for each renderable model
            $this->renderCallbacks['Form'] = array('HtmlRenderCallbacks', 'renderForm');
            $this->renderCallbacks['WizardForm'] = array('HtmlRenderCallbacks', 'renderWizardForm');
            $this->renderCallbacks['AccordionForm'] = array('HtmlRenderCallbacks', 'renderForm');
            $this->renderCallbacks['TabView'] = array('HtmlRenderCallbacks', 'renderTabView');
            $this->renderCallbacks['FormSection'] = array('HtmlRenderCallbacks', 'renderFormSection');
            $this->renderCallbacks['AccordionItem'] = array('HtmlRenderCallbacks', 'renderAccordionItem');
            $this->renderCallbacks['FormSectionActionBar'] = array('HtmlRenderCallbacks', 'renderFormSectionActionBar');
            $this->renderCallbacks['WizardPage'] = array('HtmlRenderCallbacks', 'renderWizardPage');
            $this->renderCallbacks['Tab'] = array('HtmlRenderCallbacks', 'renderTab');
            $this->renderCallbacks['TextField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['EmailField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['PhoneField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['ZipCodeField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['Ccv2Field'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['NumericField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['PasswordField'] = array('HtmlRenderCallbacks', 'renderPasswordField');
            $this->renderCallbacks['AutoCompleteField'] = array('HtmlRenderCallbacks', 'renderAutoCompleteField');
            $this->renderCallbacks['LabelField'] = array('HtmlRenderCallbacks', 'renderLabelField');
            $this->renderCallbacks['DeletableLabelField'] = array('HtmlRenderCallbacks', 'renderDeletableLabelField');
            $this->renderCallbacks['RawField'] = array('HtmlRenderCallbacks', 'renderRawField');
            $this->renderCallbacks['Button'] = array('HtmlRenderCallbacks', 'renderButton');
            $this->renderCallbacks['PersistorField'] = array('HtmlRenderCallbacks', 'renderPersistorField');
            $this->renderCallbacks['TextArea'] = array('HtmlRenderCallbacks', 'renderTextArea');
            $this->renderCallbacks['RichTextEditor'] = array('HtmlRenderCallbacks', 'renderRichTextEditor');
            $this->renderCallbacks['CheckBox'] = array('HtmlRenderCallbacks', 'renderCheckBox');
            $this->renderCallbacks['ToggleButton'] = array('HtmlRenderCallbacks', 'renderToggleButton');
            $this->renderCallbacks['ComboBox'] = array('HtmlRenderCallbacks', 'renderComboBox');
            $this->renderCallbacks['ButtonComboBox'] = array('HtmlRenderCallbacks', 'renderComboBox');
            $this->renderCallbacks['DateField'] = array('HtmlRenderCallbacks', 'renderDateField');
            $this->renderCallbacks['CreditCardField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['MonthYearComboBox'] = array('HtmlRenderCallbacks', 'renderMonthYearComboBox');
            $this->renderCallbacks['CreditCardCompoundField'] = array('HtmlRenderCallbacks', 'renderCreditCardCompoundField');
            $this->renderCallbacks['MessageControl'] = array('HtmlRenderCallbacks', 'renderMessageControl');
            $this->renderCallbacks['ListBox'] = array('HtmlRenderCallbacks', 'renderListBox');
            $this->renderCallbacks['AdSpotControl'] = array('HtmlRenderCallbacks', 'renderAdSpotControl');
            $this->renderCallbacks['SearchField'] = array('HtmlRenderCallbacks', 'renderSearchField');
            $this->renderCallbacks['ImageField'] = array('HtmlRenderCallbacks', 'renderTextField');
            $this->renderCallbacks['FileExistField'] = array('HtmlRenderCallbacks', 'renderFileExistField');
            $this->renderCallbacks['FileField'] = array('HtmlRenderCallbacks', 'renderFileField');
            $this->renderCallbacks['Gallery'] = array('HtmlRenderCallbacks', 'renderGallery');
            
            self::registerDependencies();
            
            $autoCompleteFields = $model->getChildren()->getControlNames(true, 'AutoCompleteField');
            if (empty($autoCompleteFields) === false)
            {
                $yuiAutoCompletePath = 'lib/yui/autocomplete-min.js';
                $yuiAutoCompleteCssPath = 'lib/yui/sam/autocomplete.css';
                HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIAutoComplete', $yuiAutoCompletePath);
                HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'YUIAutoCompleteCss', $yuiAutoCompleteCssPath);
            }
        }
        
        public function setCallback($name, $callback)
        {
            $this->renderCallbacks[$name] = $callback;
        }
        
        public static function registerDependencies()
        {
            self::registerCommonDependencies();
            
            $commonPath = 'lib/javascript/common.js';
            $yuiMenuPath = 'lib/yui/menu-min.js';
            $tabViewCSSPath = 'lib/yui/sam/tabview.css';
            $tabViewPath = 'lib/yui/tabview-min.js';
            $commonCssPath = 'lib/css/common.css';
            $editorPath = 'lib/yui/editor-min.js';
            $editorCssPath = 'lib/yui/sam/editor.css';
            
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'YUIMenu', $yuiMenuPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'TabView', $tabViewPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'Common', $commonPath);
            HtmlViewManager::registerDependency(HtmlDependency::JS_FILE, 'Editor', $editorPath);
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'TabViewCSS', $tabViewCSSPath);
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'EditorCSS', $editorCssPath);
            HtmlViewManager::registerDependency(HtmlDependency::CSS_FILE, 'CommonCSS', $commonCssPath);
        }
     
        public function render()
        {
            if ($this->isUploaderFlashLocationRendered === false)
            {
                $tw = HtmlWriter::getInstance();
                $tw->openScript();
                $tw->addAttribute('language','javascript');
                //$tw->writeRaw('YAHOO.widget.Uploader.SWFURL="http://developer.yahoo.com/yui/examples/uploader/assets/uploader.swf";');
                
                $tw->writeRaw('YAHOO.widget.Uploader.SWFURL="' . str_repeat('../', HtmlViewManager::getDepth()) . 'lib/yui/uploader.swf";');
                $tw->closeScript();
                
                $this->isUploaderFlashLocationRendered = true;
            }
            
            parent::render();
        }
        /**
         * Helper method to render child controls within a container model.
         *
         * @param ContainerModelBase $model
         * @param HtmlFormView $view
         */
        public static function renderChildren(&$model, &$view)
        {
            $callbacks = $view->getRenderCallbacks();
            $children = $model->getChildren()->getControls();
            
            foreach($children as $currentControl)
            {
                if ($currentControl->getVisible() === false) continue;
                
                $controlClassName = get_class($currentControl);
                if ($controlClassName == 'Button') continue;
                
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
        }
    }
   
    /**
     * Contains static callback methods to render standard framework HTML controls
     *
     * @package Grandprix
     * @subpackage View
     */
    class HtmlRenderCallbacks
    {
        /**
         * Renders buttons container
         * 
         * @param Form $model
         * @param HtmlFormView $view
         */
        static public function renderButtonsContainer(&$model, &$view)
        {
            // The form's button section
            $buttonControls = $model->getChildren()->getControlNames(false, 'ButtonModelBase');
            
            $tw = HtmlWriter::getInstance();
            
            if (count($buttonControls) == 0) return;
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-buttonpanel-container');
        
            $tw->openDiv();
            $tw->addAttribute('id', 'form_' . $model->getName() . '_buttonPanel');
            $tw->addAttribute('class', $view->getCssClass() . '-buttonpanel');
    
            $callbacks = $view->getRenderCallbacks();
            
            foreach ($buttonControls as $buttonName)
            {
                $currentControl = $model->getButton($buttonName);
                
                if ($currentControl->getVisible() === false) continue;
                
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
            
            $tw->closeDiv(); // Close the button panel
            
            $tw->closeDiv(true); // Close the bottom container
        }
        
        /**
         * Renders a ListBox
         * 
         * @param ListBox $model
         * @param HtmlFormView $view
         */
        static public function renderListBox(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
            
            if ($model->getEventName() != '')
            {
                $tw->openInput();
                $tw->addAttribute('class', $view->getCssClass() . '-table-selected');
                $tw->addAttribute('id', 'listbox_' . $model->getName() . '_selected');
                $tw->addAttribute('name', 'listbox_' . $model->getName() . '_selected');
                $tw->addAttribute('type', 'hidden');
                $tw->addAttribute('value', "");
                $tw->closeInput();
            }
            
            $tw->openDiv();
            $tw->addAttribute('id', 'listbox_' . $model->getName());
            $tw->addAttribute('class', $view->getCssClass().'-listbox');
            if ($model->getEventName() != '') $tw->addAttribute('eventName', $model->getEventName());
            $tw->addAttribute('highlighteditem', $model->getHighlightedItem());
            $tw->addAttribute('paging', $model->getIsPaging());
            $tw->addAttribute('totalRecords', $model->getTotalRecords());
            $tw->addAttribute('page', $model->getCurrentPage());
            
            $tw->openTable();
            $tw->addAttribute('id', 'listbox_' . $model->getName() . '_table');
            $tw->addAttribute('direction', $model->getSortDirection());
            $tw->addAttribute('tooltip', $model->getTooltip() ? 'true' : 'false');
            
            $tw->openThead();
            $tw->openTr();
            
            foreach($model->getHeaders() as $headerName)
            {
                $tw->openTh();
                $tw->writeContent($headerName);
                $tw->closeTh();
            }
            
            $tw->closeTr();
            $tw->closeThead();
            
            $tw->openTbody();
            
            foreach($model->getItems() as $item)
            {
                $tw->openTr();
                
                foreach($item as $key => $value)
                {
                    $tw->openTd();
                    $tw->writeContent($value, false, true, false);
                    $tw->closeTd();
                }
                
                $tw->closeTr();
            }
            
            $tw->closeTbody(true);
            
            $tw->closeTable();
            
            $tw->closeDiv();
            
            if ($model->getIsPaging())
            {
                $tw->openDiv();
                $tw->addAttribute('id', 'listbox_' . $model->getName() . '_paginator');
                $tw->closeDiv(true);
            }
        }
        
        /**
         * Renders a TabView
         * 
         * @param TabView $model
         * @param HtmlFormView $view
         */
        static public function renderTabView(&$model, &$view)
        {    
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
            
            // The tabview's label
            if ($model->getCaption() != '')
            {
                $tw->openDiv();
                $tw->addAttribute('id', 'formSection_' . $model->getName() . '_caption');
                $tw->addAttribute('class', $view->getCssClass() . '-formsection-caption');
                $tw->writeContent($model->getCaption());
                $tw->closeDiv();
            }
            
            $tw->openDiv();
            $tw->addAttribute('class', 'yui-navset');
            $tw->addAttribute('id', 'tabview_' . $model->getName());
            
            $tw->openUl();
            $tw->addAttribute('class', 'yui-nav');
           
            $children = $model->getChildren()->getControlNames(true, 'Tab');
            
            foreach($children as $controlName)
            {
                $currentControl = $model->getChildren()->getControl($controlName);
                
                $tw->openLi();
                
                if ($currentControl->getIsSelected())
                    $tw->addAttribute('class', 'selected');
                    
                $tw->openA();
                $tw->addAttribute('href', '#' . $currentControl->getName());
                $tw->openEm();
                $tw->writeContent($currentControl->getCaption());
                $tw->closeEm();
                $tw->closeA();
                
                $tw->closeLi();
            }
           
            $tw->closeUl();
            
            $tw->openDiv();
            $tw->addAttribute('class', 'yui-content');
            HtmlFormView::renderChildren($model, $view);
            $tw->closeDiv();
            
            $tw->closeDiv();
        }
        
        /**
         *
         * @param HtmlWriter $tw
         * @param Form $model
         * @param HtmlFormView $view
         */
        static public function renderForm(&$model, &$view)
        {    
            $tw = HtmlWriter::getInstance();
            
            // The form tag
            $tw->openForm();
            $tw->addAttribute('id', 'form_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('class', $view->getCssClass());
            $tw->addAttribute('isAsync', ($view->getIsAsync()) ? 'true' : 'false');
            $tw->addAttribute('action', $_SERVER['REQUEST_URI']);
            $tw->addAttribute('method', 'post');
            $tw->addAttribute('enctype', 'multipart/form-data');
         
            if ($model instanceof AccordionForm)
                $tw->addAttribute('type', 'accordion');
            else
                $tw->addAttribute('type', 'form');
                
            // Add the postback hidden field to signal postbacks
            $postBackIdentifier = 'postBackFlag_' . $model->getName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $postBackIdentifier);
            $tw->addAttribute('name', $postBackIdentifier);
            $tw->addAttribute('value', '1');
            $tw->closeInput();
            
            if ($model->getVisible() === false)
            {
                $tw->closeForm();
                return;
            }
            
            // The form's label
            if ($model->getCaption() != '')
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getMasterCssClass() . '-caption');
                $tw->writeContent($model->getCaption());
                $tw->closeDiv();
            }
            
            if ($model instanceof DetailsForm)
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getMasterCssClass() . '-subcaption');
                
                $tw->openInput();
                $tw->addAttribute('class', $view->getCssClass() . '-navbutton');
                $tw->addAttribute('value', 'Previous');
                $tw->addAttribute('name', 'Previous');
                $tw->addAttribute('type', 'button');
                $tw->closeInput();
                
                $tw->openInput();
                $tw->addAttribute('class', $view->getCssClass() . '-navbutton');
                $tw->addAttribute('value', 'Next');
                $tw->addAttribute('name', 'Next');
                $tw->addAttribute('type', 'button');
                $tw->closeInput();
                
                $tw->closeDiv();
            }
            
            // The form's content section
            $tw->openDiv();
            $tw->addAttribute('id', 'form_' . $model->getName() . '_content');
            $tw->addAttribute('class', $view->getMasterCssClass() . '-content');
            
            // The form's child controls
            HtmlFormView::renderChildren($model, $view);

            // The form's error message (if available)
            if ($model->hasErrorMessage())
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass().'-errordialog');
                $tw->writeRaw($model->getErrorMessage());
                $tw->closeDiv();
            }
            elseif($model->hasNotificationMessage())
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass().'-notificationdialog');
                $tw->writeRaw($model->getNotificationMessage());
                $tw->closeDiv();
            }
  
            self::renderButtonsContainer($model, $view);
            
            $tw->closeDiv(true);
            $tw->closeForm();
        }
        
        /**
         * Renders a wizard form
         * 
         * @param Form $model
         * @param HtmlFormView $view
         */
        static public function renderWizardForm(&$model, &$view)
        {    
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
            
            // The form tag
            $tw->openForm();
            $tw->addAttribute('id', 'form_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('class', $view->getCssClass());
            $tw->addAttribute('action', $_SERVER['PHP_SELF']);
            $tw->addAttribute('method', 'post');
            $tw->addAttribute('enctype', 'multipart/form-data');
            $tw->addAttribute('type', 'wizard');
            
            // Add the postback hidden field to signal postbacks
            $postBackIdentifier = 'postBackFlag_' . $model->getName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $postBackIdentifier);
            $tw->addAttribute('name', $postBackIdentifier);
            $tw->addAttribute('value', '1');
            $tw->closeInput();
           
            $tw->openDiv();
            $tw->addAttribute('id', 'form_' . $model->getName() . '_caption');
            $tw->addAttribute('class', $view->getMasterCssClass() . '-caption');
            $tw->writeContent($model->getCaption());
            $tw->closeDiv();

            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', 'form_' . $model->getName() . '_page');
            $tw->addAttribute('name', 'form_' . $model->getName() . '_page');
            $tw->addAttribute('value', $model->getCurrentPage());
            $tw->closeInput();

            $tw->openInput();
            $tw->addAttribute('id', 'wizard_' . $model->getName() . '_page');
            $tw->addAttribute('name', 'wizard_' . $model->getName() . '_page');
            $tw->addAttribute('value', $model->getCurrentPage());
            $tw->addAttribute('type', 'hidden');
            $tw->closeInput();
            
            // The form's content section
            $tw->openDiv();
            $tw->addAttribute('id', 'form_' . $model->getName() . '_content');
            $tw->addAttribute('class', $view->getMasterCssClass() . '-content');
            
            // The form's child controls
            HtmlFormView::renderChildren($model, $view);
            
            // The form's error message (if available)
            if ($model->hasErrorMessage())
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass().'-errordialog');
                $tw->writeRaw($model->getErrorMessage());
                $tw->closeDiv();
            }
            elseif ($model->hasNotificationMessage())
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass().'-notificationdialog');
                $tw->writeRaw($model->getNotificationMessage());
                $tw->closeDiv();
            }
            
            self::renderButtonsContainer($model, $view);
            
            $tw->closeDiv();
            $tw->closeForm();
        }
        
        /**
         * Renders a wizard page
         * 
         * @param WizardPage $model
         * @param HtmlFormView $view
         */        
        static public function renderWizardPage(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
        
            $tw->openDiv();
            $tw->addAttribute('id', 'formWizardPage_' . $model->getName());
            $tw->addAttribute('class', $view->getCssClass() . '-wizardpage');
            
            // The container's header
            if ($model->getCaption() != '')
            {
                $tw->openDiv();
                $tw->addAttribute('id', 'formWizardPage_' . $model->getName() . '_caption');
                $tw->addAttribute('class', $view->getCssClass() . '-wizardpage-caption');
                $tw->writeContent($model->getCaption());
                $tw->closeDiv();
            }
            
            $tw->openDiv();
            $tw->addAttribute('id', 'formWizardPage_' . $model->getName() . '_content');
            $tw->addAttribute('class', $view->getCssClass() . '-wizardpage-content');
            
            HtmlFormView::renderChildren($model, $view);
            
            $tw->closeDiv(true); // close content
            $tw->closeDiv(); // close main container
        }
        
        /**
         * Renders a ComboBox
         * 
         * @param ComboBox $model
         * @param HtmlFormView $view
         */        
        static public function renderComboBox(&$model, &$view)
        {
            self::openFieldLayout($model, $view);
            
            $isMultiple = $model->getIsMultiple();
            $useButton = is_a($model, 'ButtonComboBox');
            
            $tw = HtmlWriter::getInstance();
            
            if ($model->getIsReadOnly())
            {
                $tw->openSpan();
                $tw->addAttribute('class', 'readonly');
                
                if ($model->getValue() == '')
                    $tw->writeContent("-");
                else
                    $tw->writeContent($model->getValue());
                
                $tw->closeSpan();
            }
            else
            {
                $comboValue = '-- Seleccione --';
                
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass().'-combobox');
                $tw->writeRaw($model->getName());
                $tw->closeDiv();

                $hiddenValue = $isMultiple
                                ? implode($model->getMultipleSeparator(), $model->getValue())
                                : $model->getValue();
                                
                $tw->openInput();
                $tw->addAttribute('type', 'hidden');
                $tw->addAttribute('name', $model->getName());
                $tw->addAttribute('value', $hiddenValue);
                $tw->addAttribute('id', $model->getName());
                if ($model->getEventName() != '') $tw->addAttribute('eventname', $model->getEventName());
                $tw->closeInput();
                
                $tw->openSelect();
                $tw->addAttribute('id', 'select_' . $model->getName());
                $tw->addAttribute('class', $view->getCssClass() . '-select');
                
                if ($isMultiple)
                {
                    $tw->addAttribute('multiple', 'multiple');
                    $tw->addAttribute('size', '3');
                    $tw->addAttribute('separator', $model->getMultipleSeparator());
                }
                
                $currentOptgroup = '';
                $isOptgroupOpen = false;
                $selectedValuesArray = (array)$model->getValue();

                $options = $model->getOptions();
                if ($useButton === false && $model->getIsMultiple() === false)
                {
                    $options = array_merge(array(array('value' => '', 'display' => '-- Seleccione --')), $options);
                }
                
                foreach ($options as $option)
                {
                    if ($currentOptgroup != $option['category'])
                    {
                        if ($isOptgroupOpen === true) $tw->closeOptgroup();
                        
                        if ($option['category'] != '')
                        {
                            $tw->openOptgroup();
                            $tw->addAttribute('label', $option['category']);
                            $isOptgroupOpen = true;
                        }
                        else
                        {
                            $isOptgroupOpen = false;
                        }
                        
                        $currentOptgroup = $option['category'];
                    }
                    
                    $tw->openOption();
                    $tw->addAttribute('value', $option['value']);
                    
                    if (in_array($option['value'], $selectedValuesArray))
                    {
                        $tw->addAttribute('selected', 'selected');
                        $comboValue = $option['display'];
                    }
                       
                    $tw->writeContent($option['display']);
                    $tw->closeOption();
                }
                
                if ($isOptgroupOpen === true) $tw->closeOptgroup();
                
                $tw->closeSelect();
                
                if ($isMultiple === false && $useButton)
                {
                    $tw->openInput();
                    $tw->addAttribute('id', 'button_' . $model->getName());
                    $tw->addAttribute('type', 'button');
                    $tw->addAttribute('value', $comboValue);
                    $tw->closeInput();
                }
            }
            
            self::renderToolTip($model, $view);
            self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a DateField
         * 
         * @param DateField $model
         * @param HtmlFormView $view
         */
        static public function renderDateField(&$model, &$view)
        {
            self::openFieldLayout($model, $view);
            
            $tw = HtmlWriter::getInstance();
            
            // Render the html-equivalent input
            $tw->openDiv();
            $tw->addAttribute('id', $model->getName());
            $tw->addAttribute('class', $view->getCssClass() . '-datefield');
            $tw->addAttribute('mindate', $model->getMinDate('m/j/Y'));
            $tw->addAttribute('maxdate', $model->getMaxDate('m/j/Y'));
            
            $tw->openInput();
            $tw->addAttribute('id', 'datefield_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('value', $model->getValue('Y-m-d'));
            $tw->addAttribute('type', 'hidden');
            $tw->closeInput();

            $value = $model->getValue('j/m/Y');
            if (is_null($value)) $value = '-';
            
            $tw->openDiv();
            $tw->addAttribute('id', 'datefield_' . $model->getName() . '_display_value');
            $tw->addAttribute('class', $view->getCssClass() . '-datefield-display');
            $tw->writeContent($value);
            $tw->closeDiv();
            
            if ($model->getIsRequired() == false)
            {
                $tw->openDiv();
                $tw->addAttribute('id', 'datefield_' . $model->getName() . '_delete');
                $tw->addAttribute('class', $view->getCssClass() . '-datefield-delete');
                $tw->writeContent(' ');
                $tw->closeDiv();
            }
            
            $tw->openDiv();
            $tw->addAttribute('id', 'datefield_' . $model->getName() . '_calendar');
            $tw->addAttribute('class', $view->getCssClass() . '-datefield-container');
            $tw->closeDiv(true);
            
            $tw->closeDiv();            

            self::renderToolTip($model, $view);
            
            self::closeFieldLayout($model, $view);
        }

        /**
         * Renders a accordion item
         * 
         * @param AccordionItem $model
         * @param HtmlFormView $view
         */        
        static public function renderAccordionItem(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
        
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('id', 'accordionItem_' . $model->getName());
            $tw->addAttribute('class', $view->getCssClass() . '-accordionitem');
            
            if ($model->getCurrent())
                $tw->addAttribute('current', 'true');
            else
                $tw->addAttribute('current', 'false');
                
            // The container's header
            if ($model->getCaption() != '')
            {
                $tw->openDiv();
                $tw->addAttribute('id', 'accordionItem_' . $model->getName() . '_caption');
                $tw->addAttribute('class', $view->getCssClass() . '-accordionitem-caption');
                $tw->writeContent($model->getCaption());
                $tw->closeDiv();
            }
            
            $tw->openDiv();
            $tw->addAttribute('id', 'accordionItem_' . $model->getName() . '_content');
            $tw->addAttribute('class', $view->getCssClass() . '-accordionitem-content');
            
            HtmlFormView::renderChildren($model, $view);
            
            $tw->closeDiv(true); // close content
            $tw->closeDiv(); // close main container
        }
        
        /**
         * Renders a form's section
         * 
         * @param FormSection $model
         * @param HtmlFormView $view
         */        
        static public function renderFormSectionColumn(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
        
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-formsection-column');
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-formsection-content');
            
            HtmlFormView::renderChildren($model, $view);
            
            $tw->closeDiv(true); // close content
            $tw->closeDiv(); // close main container
        }
        
        /**
         * Renders a form's section
         * 
         * @param FormSection $model
         * @param HtmlFormView $view
         */        
        static public function renderFormSection(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
        
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-formsection');
            
            // The container's header
            if ($model->getCaption() != '')
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-formsection-caption');
                $tw->writeContent($model->getCaption());
                $tw->closeDiv();
            }
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-formsection-content');
            
            HtmlFormView::renderChildren($model, $view);
            
            $tw->closeDiv(true); // close content
            $tw->closeDiv(); // close main container
        }
        
        /**
         * Renders a Tab
         * 
         * @param Tab $model
         * @param HtmlFormView $view
         */        
        static public function renderTab(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
        
            $tw->openDiv();
            $tw->addAttribute('id', $model->getName());            
            HtmlFormView::renderChildren($model, $view);
            $tw->closeDiv(true);
        }
        
        /**
         * Renders a PasswordField
         *
         * @param PasswordField $model
         * @param HtmlFormView $view
         */
        static public function renderPasswordField(&$model, &$view)
        {
            self::openFieldLayout($model, $view);
        
            $tw = HtmlWriter::getInstance();
            
            if ($model->getIsReadOnly())
            {
                $tw->openSpan();
                $tw->addAttribute('class', 'readonly');
                $tw->writeContent("******");
                $tw->closeSpan();
            }
            else
            {
                $tw->openInput();
                $tw->addAttribute('id', 'passwordField_' . $model->getName());
                $tw->addAttribute('name', $model->getBindingMemberName());
                $tw->addAttribute('value', $model->getValue());
                $tw->addAttribute('class', $view->getCssClass() . '-textfield');
                $tw->addAttribute('type', 'password');
                $tw->closeInput();
                
                if ($model->getMustVerify())
                {
                    self::closeFieldLayout($model, $view);
                    
                    $caption = $model->getCaption();
                    $model->setCaption("Confirmar");
                    
                    self::openFieldLayout($model, $view);
                    $model->setCaption($caption);
                    
                    $tw->openInput();
                    $tw->addAttribute('id', 'passwordField_' . $model->getName() . '_verification');
                    $tw->addAttribute('name', $model->getBindingMemberName() . '_verification');
                    $tw->addAttribute('value', $model->getValue());
                    $tw->addAttribute('class', $view->getCssClass() . '-textfield');
                    $tw->addAttribute('type', 'password');
                    $tw->closeInput();
                }
            }
           
            self::renderToolTip($model, $view);
            self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a FileExistField
         *
         * @param FileExistField $model
         * @param HtmlFormView $view
         */
        static public function renderFileExistField(&$model, &$view)
        {
            self::openFieldLayout($model, $view);
            
            $tw = HtmlWriter::getInstance();
            
            $tw->openInput();
            $tw->addAttribute('id', 'textField_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('value', $model->getFileValue());
            
            if ($model->getPreviewValue() == '')
                $tw->addAttribute('class', $view->getCssClass() . '-textfield');
            else
                $tw->addAttribute('class', $view->getCssClass() . '-fileexitsfield-' . $model->getPreviewValue());
                
            $tw->addAttribute('type', 'text');
            if ($model->getIsReadOnly()) $tw->addAttribute('disabled', 'disabled');
            $tw->closeInput();
            
            self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a TextField
         *
         * @param TextField $model
         * @param HtmlFormView $view
         */
        static public function renderTextField(&$model, &$view)
        {
            if ($model->getIsVisible() === false) return;
            
            self::openFieldLayout($model, $view);
            
            $tw = HtmlWriter::getInstance();
            
            if ($model->getIsReadOnly() === true)
            {
                $tw->openSpan();
                $tw->addAttribute('class', 'readonly');
                
                if ($model->getValue() == '')
                    $tw->writeContent("-");
                else
                {
                    if ($model instanceof CreditCardField)
                        $tw->writeContent($model->getMaskValue());
                    else
                        $tw->writeContent($model->getValue());
                }
                
                $tw->closeSpan();
            }
            else
            {         
                // Render the html-equivalent input
                $tw->openInput();
                $tw->addAttribute('id', 'textField_' . $model->getName());
                $tw->addAttribute('name', $model->getBindingMemberName());
                
                if ($model instanceof ImageField)
                    $tw->addAttribute('value', $model->getImageValue());
                else
                    $tw->addAttribute('value', $model->getValue());
                
                $tw->addAttribute('maxlength', $model->getMaxChars());
                $tw->addAttribute('class', $view->getCssClass() . '-textfield');
                $tw->addAttribute('type', 'text');
                if ($model->getIsDisabled()) $tw->addAttribute('readonly', 'readonly');
                $tw->closeInput();
                
                self::renderToolTip($model, $view);
            }
            
            if ($model instanceof ImageField)
            {
                $tw->closeDiv();
                self::renderFieldError($model, $view);
                $tw->closeTd();
                $tw->closeTr();
                
                $tw->openTr();
                $tw->openTd();
                $tw->addAttribute('class', $view->getCssClass() . '-imagefield-container');
                $tw->addAttribute('colspan', '2');
                
                $tw->openImg();
                $tw->addAttribute('class', $view->getCssClass() . '-imagefield');
                $tw->addAttribute('src', $model->getValue());
                $tw->closeImg();
                
                $tw->closeTd();
                $tw->closeTr();
                $tw->closeTable();
            }
            else
                self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a FileField
         *
         * @param FileField $model
         * @param HtmlFormView $view
         */
        static public function renderFileField(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();

            // Render the html-equivalent input
            if ($model->getIsMultiple() === false)
            {
                self::openFieldLayout($model, $view);
                
                $tw->openInput();
                $tw->addAttribute('id', 'textField_' . $model->getName());
                $tw->addAttribute('name', $model->getBindingMemberName());
                $tw->addAttribute('class', $view->getCssClass() . '-textfield');
                $tw->addAttribute('type', 'file');
                $tw->closeInput();
                
                self::renderToolTip($model, $view);
                self::closeFieldLayout($model, $view);
            }
            else
            {
                $tw->openDiv();
                $tw->addAttribute('id', 'fileField_' . $model->getName());
                $tw->addAttribute('class', $view->getCssClass() . '-filefield');
                $tw->addAttribute('name', $model->getName());
                
                    $eventIdentifier = 'eventValue_' . $model->getName() . '_' . $model->getEventName();
                    $tw->openInput();
                    $tw->addAttribute('type', 'hidden');
                    $tw->addAttribute('id', $eventIdentifier);
                    $tw->addAttribute('name', $eventIdentifier);
                    $tw->addAttribute('value', $model->getName());
                    $tw->closeInput();
                    
                    $tw->openInput();
                    $tw->addAttribute('type', 'hidden');
                    $tw->addAttribute('id', 'sessionId');
                    $tw->addAttribute('name', 'sessionId');
                    $tw->addAttribute('value', session_id());
                    $tw->closeInput();

                    $tw->openTable();
                    $tw->addAttribute('class', $view->getCssClass() . '-filefield-table');
                    $tw->openTr();
                    $tw->openTd();
                    $tw->addAttribute('colspan', '2');
                    
                    $tw->openDiv();
                    $tw->addAttribute('id', 'fileField_' . $model->getName() . '_grid');
                    $tw->addAttribute('class', $view->getCssClass() . '-filefield-grid');
                    $tw->closeDiv(true);
                    
                    $tw->closeTd();
                    $tw->closeTr();

                    $tw->openTr();
                    $tw->openTd();
                    $tw->addAttribute('style', 'width:50%; text-align: right;');
                        $tw->openDiv();
                        $tw->addAttribute('style', 'float: right; width: 120px');
                        
                        $tw->openDiv();
                        $tw->addAttribute('id', 'fileField_' . $model->getName() . '_overlay');
                        $tw->addAttribute('class', $view->getCssClass() . '-filefield-overlay');
                        $tw->closeDiv(true);


                        $tw->openSpan();
                        $tw->addAttribute('id', 'fileField_' . $model->getName() . '_selector');
                        $tw->addAttribute('class', $view->getCssClass() . '-filefield-selector');
                        $tw->writeContent('Seleccionar Archivos');
                        $tw->closeSpan();
                        $tw->closeDiv();

                    $tw->closeTd();
                    $tw->openTd();
                    $tw->addAttribute('style', 'width:50%;');
                    
                    $tw->openSpan();
                    $tw->addAttribute('id', 'fileField_' . $model->getName() . '_uploader');
                    $tw->addAttribute('class', $view->getCssClass() . '-filefield-uploader');
                    $tw->writeContent('Subir Archivos');
                    $tw->closeSpan();
                    
                    $tw->closeTd();
                    $tw->closeTr();
                    
                    $tw->closeTable();

                $tw->closeDiv();
            }
        }
        
        public static function renderRichTextEditor(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-rte-container');
            
            $tw->openTextArea();
            $tw->addAttribute('id', 'textArea_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('class', $view->getCssClass() . '-rte-control');
            $tw->addAttribute('caption', $model->getCaption());

            if ($model->getIsReadOnly() === true)
                $tw->addAttribute('disabled', 'disabled');
              
            if (trim($model->getValue()) != '')
                $tw->writeContent($model->getValue(), false, false, false);
            
            $tw->closeTextarea(true);
            
            $tw->closeDiv();
        }
        /**
         * Renders a TextArea
         *
         * @param TextArea $model
         * @param HtmlFormView $view
         */
        static public function renderTextArea(&$model, &$view)
        {
            self::openFieldLayout($model, $view);
            
            $tw = HtmlWriter::getInstance();
            
            // Render the html-equivalent input
            $tw->openTextarea();
            $tw->addAttribute('id', 'textArea_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('class', $view->getCssClass() . '-textarea');
            
            $tw->addAttribute('type', 'text');
            
            if ($model->getIsReadOnly() === true)
                $tw->addAttribute('disabled', 'disabled');
              
            if (trim($model->getValue()) != '')
                $tw->writeContent($model->getValue(), false, false, false);
            
            $tw->closeTextarea(true);
            
            self::renderToolTip($model, $view);
            self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a ToggleButton
         *
         * @param ToggleButton $model
         * @param HtmlFormView $view
         */
        static public function renderToggleButton(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('style', 'text-align: center; width: auto; padding: 4px;');
            
            $tw->openInput();
            $tw->addAttribute('id', 'toggleButton_' . $model->getName() . '_ctrl');
            $tw->addAttribute('value', $model->getValue());
            $tw->addAttribute('class', $view->getCssClass() . '-togglebutton');
            $tw->addAttribute('type', 'checkbox');
            $tw->addAttribute('label', $model->getCaption());
            if ($model->getIsChecked()) $tw->addAttribute('checked', 'checked');
            if ($model->getIsReadOnly() === true) $tw->addAttribute('disabled', 'disabled');
            $tw->closeInput();
            
            $tw->openInput();
            $tw->addAttribute('id', 'toggleButton_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('value', $model->getValue());
            $tw->addAttribute('type', 'hidden');
            $tw->closeInput();
            
            $tw->closeDiv();
        }
        
        /**
         * Renders a CheckBox
         *
         * @param CheckBox $model
         * @param HtmlFormView $view
         */
        static public function renderCheckBox(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openTable();
            $tw->addAttribute('class', $view->getCssClass() . '-table');
            $tw->addAttribute("id", "field_" . $model->getName());
            
            $tw->openTr();
            $tw->openTd();
            $tw->addAttribute('class', $view->getCssClass() . '-label');
            
            $tw->openInput();
            $tw->addAttribute('id', 'checkBox_' . $model->getName() . '_ctrl');
            $tw->addAttribute('name', $model->getBindingMemberName() . '_ctrl');
            $tw->addAttribute('value', $model->getValue());
            $tw->addAttribute('class', $view->getCssClass() . '-checkbox');
            $tw->addAttribute('type', 'checkbox');
            $tw->addAttribute('onchange', "if (this.checked) { YUtil.Dom.get('" . 'checkBox_' . $model->getName() . "').value = '" . $model->getCheckedValue() . "'; } else { YUtil.Dom.get('" . 'checkBox_' . $model->getName() . "').value = '" . $model->getUncheckedValue() . "'; } ");
            if ($model->getIsChecked()) $tw->addAttribute('checked', 'checked');
            if ($model->getIsReadOnly() === true) $tw->addAttribute('disabled', 'disabled');
            $tw->closeInput();
            
            // supporting hidden field
            $tw->openInput();
            $tw->addAttribute('id', 'checkBox_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('value', $model->getValue());
            $tw->addAttribute('type', 'hidden');
            $tw->closeInput();
            
            $tw->closeTd();
            $tw->openTd();
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass().'-field-container');
            
            $tw->openLabel();
            $tw->addAttribute('for', $model->getName());
            $tw->writeRaw($model->getCaption());
            $tw->closeLabel();
           
            self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a PersistorField
         *
         * @param PersistorField $model
         * @param HtmlFormView $view
         */
        static public function renderPersistorField(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openInput();
        
            $tw->addAttribute('id', 'persistorField_' . $model->getName());
            $tw->addAttribute('name', $model->getBindingMemberName());
            $tw->addAttribute('value', $model->getValue());
            $tw->addAttribute('type', 'hidden');
        
            $tw->closeInput();
        }
        
        /**
         * Renders a Button model
         *
         * @param Button $model
         * @param HtmlFormView $view
         */
        static public function renderButton(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            // Render the button itself.
            $tw->openInput();
            $tw->addAttribute('id', 'button_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('class', $view->getCssClass() . '-button');
            $tw->addAttribute('type', 'button');
            $tw->addAttribute('value', $model->getCaption());
            $tw->addAttribute('eventname', $model->getEventName());
            
            if ($model->getIsEnabled() === false)
                $tw->addAttribute('disabled', 'disabled');
      
            $tw->closeInput();
        
            // Render the necessary fields to perform event firing.
            $eventIdentifier = 'eventValue_' . $model->getName() . '_' . $model->getEventName();
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', $eventIdentifier);
            $tw->addAttribute('name', $eventIdentifier);
            $tw->addAttribute('value', $model->getName());
            $tw->closeInput();
        }
        
        /**
         * Helper function  that renders a dynamic tooltip for a field.
         *
         * @param FieldModelBase $model
         * @param HtmlFormView $view
         */
        static private function renderToolTip(&$model, &$view)
        {
            // Render the tooltip
            if ($model->getHelpMessage() != '')
            {
                $tw = HtmlWriter::getInstance();
                $tw->openDiv();
                $tw->addAttribute('id', $model->getName() . '_tooltip');
                $tw->addAttribute('class', $view->getCssClass() . '-tooltip');
                $tw->addAttribute('style', 'display: none;');
                $tw->writeContent($model->getHelpMessage(), false, true, false);
                $tw->closeDiv();
            }
        }
        
        /**
         * Helper function that renders a dynamic error marker + a tooltip for a field.
         *
         * @param ModelBase $model
         * @param HtmlFormView $view
         */        
        static private function renderFieldError(&$model, &$view)
        {
            if (!$model->hasErrorMessage())
                return;
            
            $tw = HtmlWriter::getInstance();
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-field-error');
            $tw->writeContent($model->getErrorMessage(), false, true, false);
            $tw->closeDiv();
        }
        
        /**
         * Helper function that provides a simple, table-based layout for regular fields.
         * Use the closeFieldLayout method after writing the control.
         *
         * @param FieldModelBase $model
         * @param HtmlFormView $view
         */        
        static private function openFieldLayout(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openTable();
            $tw->addAttribute('class', $view->getCssClass() . '-table');
            $tw->addAttribute("id", "field_" . $model->getName());
            $tw->openTr();
        
            // Render the label
            $tw->openTd();
            $tw->addAttribute('class', $view->getCssClass() . '-label');
            
            if ($model->getIsRequired() && !$model->getIsReadOnly())
            {
                $tw->openSpan();
                $tw->addAttribute('title', 'Field is required');
                $tw->writeContent('*', false, false, false);
                $tw->closeSpan();
            }
            
            $tw->openLabel();
            $tw->addAttribute('for', $model->getName());
            $tw->writeContent($model->getCaption());
            $tw->closeLabel();
            $tw->closeTd();
            $tw->openTd();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass().'-field-container');
        }
        
        /**
         * Helper function that must be preceeded by an opendFieldLayout call.
         *
         * @param FieldModelBase $model
         * @param HtmlFormView $view
         */        
        static private function closeFieldLayout(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->closeDiv();
            self::renderFieldError($model, $view);
            $tw->closeTd();
            $tw->closeTr();
            $tw->closeTable();
        }
        
        /**
         * Renders a LabelField
         *
         * @param LabelField $model
         * @param HtmlFormView $view
         */
        static public function renderLabelField(&$model, &$view)
        {
            if ($model->getEditable() === true)
            {
                self::renderTextField($model, $view);
            }
            else
            {
                $tw = HtmlWriter::getInstance();
                
                $model->setIsRequired(false);
                self::openFieldLayout($model, $view);
             
                $tw->openSpan();
                $tw->addAttribute('class', 'readonly');
                
                if ($model->getValue() == '')
                    $tw->writeContent("-");
                else
                    $tw->writeContent($model->getValue());
                
                $tw->closeSpan();
                
                self::renderToolTip($model, $view);
                self::closeFieldLayout($model, $view);
            }
        }
        
        /**
         * Renders a DeletableLabelField
         *
         * @param DeletableField $model
         * @param HtmlFormView $view
         */
        public static function renderDeletableLabelField($model, $view)
        {
            if ($model->getEditable() === true)
            {
                self::renderTextField($model, $view);
            }
            else
            {
                $tw = HtmlWriter::getInstance();
                
                $model->setIsRequired(false);
                self::openFieldLayout($model, $view);
             
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-deletablefield');
                
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-deletablefield-content');

                if ($model->getValue() == '')
                    $tw->writeContent("-");
                else
                    $tw->writeContent($model->getValue());
                
                $tw->closeDiv();
                
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-deletablefield-delete');
                $tw->addAttribute('deleteid', $model->getId());

                $tw->closeDiv(true);
                $tw->closeDiv();
                
                self::renderToolTip($model, $view);
                self::closeFieldLayout($model, $view);
            }
        }
        
        /**
         * Renders a RawField
         *
         * @param RawField $model
         * @param HtmlFormView $view
         */
        static public function renderRawField(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            if ($model->getCaption() == '')
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-textblock');
                $tw->writeRaw($model->getValue());
                $tw->closeDiv();
            }
            else
            {
                self::openFieldLayout($model, $view);
            
                $tw->openSpan();
                $tw->writeRaw($model->getValue());
                $tw->closeSpan();
                
                self::renderToolTip($model, $view);
                self::closeFieldLayout($model, $view);
            }
        }
        
        /**
         * Renders a FormSectionActionBar
         *
         * @param FormSectionActionBar $model
         * @param HtmlFormView $view
         */
        static public function renderFormSectionActionBar(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-formsection-actionbar');
            
            $callbacks = $view->getRenderCallbacks();
            $buttonControls = $model->getChildren()->getControlNames(true, 'ButtonModelBase');
            
            foreach ($buttonControls as $buttonName)
            {
                $currentControl = $model->getButton($buttonName);
                
                if ($currentControl->getVisible() === false) continue;
                
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
   
            $tw->closeDiv(true);
        }
        
        /**
         * Renders a Month/Year combo box duo
         * 
         * @param MonthYearComboBox $model
         * @param HtmlFormView $view
         */        
        static public function renderMonthYearComboBox(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
            
            self::openFieldLayout($model, $view);
            
            $children = $model->getChildren()->getControls();
            $key = 0;
            
            foreach($children as $currentControl)
            {
                if ($currentControl->getVisible() === false) continue;
                   
                if ($currentControl->getIsReadOnly())
                {
                    $tw->openSpan();
                    
                    if ($currentControl->getValue() == '')
                        $tw->writeContent("-");
                    else
                        $tw->writeContent($currentControl->getValue());
                    
                    if ($key == 0) $tw->writeContent(" / ");
                        
                    $tw->closeSpan();
                }
                else
                {
                    $tw->openDiv();
                    $tw->addAttribute('class', $view->getCssClass().'-combobox');
                    $tw->writeRaw($currentControl->getName());
                    $tw->closeDiv();

                    $tw->openInput();
                    $tw->addAttribute('type', 'hidden');
                    $tw->addAttribute('name', $currentControl->getName());
                    $tw->addAttribute('value', $currentControl->getValue());
                    $tw->addAttribute('id', $currentControl->getName());
                    $tw->closeInput();
                    
                    $comboValue = '-- Seleccione --';
                    $tw->openSelect();
                    $tw->addAttribute('id', 'select_' . $currentControl->getName());
                    $tw->addAttribute('name', $currentControl->getName());
                    $tw->addAttribute('class', $view->getCssClass() . '-select');
                    
                    foreach ($currentControl->getOptions() as $option)
                    {
                        $tw->openOption();
                        $tw->addAttribute('value', $option['value']);
                        
                        if ($option['value'] == $currentControl->getValue())
                        {
                            $tw->addAttribute('selected', 'selected');
                            $comboValue = $option['display'];
                        }
                        
                        $tw->writeContent($option['display'], false, false, false);
                        $tw->closeOption();
                    }
                 
                    $tw->closeSelect();
                 
                    $tw->openInput();
                    $tw->addAttribute('id', 'button_' . $currentControl->getName());
                    $tw->addAttribute('type', 'button');
                    $tw->addAttribute('value', $comboValue);
                    $tw->closeInput();
                    
                    if ($key == 0) $tw->writeContent(" / ");
                }
                
                $key++;
            }
            
            self::renderToolTip($model, $view);
            self::closeFieldLayout($model, $view);
        }
      
        /**
         * Renders a credit card compund field
         * 
         * @param CreditCardCompoundField $model
         * @param HtmlFormView $view
         */        
        static public function renderCreditCardCompoundField(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            if ($model->getErrorMessage() != '')
            {
                $errorMessage = $model->getErrorMessage();
                $controls = $model->getChildren()->getControlNames(false, 'IValidatable');
             
                foreach($controls as $control)
                {
                   $controlRef = $model->getChildren()->getControl($control);
                   $controlRef->setErrorMessage($errorMessage);
                }
            }
            
            HtmlFormView::renderChildren($model, $view);
        }
      
        /**
         * Renders a MessageControl
         *
         * @param MessageControl $model
         * @param HtmlFormView $view
         */
        static public function renderMessageControl(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            if ($model->getValue() == '') return;
            
            $tw->openP();
            $tw->addAttribute('class', $view->getCssClass().'-message');
            $tw->writeRaw($model->getValue());
            $tw->closeP();
        }
        
        /**
         * Renders a AdSpotControl
         *
         * @param AdSpotControl $model
         * @param HtmlFormView $view
         */
        static public function renderAdSpotControl(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass().'-adspot');
            $tw->writeRaw($model->getValue());
            $tw->closeDiv();
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
         * Helper method to render an autocomplete field.
         *
         * @param AutoCompleteField $model
         * @param HtmlFormView $view
         */
        public static function renderAutoCompleteField(&$model, &$view)
        {
            $tw = HtmlWriter::getInstance();
            
            self::openFieldLayout($model, $view);
            $tw->openDiv();
            
            $tw->openScript();
            $tw->addAttribute('type', 'text/javascript');
            $tw->writeRaw('_autoCompleteDataSource["' . $model->getName() . '"] = ' . json_encode($model->getDataSource()) . ';');
            $tw->closeScript();
            
            $tw->openInput();
            $tw->addAttribute('type', 'hidden');
            $tw->addAttribute('id', 'autoComplete_' . $model->getName());
            $tw->addAttribute('name', $model->getName());
            $tw->addAttribute('value', $model->getValue());
            $tw->addAttribute('class', $view->getCssClass() . '-autocompletefield');
            $tw->addAttribute('sourceid', $model->getDataSourceIdField());
            $tw->addAttribute('sourcedescription', $model->getDataSourceDscField());
            $tw->closeInput();
            
            $predefinedText = '';
            if ($model->getValue())
            {
                foreach ($model->getDataSource() as $value)
                {
                    if ($value[$model->getDataSourceIdField()] == $model->getValue())
                    {
                        $predefinedText = $value[$model->getDataSourceDscField()];
                        break;
                    }    
                }
            }
            
            $tw->openInput();
            $tw->addAttribute('type', 'text');
            $tw->addAttribute('id', 'autoComplete_' . $model->getName() . '_textField');
            $tw->addAttribute('class', $view->getCssClass() . '-textfield');
            $tw->addAttribute('value', $predefinedText);
            $tw->closeInput();
            
            $tw->openDiv();
            $tw->addAttribute('id', 'autoComplete_' . $model->getName() . '_container');
            $tw->closeDiv(true);
                
            self::renderToolTip($model, $view);
            $tw->closeDiv();
            self::closeFieldLayout($model, $view);
        }
        
        /**
         * Renders a gallery
         *
         * @param Gallery $model
         * @param HtmlFormView $view
         */
        public static function renderGallery(&$model, &$view)
        {
            if ($model->getVisible() === false) return;
            
            $tw = HtmlWriter::getInstance();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-gallery');
            
            // The container's header
            if ($model->getCaption() != '')
            {
                $tw->openDiv();
                $tw->addAttribute('class', $view->getCssClass() . '-gallery-caption');
                $tw->writeContent($model->getCaption());
                $tw->closeDiv();
            }
            
            $imagesNames = $model->getImagesNames();
            
            $tw->openDiv();
            $tw->addAttribute('class', $view->getCssClass() . '-gallery-content');
            
            HtmlRenderCallbacks::renderPersistorField($model->getChildren()->getControl($model->getName() . 'Persistor'), $view);
            
            if (count($imagesNames) == 0)
            {
                $tw->openDiv();
                $tw->addAttribute('style', 'text-align:center');
                $tw->writeContent('No existen imagenes');
                $tw->closeDiv();
            }
            else
            {
                $isDeletable = $model->getIsDeletable();
                $isSelectable = $model->getIsSelectable();
                
                foreach ($imagesNames as $name)
                {
                    $image = $model->getChildren()->getControl($name);
                    
                    $tw->openDiv();
                    $tw->addAttribute('class', $view->getCssClass() . '-gallery-image');
                    $tw->addAttribute('path', $image->getValue());
                    
                    $tw->openImg();
                    $tw->addAttribute('src', $image->getThumbnail());
                    $tw->closeImg();
                    
                    $tw->openDiv();
                    $tw->addAttribute('class', $view->getCssClass() . '-gallery-image-actions');
                    $tw->addAttribute('imageid', $image->getImageId());

                    if ($isSelectable)
                    {
                        $tw->openInput();
                        $tw->addAttribute('class', $view->getCssClass() . '-gallery-image-checkbox');
                        $tw->addAttribute('type', 'checkbox');
                        if (in_array($image->getImageId(), $model->getValue()))
                            $tw->addAttribute('checked', 'checked');
                            
                        $tw->closeInput();
                    }
                    
                    if ($isDeletable)
                    {
                        $tw->openDiv();
                        $tw->addAttribute('class', $view->getCssClass() . '-gallery-image-delete');
                        $tw->closeDiv(true);
                    }
                    
                    $tw->closeDiv(true);
                    
                    $tw->closeDiv();
                }
            }
            $tw->closeDiv(true); // close content

            $tw->openDiv();
            $tw->addAttribute('style', 'clear:both');
            $tw->closeDiv(true);

            $tw->closeDiv(); // close main container

        }
    }
?>