<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Xhr_Answer  
{
  public $answer_data = array();

  public function __construct()
  {
    $this->answer_data['valid'] = true;
    $this->answer_data['errors'] = array();
    $this->answer_data['view'] = false;
    $this->answer_data['redirect']= false;
    $this->answer_data['update']= false;
    $this->answer_data['message']= false;
  }
  
  public function send()
  {
    $json_data = json_encode($this->answer_data);
    $json_data_length = strlen($json_data);

    //ob_end_clean();
    header("Connection: close");
    ignore_user_abort(); // optional
    ob_start();
    echo($json_data);
    $size = ob_get_length();
    header("Content-Length: {$json_data_length}");
    ob_end_flush(); // Strange behaviour, will not work
    flush(); // Unless both are called !
  }
  
  public function __set($name, $value)
  {
    $this->answer_data[$name] = $value;
  }
  
  public function __get($name)
  {
    if (array_key_exists($name, $this->answer_data)) return $this->answer_data[$name];
    else return false;
  }
    
}