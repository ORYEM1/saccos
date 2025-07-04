<?php

namespace App\Controllers;

class Save extends RestrictedBaseController
{
    public function index()
    {
        $vars['title'] = 'Home';
        return view('page',$vars);
    }
}
