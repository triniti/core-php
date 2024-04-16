<?php
declare(strict_types=1);

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
