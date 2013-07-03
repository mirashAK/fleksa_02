<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class List_Builder
{
  public $list_data = Null;
  
  public function draw_table ()
  {
    if (!empty($this->list_data))
    {
      var_export($this->list_data);
    }
  }

}
