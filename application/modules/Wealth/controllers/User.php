<?php

class UserController extends BaseresumeController 
{
    protected $user_id = 0;

    public function init()
    {
        parent::init();
        $this->checkLogin();
        $this->user_id = $this->_user['id'];
    }

    public function listBaseUserAction()
    {

    }

    public function editBaseUserAction()
    {

    }

    public function createBaseUserAction()
    {

    }

    public function saveBaseUserAction()
    {

    }

}
