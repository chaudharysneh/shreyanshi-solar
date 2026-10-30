<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNetMeteringRequest extends FormRequest
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
            'meter_number' => 'nullable|string',
            'discom_status' => 'nullable|string',
        ];
    }
}
