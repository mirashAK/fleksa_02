<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// Класс для обеспечения работы с системой меню
class Menu_Lib
{

  function __construct()
  {
    $this->load->model('menu_mdl', 'model');
    $this->lang->load('site/menus', lang());
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
  
  // ----------------------------------- Methods --------------------------------------- //
  
  public function create_top_app_menu(&$view_data)
  {
    $menu = $this->model->get_sub_menu($this->user, 'top_app_menu');

    $prefix = 'tpm_';

    if (!empty($menu['values']))
      foreach($menu['values'] as $value)
      {
        $result_array = array();
        $result_array['link'] = sub_url($value['menu_item_link']);
        $result_array['name'] = $value['menu_item_name'];
        $result_array['caption'] = $this->lang->line($prefix.$value['menu_item_name']);
        
        $sub_items = $this->model->get_sub_menu($this->user, $value['menu_item_name']);

        $result_array['class_if_current'] = '';
        
        if ($value['menu_item_link'] == substr ($this->uri->uri_string , 0 , strlen($value['menu_item_link']))) $result_array['class_if_current'] = 'class="is-current"';
        elseif (!empty($sub_items['values']))
        { 
          foreach($sub_items['values'] as $sub_item)
          {
            if ($sub_item['menu_item_link'] == substr ($this->uri->uri_string , 0 , strlen($sub_item['menu_item_link']))) $result_array['class_if_current'] = 'class="is-current"';
          }
        }
        
        $view_data[$prefix.$value['menu_item_name']] = $result_array;
      }
  }

  public function create_bottom_menu(&$view_data)
  {
    $menu = $this->model->get_sub_menu($this->user, 'bottom_menu');

    $prefix = 'btm_';
    $class_current = 'class="is-current"';
    
    if (!empty($menu['values']))
      foreach($menu['values'] as $value)
      {
        $result_array = array();
        $result_array['link'] = sub_url($value['menu_item_link']);
        $result_array['name'] = $value['menu_item_name'];
        $result_array['caption'] = $this->lang->line($prefix.$value['menu_item_name']);
        
        $sub_items = $this->model->get_sub_menu($this->user, $value['menu_item_name']);

        $result_array['class_if_current'] = '';
        
        if ($value['menu_item_link'] == substr ($this->uri->uri_string , 0 , strlen($value['menu_item_link']))) $result_array['class_if_current'] = $class_current;
        elseif (!empty($sub_items['values']))
        { 
          foreach($sub_items['values'] as $sub_item)
          { 
            if ($sub_item['menu_item_link'] == substr ($this->uri->uri_string , 0 , strlen($sub_item['menu_item_link']))) $result_array['class_if_current'] = $class_current;
          }
        }
        
        $view_data[$prefix.$value['menu_item_name']] = $result_array;
      }
  }
  
  public function create_front_main_menu(&$view_data)
  {
    $menu = $this->model->get_sub_menu($this->user, 'main_menu');

    $prefix = 'fpm_';
    $class_current = 'class="is-current"';
    
    if (!empty($menu['values']))
      foreach($menu['values'] as $value)
      {
        $result_array = array();
        $result_array['link'] = sub_url($value['menu_item_link']);
        $result_array['name'] = $value['menu_item_name'];
        $result_array['caption'] = $this->lang->line($prefix.$value['menu_item_name']);
        
        $sub_items = $this->model->get_sub_menu($this->user, $value['menu_item_name']);

        $result_array['class_if_current'] = '';
        
        if ($value['menu_item_link'] == substr ($this->uri->uri_string , 0 , strlen($value['menu_item_link']))) $result_array['class_if_current'] = $class_current;
        elseif (!empty($sub_items['values']))
        { 
          foreach($sub_items['values'] as $sub_item)
          { 
            if ($sub_item['menu_item_link'] == substr ($this->uri->uri_string , 0 , strlen($sub_item['menu_item_link']))) $result_array['class_if_current'] = $class_current;
          }
        }
        
        $view_data[$prefix.$value['menu_item_name']] = $result_array;
      }
  }
  
}