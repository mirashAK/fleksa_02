<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lib_Test extends Test_Controller
{ 

  function __construct ()
  {
    parent::__construct();

  }

  public function _remap()
  {
    echo('Lib_Test'); echo('<br/>');
  }
  
  public function index()
  {

  }
  
}