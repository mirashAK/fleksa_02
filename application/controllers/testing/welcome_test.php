<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome_Test extends Test_Controller {

	public function index()
	{
      $this->view_data['lang'] = $this->uri->lang;
      $this->view_data['uri_lang'] = $this->config->item('language');
      $this->lang->load('site/test', $this->config->item('language'));
      $this->view_data['test_email_missing'] = $this->lang->line('test_email_missing'); 
      $this->view_data['test_arr'] = array ( 'social_description' => 'Работа с задачами в вашей команде никогда не была такой простой', 'reg_description' => 'Работа с задачами в вашей команде никогда не была такой простой', 'reg_title' => 'Работа с задачами в вашей команде никогда не была такой простой', 'reg_step1_input_text' => 'Введите свой e-mail', 'reg_step1_button_text' => 'Будьте первым', 'reg_step2_text' =>'Подтвердите свой e-mail, пройдя по ссылке в письме на', );
      $this->parse_out('layouts/testing/welcome_test_view');
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */