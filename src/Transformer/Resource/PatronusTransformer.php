<?php

declare(strict_types=1);

namespace App\Transformer\Resource;

use App\Entity\Patronus;
use League\Fractal\TransformerAbstract;

class PatronusTransformer extends TransformerAbstract
{
    public function transform(Patronus $patronus): array
    {
        return [
            'id' => $patronus->getId(),
            'token' => $patronus->getToken(),
            'role' => $patronus->getRole()->getName(),
            'team' => $patronus->getTeam()->getName(),
            'issued_at' => $patronus->getIssuedAt()->format('c'),
            'expires_at' => $patronus->getExpiresAt()->format('c'),
            'is_valid' => $patronus->isValid(),
        ];
    }
}
