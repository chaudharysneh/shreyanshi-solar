<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'aspl_id' => 'required|string|max:255',
            'status' => 'required|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|exists:users,id',
            'remarks' => 'nullable|string',
            'previous_stage' => 'nullable|string',
        ];
    }
}
