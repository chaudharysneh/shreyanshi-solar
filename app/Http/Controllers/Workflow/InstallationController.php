<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Installation;
use App\Http\Requests\Workflow\StoreInstallationRequest;
use App\Http\Requests\Workflow\UpdateInstallationRequest;
use App\Http\Resources\Workflow\InstallationResource;

class InstallationController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Installation::class;
        $this->storeRequestClass = StoreInstallationRequest::class;
        $this->updateRequestClass = UpdateInstallationRequest::class;
        $this->resourceClass = InstallationResource::class;
    }

}
