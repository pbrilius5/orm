<?php

declare(strict_types=1);

namespace App\Responder;

use Oryx\Adr\Responder\JsonApiResponder;
use Oryx\Adr\Responder\ProblemDetailsResponder;
use Psr\Http\Message\ResponseInterface;

class JsonHalResponder
{
    public static function resource(
        string $type,
        string $id,
        mixed $attributes,
        array $links = [],
        array $embedded = []
    ): ResponseInterface {
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

        return self::respondHal($data, 200);
    }

    public static function collection(
        string $type,
        array $items,
        array $meta = [],
        array $links = []
    ): ResponseInterface {
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

        return self::respondHal($data, 200);
    }

    public static function created(
        string $type,
        string $id,
        mixed $attributes,
        array $links = [],
        array $embedded = []
    ): ResponseInterface {
        $response = self::resource($type, $id, $attributes, $links, $embedded);
        return $response->withStatus(201);
    }

    public static function noContent(): ResponseInterface
    {
        return (new JsonApiResponder(null, 204))->respond();
    }

    public static function error(
        string $title,
        int $status,
        string $detail = '',
        array $extra = []
    ): ResponseInterface {
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

        return self::respondHal($data, $status);
    }

    public static function badRequest(string $detail = ''): ResponseInterface
    {
        return self::error('Bad Request', 400, $detail);
    }

    public static function notFound(string $detail = ''): ResponseInterface
    {
        return self::error('Not Found', 404, $detail);
    }

    public static function unprocessableEntity(array $errors): ResponseInterface
    {
        return self::error('Unprocessable Entity', 422, 'Validation failed', [
            'errors' => $errors,
        ]);
    }

    public static function unauthorized(string $detail = 'Unauthorized'): ResponseInterface
    {
        return self::error('Unauthorized', 401, $detail);
    }

    public static function forbidden(string $detail = 'Forbidden'): ResponseInterface
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
    ): ResponseInterface {
        return (new ProblemDetailsResponder(
            $type,
            $title,
            $status,
            $detail !== '' ? $detail : null,
            $instance,
            $extensions
        ))->respond();
    }

    private static function respondHal(array $data, int $status): ResponseInterface
    {
        return (new JsonApiResponder($data, $status, [
            'Content-Type' => 'application/hal+json; charset=utf-8',
        ]))->respond();
    }
}
