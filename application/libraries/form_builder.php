<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Form_Builder
{
  protected $form_data = null;
  protected $CI = null;
  
  public $xhr_answer = null;
  public $errors = array();
  
  function __construct()
  {
    $this->CI =& get_instance();
    $this->CI->load->helper('form_builder_helper');
    $this->CI->load->library('xhr_answer');
    $this->xhr_answer = & $this->CI->xhr_answer;
  }

  public static function factory ($flx_form_name = '', $flx_form_action = '')
  {
    $CI =& get_instance();
    $CI->config->load('flx_forms');
    
    $config_item_name = 'flx_'.strtolower($flx_form_name);
    $config = $CI->config->item($config_item_name);
    
    
    if (empty($config))
    {
      $form = new Flx_DB_Form ();
      $form->name = $flx_form_name;
      $form->action = $flx_form_action;
    }
    else
    { 
      $form = new Flx_Custom_Form ();
      $form->name = $flx_form_name;
      $form->action = $flx_form_action;
      $form->get_data_array($config);
    }
    return $form;
  }
  
  function __set($name, $value)
  {
    switch ($name)
    {
      case 'form_data':
        if (is_array($value) && array_key_exists('value', $value)) $this->get_data_array($value);
        elseif (is_object($value) && isset($value->value)) $this->get_data_object($value);
        break;
      case 'name':
        $this->name($value);
        break;
      case 'action':
        $this->action($value);
        break;
      default:
        if (array_key_exists($name, $this->form_data['value'])) $this->form_data['value'][$name] = $value;
    }
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
  
  function add_error($field_name, $value)
  {
    $this->errors[$field_name][] = $value;
  }
  
  public function get_data_array($provided_data = null)
  {
    $xhr_request = array();
    if (!empty($provided_data))
    {
      $this->form_data = array_merge($this->form_data, $provided_data);
    
      $is_xhr_request = $this->CI->input->is_ajax_request();
      if ($is_xhr_request == true) parse_str($this->CI->input->post($this->form_data['form']['name']), $xhr_request);
      
      foreach ($provided_data['value'] as $field=>$value)
      {
        $this->form_data['name'][$field] = $this->form_data['form']['name']."_$field";
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
      }
      if(!empty($provided_data['is_new'])) $this->form_data['is_new'] = $provided_data['is_new'];
      else $this->form_data['is_new'] = false;
    }
    //var_export($this->form_data['value']);
  }
  
  public function get_data_object($provided_data = null)
  {
    $xhr_request = array();
    if (!empty($provided_data))
    {
      $this->form_data = array_merge($this->form_data, (array)$provided_data);
      
      foreach ($this->form_data as $key=>$value) $this->form_data[$key] = (array)$value;
      if(array_key_exists('dict', $this->form_data))
        foreach ($this->form_data['dict'] as $key=>$value) $this->form_data['dict'][$key] = (array)$value;
        
      $is_xhr_request = $this->CI->input->is_ajax_request();
      if ($is_xhr_request == true) parse_str($this->CI->input->post($this->form_data['form']['name']), $xhr_request);
      
      foreach ($provided_data->value as $field=>$value)
      {
        $this->form_data['name'][$field] = $this->form_data['form']['name']."_$field";
        
        if ($is_xhr_request == true)
        {
          if (array_key_exists($this->form_data['name'][$field], $xhr_request))
            $this->form_data['value'][$field] = trim($xhr_request[$this->form_data['name'][$field]]);
          else
            $this->form_data['value'][$field] = $provided_data->value->$field;
        }
        else
        {
          $posted_field = trim($this->CI->input->post($this->form_data['name'][$field]));
          if (!empty($posted_field))
            $this->form_data['value'][$field] = $posted_field;
          else
            $this->form_data['value'][$field] = $provided_data->value->$field;
        }
        
      }
      if(!empty($provided_data->is_new)) $this->form_data['is_new'] = $provided_data->is_new;
      else $this->form_data['is_new'] = false;
    }
    //var_export($this->form_data);
  }
  
  public function validate($custom_config = array())
  {
    $errors_arr = array();
    $pass = '';
    if ($this->check_request() === true)
    {
      foreach($this->form_data['type'] as $key=>$value)
        switch ($value)
        {
          case 'email':
              if (strlen($this->form_data['value'][$key]) == 0)
              {
                $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
                break;
              }
              if (!preg_match("/.+\@.+\..+/", $this->form_data['value'][$key])) $errors_arr[$this->form_data['caption'][$key]][] = 'not email';
          break;
          case 'subdomain':
              if (strlen($this->form_data['value'][$key]) == 0)
              { 
                $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
                break;
              }
              $this->form_data['value'][$key] = strtolower ($this->form_data['value'][$key]);
              if (!preg_match("/^[a-z0-9]+$/", $this->form_data['value'][$key])) $errors_arr[$this->form_data['caption'][$key]][] = 'forbidden chars';
          break;
          case 'pass':
              $pass = $this->form_data['value'][$key];
              if (strlen($this->form_data['value'][$key]) == 0) $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
          break;
          case 're_pass':
              if (strlen($this->form_data['value'][$key]) == 0)
              {
                $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
                break;
              }
              if ($this->form_data['value'][$key] !== $pass) $errors_arr[$this->form_data['caption'][$key]][] = 'password mismatch';
          break;
          default:
              if ($this->form_data['require'][$key] == true && strlen($this->form_data['value'][$key]) == 0)
              {
                $errors_arr[$this->form_data['caption'][$key]][] = 'empty';
                break;
              }
        }
      if (empty($errors_arr)) return true;
      else
      {
        $this->errors = $errors_arr;
        return false;
      }
    }
    else
    {
      $this->errors['Validation'] = 'Wrong request';
      return false;
    }
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
        
    if (!empty($this->form_data['r_only']) && is_array($this->form_data['r_only']))
      foreach ($this->form_data['r_only'] as $key=>$value)
        if ($value) $this->form_data['r_only'][$key] = 'disabled';
        else $this->form_data['r_only'][$key] = '';
  }
  
  protected function check_request()
  {
    if ($this->CI->input->is_ajax_request() == true)
    {
      if ($this->CI->input->post($this->form_data['form']['name']) !==  false) return true;
      else return false;
    }
    else
      foreach ($this->form_data['type'] as $field=>$value)
        if ($this->CI->input->post($this->form_data['form']['name']."_$field") !==  false) return true;
        
    return false;
  }
  
  public function draw_form($view_name = null, &$view_data = null)
  {
    if (!empty($view_data)) $this->form_data = array_merge($this->form_data, $view_data);
    //echo(draw_partial_input($this->form_data, 'u_soname'));
    $this->types_transform_to_HTML();
    $this->form_data['dict'] = '';
    
    if ($this->CI->input->is_ajax_request() === true)
    {
      if (!empty($this->errors))
      {
        $this->xhr_answer->valid = false;
        $this->xhr_answer->errors = $this->errors;
      }
      if (!empty($view_name)) $this->xhr_answer->view = $this->CI->parser->parse($view_name, $this->form_data, true);
      $this->xhr_answer->send();
    }
    else
    {
      if (!empty($view_name)) return $this->CI->parser->parse($view_name, $this->form_data, true);
      else return '';
    }
  }
  
  public function get_values()
  {
    $result = '';
    
    foreach ($this->form_data['value'] as $key => $value)
      if ($key !== 'r_only' && $key !== 'owner')
        if ($this->form_data['r_only'][$key] !== true)
          $result .= '\"'.$key.'\":\"'.$value.'\",';
        
    return $result;
  }

}

class Flx_DB_Form extends Form_Builder
{
  function __construct()
  {
    parent::__construct();
  }
}

class Flx_Custom_Form extends Form_Builder
{
  function __construct()
  {
    parent::__construct();
  }
 
  public function get_data_array($provided_data = null)
  {
    $prepared_arr = array ();

    foreach ($provided_data['type'] as $field=>$field_type)
    {
      $prepared_arr['type'][$field] = $field_type;
      $prepared_arr['caption'][$field] = (isset($provided_data['caption'][$field])) ? $provided_data['caption'][$field] : $field;
      $prepared_arr['require'][$field] = (isset($provided_data['require']) && in_array($field, $provided_data['require'])) ? true : false;
      $prepared_arr['unique'][$field] = (isset($provided_data['unique']) && in_array($field, $provided_data['unique'])) ? true : false;
      $prepared_arr['value'][$field] = (isset($provided_data['value'][$field])) ? $provided_data['value'][$field] : '';
    }

    parent::get_data_array($prepared_arr);
  }
}
