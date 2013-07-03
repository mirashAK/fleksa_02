<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

abstract class Form_Builder
{
  protected $form_data = null;
  protected $CI = null;
  
  //abstract protected function get_data($provided_data = null);
  abstract protected function draw_form($view_name);
  //abstract protected function validate($custom_config = array());
  
  function __construct()
  {
    $this->CI =& get_instance();
    $this->CI->load->helper('form_builder_helper');
  }

  public static function factory ($flx_form_name = '', $flx_form_action = '')
  {
    switch ($flx_form_name)
    {
      case 'auth_form':
        return new Flx_Auth_Form($flx_form_name, $flx_form_action);
        break;
      case 'reg_form':
        return new Flx_Reg_Form($flx_form_name, $flx_form_action);
        break;
      case 'change_pass_form_email':
        return new Flx_Ch_Pass_Form_Email($flx_form_name, $flx_form_action);
        break;
      case 'change_pass_form':
        return new Flx_Ch_Pass_Form($flx_form_name, $flx_form_action);
        break;
      default:
        return new Flx_Edit_Form($flx_form_name, $flx_form_action);
    }
  }
  
  function __set($name, $value)
  {
    if ($name == 'form_data' && !empty($value))
      if (is_array($value)) $this->get_data_array($value);
      elseif (is_object($value)) $this->get_data_object($value);
      
    if ($name == 'name' && !empty($value)) $this->name($value);
    if ($name == 'action' && !empty($value)) $this->action($value);
      
    return true;
  }
  
  function __get($name)
  {
    if (array_key_exists($name, $this->form_data)) return $this->form_data[$name];
    if (array_key_exists($name, $this->form_data['value'])) return $this->form_data['value'][$name];
  }
  
  public function name($form_name)
  {
    $this->form_data['form']['name'] = $form_name;
  }
  
  public function action($form_action)
  {
    $this->form_data['form']['action'] = $form_action;
  }
  
  public function get_data_array($provided_data = null)
  {
    $xhr_request = array();
    if (!empty($provided_data))
    {
      $is_xhr_request = $this->CI->input->is_ajax_request();
      if ($is_xhr_request == true) parse_str($this->CI->input->post($this->form_data['form']['name']), $xhr_request);
      
      foreach ($provided_data['value'] as $field=>$value)
      {
        if ($is_xhr_request == true)
        {
          if (array_key_exists($this->form_data['form']['name']."_$field", $xhr_request))
            $this->form_data['value'][$field] = trim($xhr_request[$this->form_data['form']['name']."_$field"]);
          else
            $this->form_data['value'][$field] = $provided_data['value'][$field];
        }
        else
        {
          $posted_field = trim($this->CI->input->post($this->form_data['form']['name']."_$field"));
          if (!empty($posted_field))
            $this->form_data['value'][$field] = $posted_field;
          else
            $this->form_data['value'][$field] = $provided_data['value'][$field];
        }
        
        $this->form_data['caption'][$field] = $provided_data['caption'][$field];
        $this->form_data['type'][$field] = $provided_data['type'][$field];
        $this->form_data['name'][$field] = $this->form_data['form']['name']."_$field";
      }
    }
    //var_export($this->form_data['value']);
  }
  
  public function get_data_object($provided_data = null)
  {
    $xhr_request = array();
    if (!empty($provided_data))
    {
      $is_xhr_request = $this->CI->input->is_ajax_request();
      if ($is_xhr_request == true) parse_str($this->CI->input->post($this->form_data['form']['name']), $xhr_request);
      
      foreach ($provided_data->value as $field=>$value)
      {
        if ($is_xhr_request == true)
        {
          if (array_key_exists($this->form_data['form']['name']."_$field", $xhr_request))
            $this->form_data['value'][$field] = trim($xhr_request[$this->form_data['form']['name']."_$field"]);
          else
            $this->form_data['value'][$field] = $provided_data->value->$field;
        }
        else
        {
          $posted_field = trim($this->CI->input->post($this->form_data['form']['name']."_$field"));
          if (!empty($posted_field))
            $this->form_data['value'][$field] = $posted_field;
          else
            $this->form_data['value'][$field] = $provided_data->value->$field;
        }
        
        $this->form_data['caption'][$field] = $provided_data->caption->$field;
        $this->form_data['type'][$field] = $provided_data->type->$field;
        $this->form_data['name'][$field] = $this->form_data['form']['name']."_$field";
      }
    }
    //var_export($this->form_data['value']);
  }
  
