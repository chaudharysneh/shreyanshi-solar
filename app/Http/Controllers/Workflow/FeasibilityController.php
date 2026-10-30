<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Feasibility;
use App\Http\Requests\Workflow\StoreFeasibilityRequest;
use App\Http\Requests\Workflow\UpdateFeasibilityRequest;
use App\Http\Resources\Workflow\FeasibilityResource;

class FeasibilityController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Feasibility::class;
        $this->storeRequestClass = StoreFeasibilityRequest::class;
        $this->updateRequestClass = UpdateFeasibilityRequest::class;
        $this->resourceClass = FeasibilityResource::class;
    }
}