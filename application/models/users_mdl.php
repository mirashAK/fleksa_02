<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users_mdl extends Flx_Model
{  
  public function get_users_list(&$user)
  {
    return $this->full_objects($user, 'public_users');
  }
  
  public function get_user(&$user)
  {
    return $this->row_object($user, 'public_users', 'u_id=1');
  }
  
}