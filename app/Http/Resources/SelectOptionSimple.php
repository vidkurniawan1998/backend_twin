<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class SelectOptionSimple extends JsonResource
{
    public function toArray($request)
    {
        return [
            'code'  => $this->code,
            'value' => $this->value,
            'text'  => $this->text
        ];
    }
}
