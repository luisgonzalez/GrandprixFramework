<?php
/**
 * Dummy decorator
 *
 * @access private
 * @package dompdf
 */
class Null_Frame_Decorator extends Frame_Decorator {
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $style = $this->_frame->get_style();
    $style->width = 0;
    $style->height = 0;
    $style->margin = 0;
    $style->padding = 0;
  }
}
?>