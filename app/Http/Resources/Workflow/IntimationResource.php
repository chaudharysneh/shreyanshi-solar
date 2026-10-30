<?php

namespace App\Http\Resources\Workflow;

use Illuminate\Http\Resources\Json\JsonResource;

class IntimationResource extends JsonResource
{
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if ($this->relationLoaded('customer') && $this->customer) {
            $data['customer'] = [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'city_name' => $this->customer->city_name ?: $this->customer->city?->name,
                'system_capacity' => $this->customer->system_capacity,
                'panel_make' => $this->customer->panel_make,
                'no_of_panels' => $this->customer->no_of_panels,
                'inverter_make' => $this->customer->inverter_make,
                'inverter_kw' => $this->customer->inverter_kw,
                'total_cost' => $this->customer->total_cost,
                'finance_id' => $this->customer->finance_id,
                'address' => $this->customer->address,
                'discom' => $this->customer->discom,
                'circle' => $this->customer->circle,
                'division' => $this->customer->division,
                'sub_division' => $this->customer->sub_division,
            ];
        }
        if ($this->relationLoaded('assignedTo') && $this->assignedTo) {
            $data['assigned_user'] = [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ];
        }
        return $data;
    }
}
