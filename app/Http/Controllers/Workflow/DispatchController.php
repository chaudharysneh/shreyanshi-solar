<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Dispatch;
use App\Http\Requests\Workflow\StoreDispatchRequest;
use App\Http\Requests\Workflow\UpdateDispatchRequest;
use App\Http\Resources\Workflow\DispatchResource;

class DispatchController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Dispatch::class;
        $this->storeRequestClass = StoreDispatchRequest::class;
        $this->updateRequestClass = UpdateDispatchRequest::class;
        $this->resourceClass = DispatchResource::class;
    }
}