<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\WaitingFloor;
use App\Http\Requests\Workflow\StoreWaitingFloorRequest;
use App\Http\Requests\Workflow\UpdateWaitingFloorRequest;
use App\Http\Resources\Workflow\WaitingFloorResource;

class WaitingFloorController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = WaitingFloor::class;
        $this->storeRequestClass = StoreWaitingFloorRequest::class;
        $this->updateRequestClass = UpdateWaitingFloorRequest::class;
        $this->resourceClass = WaitingFloorResource::class;
    }
}