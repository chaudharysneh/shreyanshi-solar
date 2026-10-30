<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\NetMetering;
use App\Http\Requests\Workflow\StoreNetMeteringRequest;
use App\Http\Requests\Workflow\UpdateNetMeteringRequest;
use App\Http\Resources\Workflow\NetMeteringResource;

class NetMeteringController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = NetMetering::class;
        $this->storeRequestClass = StoreNetMeteringRequest::class;
        $this->updateRequestClass = UpdateNetMeteringRequest::class;
        $this->resourceClass = NetMeteringResource::class;
    }
}