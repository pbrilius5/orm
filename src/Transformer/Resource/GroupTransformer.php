<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\DTO\GroupApiDTO;
use League\Fractal\TransformerAbstract;

class GroupTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['users'];

    public function transform(GroupApiDTO $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'created_at' => $group->createdAt->format('c'),
        ];
    }

    public function includeUsers(GroupApiDTO $group)
    {
        return $this->collection($group->users, new UserTransformer());
    }
}
