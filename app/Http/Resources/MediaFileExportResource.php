<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaFileExportResource extends JsonResource
{
    public function toArray($request)
    {
        // Pick one version as "original" (or first if not present)
        $originalVersion = $this->versions->first() ?? null;

        return [
            '_id' => (string) $this->id,
            'url' => $originalVersion?->url ?? $this->original_url,
            'key' => $this->name, // or use some other unique key if needed
            'size' => $originalVersion?->size ?? 0,
            'mimetype' => $originalVersion?->type ?? null,
            'title' => $this->name,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
