<?php


namespace App\Http\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResources extends JsonResource
{
    public function toArray($request)
    {
        Carbon::setLocale('id');
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,
            'user' => $this->user->name,
            'timestamp' => Carbon::parse($this->created_at)->toDateTimeString()
        ];
    }
}