<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Admin_Controller extends Flx_Controller
{    
    function __construct() 
    {
      parent::__construct();
      
      $this->view_data['site_title'] = '';
      $this->view_data['site_metadata'] = '';
      $this->view_data['site_header'] = '';
      $this->view_data['site_footer'] = '';
      $this->view_data['site_body'] = '';
    }
}

class Front_Controller extends Flx_Controller
{
    function __construct()
    {
      parent::__construct();

      $this->lang->load('site/titles', lang());
      $this->lang->load('site/forms', lang());
     
      $this->load->library('menu_lib');
      
      $this->view_data['transl'] = $this->lang->language; // Translation array
      $this->view_data['lang'] = lang();

      $this->view_data['site_title'] = '';
      $this->view_data['site_metadata'] = '';
      $this->view_data['site_metadata_description'] = '';
      $this->view_data['site_metadata_keywords'] = '';
      
      $this->view_data['site_header'] = '';
      $this->view_data['site_footer'] =  '';
      $this->view_data['site_body'] = '';
      $this->view_data['breadcrumbs'] = '';
    }
}


