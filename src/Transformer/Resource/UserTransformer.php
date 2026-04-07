<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\DTO\UserApiDTO;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(UserApiDTO $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->createdAt->format('c'),
        ];
    }
}
