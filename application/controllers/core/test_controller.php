<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Test_Controller extends Flx_Controller 
{
  function __construct() 
  {
    parent::__construct($db_alias = 'test');
  }
}