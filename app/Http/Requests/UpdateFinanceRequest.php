<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinanceRequest extends FormRequest
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
            'amount' => 'nullable|numeric',
            'payment_type' => 'nullable|string',
        ];
    }
}
