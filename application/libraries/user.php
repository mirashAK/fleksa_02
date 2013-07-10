<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class User  
{
  public $user_token = null;
  public $user_ip = null;
  
  public function __construct()
  {
    $this->load->model('flx_session', 'user_session');
    $this->load->model('flx_user', 'user_mdl');
    $this->load->library('encrypt');
    $this->_set_values();
  }
  
  public function do_auth ($email, $pass)
  {
    $pass = $this->_encrypt_pass($pass);
    
    $new_token = $this->user_mdl->do_auth($this, $email, $pass);
    if ($this->user_mdl->check_error($new_token, 'flx_do_auth') == true)
    {
      $this->user_session->sess_token = $new_token;
      $this->input->set_cookie('user_token', $new_token, 31536000);
      $this->_set_values();
      return true;
    }
    else return $this->user_mdl->get_error_text($new_token, 'flx_do_auth');
  }
  
  public function add_user ($email, $pass, $login = '')
  {
    $pass = $this->_encrypt_pass($pass);
    $get_params = $this->user_mdl->add_user($email, $pass, $login);
    if ($this->user_mdl->check_error($get_params, 'flx_add_user') == true)
    {
      return $get_params;
    }
    else return $this->user_mdl->get_error_text($new_token, 'flx_add_user');
  }
  
  public function reg_user ()
  {
    $token = $this->input->get('token');
    if (!empty($token))
    {
      $new_token = $this->user_mdl->reg_user($this, $token);
      if ($this->user_mdl->check_error($new_token, 'flx_reg_user') == true)
      {
        $this->user_session->sess_token = $new_token;
        $this->input->set_cookie('user_token', $new_token, 31536000);
        $this->_set_values();
        return true;
      }
      else $this->user_mdl->get_error_text($new_token, 'flx_reg_user');
    }
    else return false;
  }
  
  public function logout ()
  {
    $new_token = $this->user_mdl->logout($this);
    if (!empty($new_token))
    {
      $this->user_session->sess_token = $new_token;
      $this->input->set_cookie('user_token', $new_token, 31536000);
      $this->_set_values();
      return true;
    }
    else return false;
  }
  
  public function token_passwd ($email)
  {
    $get_params = $this->user_mdl->token_passwd($email);
    if ($this->user_mdl->check_error($get_params, 'flx_token_passwd') == true)
    {
      return $get_params;
    }
    else $this->user_mdl->get_error_text($get_params, 'flx_token_passwd');
  }
  
  public function reset_passwd ($token, $new_passwd)
  {
    if (!empty($token) && !empty($new_passwd))
    {
      $new_passwd = $this->_encrypt_pass($new_passwd);
      $new_token = $this->user_mdl->reset_passwd($this, $token, $new_passwd);
      if ($this->user_mdl->check_error($new_token, 'flx_reset_passwd') == true)
      {
        $this->user_session->sess_token = $new_token;
        $this->input->set_cookie('user_token', $new_token, 31536000);
        $this->_set_values();
        return true;
      }
      else $this->user_mdl->get_error_text($new_token, 'flx_reset_passwd');
    }
    else return false;
  }
  
  protected function _encrypt_pass ($passwd)
  {
    return $this->encrypt->sha1($passwd);
  }
  
  protected function _set_values()
  {
    $result = $this->user_session->get_user();
    
    if ($this->user_session->sess_status !== -1)
      foreach ($result as $key=>$value)
      {
        if ($key!=='last_ip') $this->$key = $value;
        if ($key=='user_id') $this->$key = (int)$value;
      }
      
    $this->user_token = $this->user_session->sess_token;
    $this->user_ip = $this->user_session->sess_ip;
    
    unset($result);
  }
  
  /**
    * __get
    *
    * Allows models to access CI's loaded classes using the same
    * syntax as controllers.
    *
    * @param   string
    * @access private
    */
  function __get($key)
  {
      $CI =& get_instance();
      return $CI->$key;
  }
  
}