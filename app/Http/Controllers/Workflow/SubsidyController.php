<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Subsidy;
use App\Http\Requests\Workflow\StoreSubsidyRequest;
use App\Http\Requests\Workflow\UpdateSubsidyRequest;
use App\Http\Resources\Workflow\SubsidyResource;

class SubsidyController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Subsidy::class;
        $this->storeRequestClass = StoreSubsidyRequest::class;
        $this->updateRequestClass = UpdateSubsidyRequest::class;
        $this->resourceClass = SubsidyResource::class;
    }
}