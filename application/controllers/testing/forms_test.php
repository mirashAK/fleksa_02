<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forms_Test extends Test_Controller
{
 
 public function index()
 {
    $form = Form_Builder::factory('auth_form', '/testing/forms_test');
    $reg_form = Form_Builder::factory('reg_form', '/testing/forms_test');
    
    $this->load->model('users_mdl');
    
    //$form->form_data = $this->users_mdl->get_user($this->user);
    
    var_export($form->validate()); echo('<br/>');
    var_export($reg_form->validate()); echo('<br/>');
    var_export($reg_form->value);
    
    $this->view_data['auth_form'] = $this->parser->parse_string($form->draw_form('layouts/testing/forms/auth_form'), $this->view_data, true);
    $this->view_data['reg_form'] = $this->parser->parse_string($reg_form->draw_form('layouts/testing/forms/reg_form'), $this->view_data, true);
     
    if (!$this->input->is_ajax_request()) $this->parse_out('layouts/testing/forms_test_view');
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
        else $this->view_data['pass_form'] = $this->parser->parse_string($form->draw_form('layouts/testing/forms/pass_email_form'), $this->view_data, true);
      }
      else
      {
        $this->view_data['pass_form'] = $this->parser->parse_string($form->draw_form('layouts/testing/forms/pass_email_form'), $this->view_data, true);
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
        $this->view_data['pass_form'] = $this->parser->parse_string($form->draw_form('layouts/testing/forms/reset_pass_form'), $this->view_data, true);
      }
    }
    
    $this->parse_out('layouts/testing/forms_test_pass_view');
  }
 
}