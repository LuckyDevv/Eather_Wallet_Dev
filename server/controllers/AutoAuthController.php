<?php

namespace controllers;

use db_managers\SessionDB;

class AutoAuthController extends Controller
{
    private array $params = [];
    public function __construct(array $params) {
        $this->params = $params;
    }

    public function handle(): ?int
    {
        if (isset($this->params['session_id'], $this->params['_usi_g%$_id$$'])) {
            $session_valid = (new SessionDB())->validateSession($this->params['session_id'], $this->params['_usi_g%$_id$$']);
            if ($session_valid > 0) {
                return $session_valid;
            }
        }
        return null;
    }
}