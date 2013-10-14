<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class secured_lib extends Front_Controller
{
  protected $CI = null;
  protected $xhr_answer = null;
  protected $is_xhr = false;

  public function __construct()
  {
    $this->CI =& get_instance();
    
    if ($this->CI->input->is_ajax_request() === true)
    {
      $this->is_xhr = true;
      $this->xhr_answer = $this->CI->load->library('xhr_answer');
      $this->user = $this->CI->user;
    }
  }
  
  public function __call ( $method, $argv )
  {
    if (method_exists($this, $method)) 
    {
      if ($this->is_xhr == true)
      {
        if (true === $this->xhr_check($method, $argv)) return call_user_func_array(array($this, $method), $argv);
      }
      else 
      {
        if (true === $this->check($method, $argv)) return call_user_func_array(array($this, $method), $argv);
      }
    }
    
    return false;
  }
  
  private function check($method, $argv)
  {
    echo "Calling object method '$method, ' "
             . implode(', ', $argv). "\n";
    return true;
    
    if ($this->user->user_id == 0)
    {
      $this->CI->user_session->last_sub = sub_domain();
      $this->CI->user_session->token=$this->input->get('token', '');
      $this->CI->user_session->last_page = $this->uri->uri_string;
      $this->CI->redirect(sub_url('auth'));
    }
    // Redirect if user not in this sub_domain
    if (false !== sub_domain() && $this->user->is_ws_member == false) $this->redirect(base_url('cabinet'));
    
    $this->load->model('workspaces_mdl', 'ws_mdl');
    $this->load->model('tasks_mdl');
    
    if (sub_domain() !== false && $this->ws_params !== false)
    {
      // Security: check ws admin
      if ($this->user->is_ws_admin === false && ($this->uri->segment(1) !== 'workspace' || $this->uri->segment(2) !== 'inactive')) redirect(base_url('cabinet'), 'refresh');
      
      $this->view_data['new_tasks_count'] = $this->tasks_mdl->count_new_tasks();
    }
    else $this->view_data['new_tasks_count'] = 0;
    
  }
  
  private function xhr_check($method, $argv)
  {
    echo "Calling object method '$method, ' "
            . implode(', ', $argv). "\n";
    return true;
    
      if ($this->input->is_ajax_request() === true) 
      {
        $this->load->library('xhr_answer');
        $this->xhr_answer->redirect = base_url('auth');
        $this->xhr_answer->send();
      }
  }
  
}