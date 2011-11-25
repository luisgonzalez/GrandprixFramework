<?php
    /**
     * Represents a generic Html tag writer
     *
     * @package Grandprix
     * @subpackage HTML
     */
    class HtmlWriter
    {
        protected $tagStack;
        protected $indentEnabled;
     
        /**
         * @var HtmlWriter
         */
        private static $__instance = null;
        
        /**
         * Gets the singleton instance for this class.
         *
         * @return HtmlWriter
         */
        public static function getInstance()
        {
            if (self::isLoaded() === false) self::$__instance = new HtmlWriter();
            
            return self::$__instance;
        }
        
        /**
         * Determines whether the singleton has loaded its instance
         *
         * @return bool
         */
        public static function isLoaded()
        {
            if (is_null(self::$__instance)) return false;
            
            return true;
        }
        
        protected function __construct($indentEnabled = false)
        {
            $this->tagStack = array();
            $this->indentEnabled = $indentEnabled;
        }
     
        /**
         * This is used for openX and closeX
         *
         * @param str $method
         * @param array $arguments
         */
        public function __call($method, $arguments)
        {
            switch(substr($method, 0, 4))
            {
                case 'open':
                    $tagName = strtolower(substr($method, 4));
                    $this->openTag($tagName);
                    return;
                    break;
                case 'clos':
                    $tagName = strtolower(substr($method, 5));
                
                    if ($this->getCurrentTag()->TagName == $tagName)
                    {
                        $fullClose = false;
                        if (count($arguments) > 0 && $arguments[0] === true)
                        {
                            $fullClose = true;
                        }
                        $this->closeTag($fullClose);
                        return;
                    }
                    else
                    {
                        throw new GrandprixException(GrandprixException::EX_PARAMETERNOTOPTIONAL, 'The current tag does not match the close tag command.');
                    }
                    break;
            }
     
            throw new Exception('The dynamic method is either malformed or does not exist');
        }
     
        /**
         * Opens a new tag and pushes it into the tag stack.
         *
         * @param str $tagName
         */
        public function openTag($tagName)
        {
            if (is_null($this->getCurrentTag()) === false)
            {
                if ($this->getCurrentTag()->HasContent === false)
                {
                    print '>';
                    $this->getCurrentTag()->HasContent = true;
                }
            }
            
            $tagName = strtolower($tagName);
            $htmlTag = new HtmlTag($tagName);
            $this->tagStack[] = $htmlTag;
            
            if ($this->indentEnabled) print "\r\n" . str_repeat(' ', count($this->tagStack) * 4);
                print '<' . $tagName;
        }
     
        /**
         * Adds an attribute to the current opened tag befor its content has begun.
         *
         * @param str $attributeName
         * @param str $attributeValue
         */
        public function addAttribute($attributeName, $attributeValue)
        {
            if ($this->getCurrentTag()->HasContent === true)
                throw new Exception('Tag content has already started.');
            
            $this->getCurrentTag()->HasAttributes = true;
            print ' ' . $attributeName . '="' . htmlentities($attributeValue) . '"';
        }
     
        /**
         * Wrties html-encoded content into the tag.
         *
         * @param str $contentText The raw text to write. Conent will be encoded as HTML.
         * @param bool $indent Determines whether or not to indent the content.
         * @param bool $useNlToBr Determines whether or not to encode new lines to br tags.
         * @param bool $useSpToNbsp Determines whether or not to encode spaces into non-breaking-spaces.
         */
        public function writeContent($contentText, $indent = true, $useNlToBr = true, $useSpToNbsp = false)
        {
            if ($this->getCurrentTag()->HasContent !== true)
            {
                print '>';
                
                if ($this->indentEnabled && $indent)
                   print "\r\n" . str_repeat(' ', (count($this->tagStack) + 1) * 4);
                
                $this->getCurrentTag()->HasContent = true;
            }
            
            print self::htmlEncode($contentText, $useNlToBr, $useSpToNbsp);
        }
        
        /**
         * Writes content inside the tag without encoding it.
         * Writing tag content with this method is not recommended.
         *
         * @param str $rawText
         */
        public function writeRaw($rawText)
        {
            if ($this->getCurrentTag()->HasContent !== true)
            {
                print '>';
                $this->getCurrentTag()->HasContent = true;
            }
            
            print $rawText;                
        }
     
     
        /**
         * Closes the tag and pops it out of the tag stack.
         *
         * @param bool $fullClose
         */
        public function closeTag($fullClose = false)
        {
            if ($this->getCurrentTag()->HasContent === true)
            {
                if ($this->indentEnabled && $this->getCurrentTag()->TagName != 'textarea')
                    print "\r\n" . str_repeat(' ', count($this->tagStack) * 4);
                
                print '</' . $this->getCurrentTag()->TagName . '>';
            }
            else
            {
                if ($fullClose === false)
                    print ' />';
                else
                    print '></' . $this->getCurrentTag()->TagName . '>';
            }
            
            array_splice($this->tagStack, count($this->tagStack) - 1, 1);
        }
     
        /**
         * Gets the current tag object
         *
         * @return HtmlTag
         */
        public function getCurrentTag()
        {
            if (count($this->tagStack) > 0)
                return $this->tagStack[count($this->tagStack) - 1];
          
            return null;
        }
     
        /**
         * Encodes the content for HTML output.
         *
         * @param str $content
         * @param bool $useNlToBr Determines whther new new lines are converted to HTML line breaks.
         * @param bool $useSpToNbsp Determines whether spaces are converted to HTML non-breaking spaces.
         * @return str
         */
        public static function htmlEncode($content, $useNlToBr = true, $useSpToNbsp = true)
        {
            $returnStr = ($useNlToBr == true) ? nl2br(htmlentities($content)) : htmlentities($content);
            $returnStr = ($useSpToNbsp == true) ? str_replace(' ', '&nbsp;', $returnStr) : $returnStr;
            return $returnStr;
        }
        
        /**
         * Html tag-closing mechanism which checks for the correct closing tag.
         * @param string $tagName The intended tag to close. If the closing tag provided does not match the current tag in the stack, this method will throw an exception.
         * @param bool $fullClose 
         */
        protected function closeHtmlTag($tagName, $fullClose = false)
        {
            if ($this->getCurrentTag()->TagName !== $tagName)
                throw new Exception('The current tag \'' . $this->getCurrentTag()->TagName . '\' does not match the close tag \'' . $tagName . '\' command.');
            
            $this->closeTag($fullClose);
        }
        
        /**
         * Opens the given HTML tag.
         */
        public function openDiv()
        {
            $this->openTag('div');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openTr()
        {
            $this->openTag('tr');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openTd()
        {
            $this->openTag('td');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openTable()
        {
            $this->openTag('table');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openSpan()
        {
            $this->openTag('span');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openInput()
        {
            $this->openTag('input');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openLink()
        {
            $this->openTag('link');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openA()
        {
            $this->openTag('a');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openScript()
        {
            $this->openTag('script');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openHtml()
        {
            $this->openTag('html');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openHead()
        {
            $this->openTag('head');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openBody()
        {
            $this->openTag('body');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openForm()
        {
            $this->openTag('form');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openTextArea()
        {
            $this->openTag('textarea');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openLabel()
        {
            $this->openTag('label');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openSelect()
        {
            $this->openTag('select');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openOption()
        {
            $this->openTag('option');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openImg()
        {
            $this->openTag('img');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openUl()
        {
            $this->openTag('ul');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openLi()
        {
            $this->openTag('li');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openButton()
        {
            $this->openTag('button');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openStyle()
        {
            $this->openTag('style');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openThead()
        {
            $this->openTag('thead');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openTbody()
        {
            $this->openTag('tbody');
        }
        /**
         * Opens the given HTML tag.
         */
        public function openOptgroup()
        {
            $this->openTag('optgroup');
        }
        
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeDiv($fullClose = false)
        {
            $this->closeHtmlTag('div', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeTr($fullClose = false)
        {
            $this->closeHtmlTag('tr', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeTd($fullClose = false)
        {
            $this->closeHtmlTag('td', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeTable($fullClose = false)
        {
            $this->closeHtmlTag('table', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeSpan($fullClose = false)
        {
            $this->closeHtmlTag('span', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeInput($fullClose = false)
        {
            $this->closeHtmlTag('input', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeLink($fullClose = false)
        {
            $this->closeHtmlTag('link', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeA($fullClose = false)
        {
            $this->closeHtmlTag('a', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeScript($fullClose = false)
        {
            $this->closeHtmlTag('script', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeHtml($fullClose = false)
        {
            $this->closeHtmlTag('html', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeHead($fullClose = false)
        {
            $this->closeHtmlTag('head', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeBody($fullClose = false)
        {
            $this->closeHtmlTag('body', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeForm($fullClose = false)
        {
            $this->closeHtmlTag('form', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeTextArea($fullClose = false)
        {
            $this->closeHtmlTag('textarea', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeLabel($fullClose = false)
        {
            $this->closeHtmlTag('label', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeSelect($fullClose = false)
        {
            $this->closeHtmlTag('select', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeOption($fullClose = false)
        {
            $this->closeHtmlTag('option', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeImg($fullClose = false)
        {
            $this->closeHtmlTag('img', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeUl($fullClose = false)
        {
            $this->closeHtmlTag('ul', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeLi($fullClose = false)
        {
            $this->closeHtmlTag('li', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeButton($fullClose = false)
        {
            $this->closeHtmlTag('button', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeStyle($fullClose = false)
        {
            $this->closeHtmlTag('style', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeThead($fullClose = false)
        {
            $this->closeHtmlTag('thead', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeTbody($fullClose = false)
        {
            $this->closeHtmlTag('tbody', $fullClose);
        }
        /**
         * Closes the given HTML tag.
         * @param bool $fullClose 
         */
        public function closeOptgroup($fullClose = false)
        {
            $this->closeHtmlTag('optgroup', $fullClose);
        }
    }
    
    /**
     * Represents an HTML tag to be used in the HtmlWriter class
     *
     * @package Grandprix
     * @subpackage HTML
     */
    class HtmlTag
    {
        public $TagName;
        public $HasContent;
        public $HasAttributes;
     
        public function __construct($tagName)
        {
            $this->TagName = $tagName;
            $this->HasAttributes = false;
            $this->HasContent = false;
        }
    }
?>