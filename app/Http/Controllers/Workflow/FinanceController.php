<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Finance;
use App\Http\Requests\Workflow\StoreFinanceRequest;
use App\Http\Requests\Workflow\UpdateFinanceRequest;
use App\Http\Resources\Workflow\FinanceResource;

class FinanceController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Finance::class;
        $this->storeRequestClass = StoreFinanceRequest::class;
        $this->updateRequestClass = UpdateFinanceRequest::class;
        $this->resourceClass = FinanceResource::class;
    }

}
