<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Flx_User extends Flx_Model
{ 
  public function do_auth(&$user, $email, $pass)
  {
    $sql = "SELECT do_auth (?,?,?,?) AS 'new_token' ;";
    $first_result = $this->db->query($sql, array($email, $pass, $user->user_ip, $user->user_token));
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();
      if ($first_result['new_token'] !== '-2' && $first_result['new_token'] !== '-1')
        return $first_result['new_token'];
      else return false;
    }
    else return false;
  }
    
  public function add_user($email, $pass, $login = '')
  {
    $sql = "SELECT add_user (?,?,?) AS 'get_params' ;";
    $first_result = $this->db->query($sql, array($login, $email, $pass));
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      $first_result = $first_result->row_array();
      if ($first_result['get_params'] !== '0') return $first_result['get_params'];
      else return false;
    }
    else return false;
  }
  
  public function reg_user(&$user, $reg_token)
  {
    $sql = "SELECT reg_user (?,?,?) AS 'new_token' ;";
    if (!empty($user))
      $first_result = $this->db->query($sql, array($reg_token, $user->user_ip, $user->user_token));
    else
      $first_result = $this->db->query($sql, array($reg_token, '', ''));

    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();

      if ($first_result['new_token'] !== '-2' && $first_result['new_token'] !== '-1' && $first_result['new_token'] !== '0')
        return $first_result['new_token'];
      else return false;
    }
    else return false;
  }
  
  public function token_passwd($user_email)
  {
    $sql = "SELECT token_passwd (?) AS 'get_params' ;";
    $first_result = $this->db->query($sql, array($user_email));
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      $first_result = $first_result->row_array();
      if ($first_result['get_params'] !== '0' && $first_result['get_params'] !== '-1') return $first_result['get_params'];
      else return false;
    }
    else return false;
  }
  
  public function reset_passwd(&$user, $pass_token, $new_passwd)
  {
    $sql = "SELECT reset_passwd (?,?,?,?) AS 'new_token' ;";
    
    if (!empty($user))
      $first_result = $this->db->query($sql, array($pass_token, $user->user_ip, $new_passwd, $user->user_token));
    else
      $first_result = $this->db->query($sql, array($pass_token, '', $new_passwd, ''));
    
    if (!empty($first_result) && $first_result->num_rows() == 1)
    {
      // Получаем результат запроса стандартным методом CI
      $first_result = $first_result->row_array();

      if ($first_result['new_token'] !== '-2' && $first_result['new_token'] !== '-1' && $first_result['new_token'] !== '0')
        return $first_result['new_token'];
      else return false;
    }
    else return false;
  }
  
  public function logout(&$user)
  {
    $sql = "SELECT logout_user (?,?) AS 'new_token' ;";
    if (!empty($user))
    {
      $first_result = $this->db->query($sql, array($user->user_token, $user->user_ip));
      
      if (!empty($first_result) && $first_result->num_rows() == 1)
      {
        $first_result = $first_result->row_array();
        return $first_result['new_token'];
      }
    }
    else return false;
  }
  
}