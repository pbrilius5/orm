<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\Entity\Wand;
use League\Fractal\TransformerAbstract;

class WandTransformer extends TransformerAbstract
{
    public function transform(Wand $wand): array
    {
        return [
            'id' => $wand->getId(),
            'name' => $wand->getName(),
            'role' => $wand->getRole()->getName(),
            'permissions' => json_decode($wand->getPermissions(), true),
            'created_at' => $wand->getCreatedAt()->format('c'),
            'expires_at' => $wand->getExpiresAt() ? $wand->getExpiresAt()->format('c') : null,
            'is_active' => !$wand->isExpired(),
        ];
    }
}
