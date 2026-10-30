<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Registration;
use App\Http\Requests\Workflow\StoreRegistrationRequest;
use App\Http\Requests\Workflow\UpdateRegistrationRequest;
use App\Http\Resources\Workflow\RegistrationResource;

class RegistrationController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Registration::class;
        $this->storeRequestClass = StoreRegistrationRequest::class;
        $this->updateRequestClass = UpdateRegistrationRequest::class;
        $this->resourceClass = RegistrationResource::class;
    }
}