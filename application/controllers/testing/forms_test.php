<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forms_Test extends Test_Controller
{
 
 public function index()
 {
    $this->load->model('users_mdl');

    $data = $this->users_mdl->get_user_signature($this->user);

    
    $this->view_data['type'] = var_export($data['type'], true);
    $this->view_data['caption'] = var_export($data['caption'], true);
    $this->view_data['r_only'] = var_export($data['r_only'], true);
    $this->view_data['value'] = (array_key_exists('value', $data))?var_export($data['value'], true):'';
 
    $this->parse_out('layouts/testing/forms_test_view');
 }
 
 
 
 
  public function user()
  {
    $this->load->model('users_mdl');
    $form = Form_Builder::factory('public_user');
    
    $form->form_data = $this->users_mdl->get_user($this->user);
    
    //var_export($this->users_mdl->get_user($this->user));
    
    if ($form->validate() == true)
    {
      if ($form->is_new) $form->u_f_user_id = $this->user->user_id;
      $this->users_mdl->save_table($this->user, 'public_users', $form, 'u_f_user_id='.$form->u_f_user_id);
    }
    else
    {
      var_export($form->errors);
    }
    
    $this->view_data['user_form'] = $form->draw_form('layouts/testing/forms/user_form', $this->view_data);
    
    $this->parse_out('layouts/testing/user_test_view');
  }
  
  
  
  
  
  public function reset_pass()
  {
    if ($this->input->get('token') == false && $this->user_session->pass_reset_token == false)
    {
      $form = Form_Builder::factory('change_pass_form_email', '/testing/forms_test/reset_pass');
      if ($form->validate() === true)
      {
        $reset_link = $this->user->token_passwd ($form->user_email);
        if (!empty($reset_link))
        {
          $reset_link = $this->config->item('language').'/testing/forms_test/reset_pass'.$reset_link;
          $this->user_session->pass_reset_token = true;
          $this->view_data['pass_form'] = '<a href="'.sub_url().$reset_link.'" id="reset_pass_link">'.sub_url().$reset_link.'</a>';
        }
        else $this->view_data['pass_form'] = $this->parse_form($form->draw_form('layouts/testing/forms/pass_email_form'));
      }
      else
      {
        $this->view_data['pass_form'] = $this->parse_form($form->draw_form('layouts/testing/forms/pass_email_form'));
      }
    }
    else
    {
      if ($this->input->get('token')!==false) $this->user_session->pass_reset_token = $this->input->get('token');
      $form = Form_Builder::factory('change_pass_form', '/testing/forms_test/reset_pass');
      if ($form->validate() === true)
      {
        if ($this->user->reset_passwd($this->user_session->pass_reset_token, $form->user_pass) === true)
        {
          $this->user_session->pass_reset_token = '';
          redirect(sub_url($this->config->item('language').'/testing/forms_test/reset_pass'), 'refresh');
        }
      }
      else
      {
        $this->view_data['pass_form'] = $this->parse_form($form->draw_form('layouts/testing/forms/reset_pass_form'));
      }
    }
    
    $this->parse_out('layouts/testing/forms_test_pass_view');
  }
 
}