<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['flx_auth_form'] = array (
          'caption'=>array('user_email'=>'email', 'user_pass'=>'password'),
          'type'    =>array('user_email'=>'email', 'user_pass'=>'pass'),
          'value'   =>array('user_email'=>'', 'user_pass'=>''),
        );
        
$config['flx_reg_form'] = array (
          'caption'=>array('user_email'=>'email', 'user_pass'=>'passwd', 'user_re_pass'=>'re_passwd'),
          'type'    =>array('user_email'=>'email', 'user_pass'=>'pass', 'user_re_pass'=>'re_pass'),
          'value'   =>array('user_email'=>'', 'user_pass'=>'', 'user_re_pass'=>''),
        );

$config['flx_ch_pass_form_email'] = array (
          'caption'=>array('user_email'=>'email'),
          'type'    =>array('user_email'=>'email'),
          'value'   =>array('user_email'=>''),
        );
        
$config['flx_ch_pass_form'] = array (
          'caption'=>array('user_pass'=>'passwd', 'user_re_pass'=>'password'),
          'type'    =>array('user_pass'=>'pass', 'user_re_pass'=>'re_pass'),
          'value'   =>array('user_pass'=>'', 'user_re_pass'=>''),
        );