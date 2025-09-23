<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'deadline' => $this->deadline,
            'assigned_to' => $this->assigned_to,
            'assigned_user' => [
                'id' => $this->assignedUser?->id,
                'name' => $this->assignedUser?->name,
                'email' => $this->assignedUser?->email,
            ],
            'arts' => $this->arts->map(function ($art) {
                return [
                    'id' => $art->id,
                    'title' => $art->title,
                    'art_path' => $art->art_path,
                    'status' => $art->status,
                    'created_at' => $art->created_at,
                    'updated_at' => $art->updated_at,
                ];
            }),
        ];
    }
}
