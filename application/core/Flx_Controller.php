<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Flx_Controller extends CI_Controller 
{
  protected $view_data = array();
  
  function __construct ($db_alias = 'default')
  {
    parent::__construct();
    $this->view_data['scripts'] = array();
    
    $this->load->database($db_alias);
    $this->load->library('core/flx_user_lib', array(), 'user');
    $this->set_language(isset($this->user->user_public->u_lang)?$this->user->user_public->u_lang:null);
    
    $this->view_data['base_url'] = base_url();
    $this->view_data['sub_url'] = sub_url();
    $this->view_data['res_url'] = res_url();
      
    $this->view_data['res_js'] = res_url('assets/js/');
    $this->view_data['res_css'] = res_url('assets/css/');
    $this->view_data['res_img'] = res_url('assets/img/');
    $this->view_data['res_btsp'] = res_url('assets/bootstrap/');
  }
  
  protected function parse_in($view_name, $custom_data = null)
  {
    if (!empty($this->parser))
      if (!empty($custom_data)) return $this->parser->parse($view_name, $custom_data, TRUE);
      else return $this->parser->parse($view_name, $this->view_data, TRUE);
  }

  protected function parse_out($view_name)
  {
    if (!empty($this->parser))
      $this->parser->parse($view_name, $this->view_data);
  }

  protected function set_language($user_lang = null)
  {
    $this->config->load('languages');
    $lang = '';
    if (empty($user_lang))
    {
      if (empty($this->uri->lang))
      {
        if ($this->config->item('lang_check_browser')) $lang =  get_browser_lang();
      } 
      else $lang = $this->uri->lang;
    
      if (!in_array($lang, $this->config->item('lang_supported')))
      {
        $ru_regions = $this->config->item('lang_ru_region');
        if (!empty($ru_regions) && in_array($lang, $ru_regions)) $lang = 'ru';
        else $lang = $this->config->item('language');
      }
      $this->config->set_item('language', $lang);
    
      if ($this->config->item('lang_redirect') && (empty($this->uri->lang) || $lang !== $this->uri->lang))
        redirect(sub_url((!$this->config->item('lang_url_include')?$lang.'/':'').uri_string()), 'refresh');
    }
    else
    {
    $this->config->set_item('language', $user_lang);
    
    if ($this->config->item('lang_redirect') && (empty($this->uri->lang) || $user_lang !== $this->uri->lang))
        redirect(sub_url((!$this->config->item('lang_url_include')?$user_lang.'/':'').uri_string()), 'refresh');
    }
  }
  
  protected function add_script($script_name)
  {
    if (!empty($this->view_data['res_js'])) $script_name = $this->view_data['res_js'].'/'.trim( $script_name,'/');
    
    if (!array_key_exists('scripts', $this->view_data)) $this->view_data['scripts'] = array();
      
    $this->view_data['scripts'][] = array('script'=>'<script src="'.$script_name.'"></script>');
  }

  protected function redirect($url, $type = 'refresh')
  {
    $this->user_session->save();
    $this->user->save_public();
    redirect($url, $type);
  }
}

  require_once APPPATH.'controllers/core/front_controller.php';
  require_once APPPATH.'controllers/core/test_controller.php';

  require_once APPPATH.'libraries/common/safe_class.php';
  require_once APPPATH.'libraries/common/xhr_answer.php';
  
  require_once APPPATH.'libraries/core/flx_form_builder_lib.php';
  

/* End of file Flx_Controller.php */
/* Location: ./application/core/Flx_Controller.php */