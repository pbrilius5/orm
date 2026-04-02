<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\Entity\UserRole;
use League\Fractal\TransformerAbstract;

class UserRoleTransformer extends TransformerAbstract
{
    public function transform(UserRole $userRole): array
    {
        return [
            'id' => $userRole->getId(),
            'role' => $userRole->getRole()->getName(),
            'team' => $userRole->getTeam()->getName(),
            'granted_at' => $userRole->getGrantedAt()->format('c'),
            'expires_at' => $userRole->getExpiresAt() ? $userRole->getExpiresAt()->format('c') : null,
            'is_active' => $userRole->isActive(),
        ];
    }
}
