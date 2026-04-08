<?php

declare(strict_types=1);

namespace App\Responder;

use Laminas\Diactoros\Response\JsonResponse;

class JsonHalResponder
{
    public static function resource(
        string $type,
        string $id,
        mixed $attributes,
        array $links = [],
        array $embedded = []
    ): JsonResponse {
        $attributesArray = is_object($attributes) ? get_object_vars($attributes) : $attributes;
        $data = [
            '_links' => [
                'self' => ['href' => "/{$type}/{$id}"],
            ],
            $type => array_merge(['id' => $id], $attributesArray),
        ];

        foreach ($links as $rel => $href) {
            $data['_links'][$rel] = is_array($href) ? $href : ['href' => $href];
        }

        if (!empty($embedded)) {
            $data['_embedded'] = $embedded;
        }

        return new JsonResponse($data, 200, [
            'Content-Type' => 'application/hal+json',
        ]);
    }

    public static function collection(
        string $type,
        array $items,
        array $meta = [],
        array $links = []
    ): JsonResponse {
        $data = [
            '_links' => [
                'self' => ['href' => "/{$type}"],
            ],
            '_embedded' => [
                $type => $items,
            ],
        ];

        if (!empty($meta)) {
            $data['_meta'] = $meta;
        }

        foreach ($links as $rel => $href) {
            $data['_links'][$rel] = is_array($href) ? $href : ['href' => $href];
        }

        return new JsonResponse($data, 200, [
            'Content-Type' => 'application/hal+json',
        ]);
    }

    public static function created(
        string $type,
        string $id,
        mixed $attributes,
        array $links = [],
        array $embedded = []
    ): JsonResponse {
        $response = self::resource($type, $id, $attributes, $links, $embedded);
        return $response->withStatus(201);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    public static function error(
        string $title,
        int $status,
        string $detail = '',
        array $extra = []
    ): JsonResponse {
        $data = [
            '_error' => [
                'status' => $status,
                'title' => $title,
            ],
        ];

        if ($detail) {
            $data['_error']['detail'] = $detail;
        }

        $data['_error'] = array_merge($data['_error'], $extra);

        return new JsonResponse($data, $status, [
            'Content-Type' => 'application/hal+json',
        ]);
    }

    public static function badRequest(string $detail = ''): JsonResponse
    {
        return self::error('Bad Request', 400, $detail);
    }

    public static function notFound(string $detail = ''): JsonResponse
    {
        return self::error('Not Found', 404, $detail);
    }

    public static function unprocessableEntity(array $errors): JsonResponse
    {
        return self::error('Unprocessable Entity', 422, 'Validation failed', [
            'errors' => $errors,
        ]);
    }

    public static function unauthorized(string $detail = 'Unauthorized'): JsonResponse
    {
        return self::error('Unauthorized', 401, $detail);
    }

    public static function forbidden(string $detail = 'Forbidden'): JsonResponse
    {
        return self::error('Forbidden', 403, $detail);
    }

    public static function problem(
        string $type,
        string $title,
        int $status,
        string $detail = '',
        ?string $instance = null,
        array $extensions = []
    ): JsonResponse {
        $problem = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
        ];

        if ($detail) {
            $problem['detail'] = $detail;
        }

        if ($instance) {
            $problem['instance'] = $instance;
        }

        $problem = array_merge($problem, $extensions);

        return new JsonResponse($problem, $status, [
            'Content-Type' => 'application/problem+json; charset=utf-8',
        ]);
    }
}
