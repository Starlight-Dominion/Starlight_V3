<?php

namespace sdo\Controllers;

class ClanController extends BaseController
{
    public function home(): string
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        return $this->render('clan/home', ['title' => 'Clan Home']);
    }

    public function bank(): string
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        return $this->render('clan/bank', ['title' => 'Clan Bank']);
    }
}
