<?php

/**
 * Ubilling administrative interface login form
 */
class LoginForm {

    protected $form = '';
    protected $loginPreset = '';
    protected $passwordPreset = '';
    protected $breaks=true;
    protected $container=true;
    protected $inputSize = 12;

    public function __construct($br=true,$container=true) {
        $this->loadForm($br,$container);
    }

    /**
     * Stores raw login form into private property
     * 
     * @param bool $br
     * @param bool $container
     * 
     * @return void
     */
    protected function loadForm($br,$container) {
        $this->breaks=$br;
        $this->container=$container;
        
        if (file_exists('DEMO_MODE')) {
            $this->loginPreset = 'admin';
            $this->passwordPreset = 'demo';
        }
        
        if ($this->container) {
            $this->form.=wf_tag('div', false, 'ubLoginContainer');
        }

        $inputs = wf_HiddenInput('login_form', '1');
        $inputs.= wf_TextInput('username', __('Login'), $this->loginPreset, $this->breaks, $this->inputSize);
        $inputs.= wf_PasswordInput('password', __('Password'), $this->passwordPreset, $this->breaks, $this->inputSize,false);
        $inputs.= wf_Submit(__('Log in'));
        $this->form.= wf_Form("", 'POST', $inputs, 'ubLoginForm');
        
        if ($this->container) {
            $this->form.=wf_tag('div',true);
        }
    }

    /**
     * Returns login form body
     * 
     * @return string
     */
    public function render() {
        return ($this->form);
    }

}

?>