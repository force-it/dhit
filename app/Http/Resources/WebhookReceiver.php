<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WebhookReceiver extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'chat' => $this->chat,
            'token' => $this->token,
            'uri' => config('receiver.host') . '/api/webhookReceiver/' . $this->token,
            'malfunction' => $this->malfunction,
            'bot' => $this->bot,
            'dql' => json_encode($this->dql, JSON_PRETTY_PRINT),
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
