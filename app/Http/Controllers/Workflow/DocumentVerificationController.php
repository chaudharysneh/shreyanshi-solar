<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\DocumentVerification;
use App\Http\Requests\Workflow\StoreDocumentVerificationRequest;
use App\Http\Requests\Workflow\UpdateDocumentVerificationRequest;
use App\Http\Resources\Workflow\DocumentVerificationResource;

class DocumentVerificationController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = DocumentVerification::class;
        $this->storeRequestClass = StoreDocumentVerificationRequest::class;
        $this->updateRequestClass = UpdateDocumentVerificationRequest::class;
        $this->resourceClass = DocumentVerificationResource::class;
    }
}