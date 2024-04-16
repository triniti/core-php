<?php
declare(strict_types=1);

use Google\Service\SearchConsole\InspectUrlIndexResponse;


class IndexSeoStatusForGoogle {
    public $success;
    public $response;
    public $error;

    public function __construct($success = true, InspectUrlIndexResponse $response, $error) {
        $this->success = $success;
        $this->response = $response;
        $this->error = $error;
    }
}
