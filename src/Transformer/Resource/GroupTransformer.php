<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\DTO\GroupApiDTO;
use League\Fractal\TransformerAbstract;

class GroupTransformer extends TransformerAbstract
{
    public function transform(GroupApiDTO $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'created_at' => $group->createdAt->format('c'),
        ];
    }
}
