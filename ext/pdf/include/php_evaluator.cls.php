<?php
/**
 * Executes inline PHP code during the rendering process
 *
 * @access private
 * @package dompdf
 */
class PHP_Evaluator {
  
  protected $_canvas;

  function __construct(Canvas $canvas) {
    $this->_canvas = $canvas;
  }

  function evaluate($code) {

    if ( !DOMPDF_ENABLE_PHP )
      return;
    
    // Set up some variables for the inline code
    $pdf = $this->_canvas;
    $PAGE_NUM = $this->_canvas->get_page_number();
    $PAGE_COUNT = $this->_canvas->get_page_count();

    eval(utf8_decode($code)); 
  }

  function render($frame) {
    $this->evaluate($frame->get_node()->nodeValue);
  }
}
?>