  public function validate($custom_config = array())
  {
    $errors_arr = array();
    $pass = '';

    if ($this->check_request() == true)
    {
      foreach($this->form_data['type'] as $key=>$value)
        switch ($value)
        {
          case 'email':
              if (strlen($this->form_data['value'][$key]) == 0) $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
              if (!preg_match("/.+\@.+\..+/", $this->form_data['value'][$key])) $errors_arr[$this->form_data['caption'][$key]][] = 'not email';
          break;
          case 'pass':
              $pass = $this->form_data['value'][$key];
              if (strlen($this->form_data['value'][$key]) == 0) $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
          break;
          case 're_pass':
              if (strlen($this->form_data['value'][$key]) == 0) $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
              if ($this->form_data['value'][$key] !== $pass) $errors_arr[$this->form_data['caption'][$key]][] = 'password mismatch';
          break;
        }
      if (empty($errors_arr)) return true;
      else return $errors_arr;
    }
    else return false;
  }
  
  protected function types_transform_to_HTML()
  {
    if (is_array($this->form_data['type']))
      foreach ($this->form_data['type'] as $key=>$value)
        switch ($value)
        {
        case 'email': case 'edit': case 'phone':
            $this->form_data['HTML_type'][$key] = 'text';
            break;
        case 'pass': case 're_pass':
            $this->form_data['HTML_type'][$key] = 'password';
            break;
        default:
            $this->form_data['HTML_type'][$key] = 'text';
        }
  }
  
  protected function check_request()
  {
    if ($this->CI->input->is_ajax_request() == true)
    {
      if ($this->CI->input->post($this->form_data['form']['name']) !==  false) return true;
      else return false;
    }
    else
      foreach ($this->form_data['value'] as $field=>$value)
        if ($this->CI->input->post($this->form_data['form']['name']."_$field") !==  false) return true;
        
    return false;
  }

}

class Flx_Auth_Form extends Form_Builder
{
  function __construct($form_name, $form_action)
  {
    parent::__construct();
    $this->form_data = array ('form'=>array('name'=>$form_name, 'action'=>$form_action));
    $provided_data = array (
      'caption'=>array('user_email'=>'email', 'user_pass'=>'password'),
      'type'    =>array('user_email'=>'email', 'user_pass'=>'pass'),
      'value'   =>array('user_email'=>'', 'user_pass'=>''),
    );
    $this->get_data_array($provided_data);
  }

  public function draw_form($view_name)
  {
    //echo(draw_partial_input($this->form_data, 'u_soname'));
    $this->types_transform_to_HTML();
    return $this->CI->parser->parse($view_name, $this->form_data, true);
  }

}

class Flx_Reg_Form extends Form_Builder
{
  function __construct($form_name, $form_action)
  {
    parent::__construct();
    $this->form_data = array ('form'=>array('name'=>$form_name, 'action'=>$form_action));
    $provided_data = array (
      'caption'=>array('user_email'=>'email', 'user_pass'=>'passwd', 'user_re_pass'=>'password'),
      'type'    =>array('user_email'=>'email', 'user_pass'=>'pass', 'user_re_pass'=>'re_pass'),
      'value'   =>array('user_email'=>'', 'user_pass'=>'', 'user_re_pass'=>''),
    );
    $this->get_data_array($provided_data);
  }

  public function draw_form($view_name)
  {
    $this->types_transform_to_HTML();
    return $this->CI->parser->parse($view_name, $this->form_data, true);
  }
}

class Flx_Edit_Form extends Form_Builder
{
  public function draw_form($view_name)
  {
    if (!empty($this->form_data))
    {
      var_export($this->form_data);
    }
  }
}

class Flx_Ch_Pass_Form_Email extends Form_Builder
{
  function __construct($form_name, $form_action)
  {
    parent::__construct();
    $this->form_data = array ('form'=>array('name'=>$form_name, 'action'=>$form_action));
    $provided_data = array (
      'caption'=>array('user_email'=>'email'),
      'type'    =>array('user_email'=>'email'),
      'value'   =>array('user_email'=>''),
    );
    $this->get_data_array($provided_data);
  }

  public function draw_form($view_name)
  {
    $this->types_transform_to_HTML();
    return $this->CI->parser->parse($view_name, $this->form_data, true);
  }
}

class Flx_Ch_Pass_Form extends Form_Builder
{
  function __construct($form_name, $form_action)
  {
    parent::__construct();
    $this->form_data = array ('form'=>array('name'=>$form_name, 'action'=>$form_action));
    $provided_data = array (
      'caption'=>array('user_pass'=>'passwd', 'user_re_pass'=>'password'),
      'type'    =>array('user_pass'=>'pass', 'user_re_pass'=>'re_pass'),
      'value'   =>array('user_pass'=>'', 'user_re_pass'=>''),
    );
    $this->get_data_array($provided_data);
  }

  public function draw_form($view_name)
  {
    $this->types_transform_to_HTML();
    return $this->CI->parser->parse($view_name, $this->form_data, true);
  }
}
