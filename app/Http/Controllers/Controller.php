<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use stdClass;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    /**
     * set Response status
     *
     */
    protected $errorStatus   = 500;
    protected $successStatus = 200;
    protected $validationStatus = 400;
    protected $unauthStatus  = 401;
    protected $notFoundStatus  = 404;
    protected $invalidPermission = 403;

    protected $response;

    public function __construct()
    {
        //
        $this->response  = new stdClass();
    }
}
