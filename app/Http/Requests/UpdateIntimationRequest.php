<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntimationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['sometimes', 'string', 'regex:/^(pending|in_progress|completed|stage_\d+)$/'],
            'assigned_to' => 'nullable|exists:users,id',
            'remarks' => 'nullable|string',
            'self_certificate_status' => 'sometimes|string',
            'self_certificate_file' => 'nullable|string',
            'gps_photo_status' => 'sometimes|string',
            'gps_photo_file' => 'nullable|string',
            'dcr_certificate_status' => 'sometimes|string',
            'dcr_certificate_file' => 'nullable|string',
            'model_agreement_status' => 'sometimes|string',
            'model_agreement_file' => 'nullable|string',
        ];
    }
}
