<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class SelectOption extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'    => $this->id,
            'code'  => $this->code,
            'value' => $this->value,
            'text'  => $this->text
        ];
    }
}
