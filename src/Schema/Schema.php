<?php

declare(strict_types=1);

namespace App\Schema;

use function array_diff;
use function count;
use function in_array;
use function is_array;
use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\scandir;
use function str_replace;

class Schema
{
    private const OFFSET = 5;
    private const DEPTH = 15;
    private const MAX_TYPES = 2;
    private const EXTENSION = '.json';

    private function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function allSchemas(): array
    {
        $schemas = [];

        /** @var string $directory */
        foreach (array_diff(scandir(__DIR__), ['.', '..', 'Schema.php']) as $directory) {
            /** @var string $fileName */
            foreach (array_diff(scandir(__DIR__ . '/' . $directory), ['.', '..']) as $fileName) {
                $fileName = str_replace(self::EXTENSION, '', $fileName);
                $schemas[self::prepareSchemaName($directory, $fileName)] = self::schema($directory, $fileName);
            }
        }

        self::prepareSchemasForOpenApi($schemas);

        return $schemas;
    }

    /**
     * @return array<string, mixed>
     */
    public static function schema(string $directory, string $name): array
    {
        /** @var array<string, mixed> $result */
        $result = json_decode(self::merge(file_get_contents(sprintf('%s/%s/%s%s', __DIR__, $directory, $name, self::EXTENSION))), true);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function listSchema(string $directory, string $name, int $itemsCount): array
    {
        return [
            'type' => 'array',
            'items' => self::schema($directory, $name),
            'minItems' => $itemsCount,
            'maxItems' => $itemsCount,
        ];
    }

    private static function merge(string $jsonSchema): string
    {
        $refRegex = '/{.?"\$ref":?.+"#\/.+"?}/Um';
        $fileRegex = '/#\/(?<schema>(.+))"/m';

        $depth = self::DEPTH;

        while (($offset = mb_strpos($jsonSchema, '$ref')) !== false) {
            preg_match_all($refRegex, $jsonSchema, $matches, PREG_SET_ORDER, $offset - self::OFFSET);

            if ($matches !== null && count($matches) > 0) {
                /** @var list<string> $match */
                foreach ($matches as $match) {
                    /** @var string $refString */
                    $refString = reset($match);
                    preg_match($fileRegex, $refString, $matchSchema);

                    $fileName = $matchSchema['schema'] ?? '{}';

                    $mergeJson = file_get_contents(__DIR__ . '/' . $fileName . self::EXTENSION);
                    $jsonSchema = str_replace($refString, $mergeJson, $jsonSchema);
                }
            }

            --$depth;

            if ($depth <= 0) {
                break;
            }
        }

        return $jsonSchema;
    }

    /**
     * OpenAPI spec for some reason does not support `{type: [string, null]}` notation. Because of that,
     * we need to replace it with `{type: string, nullable: true}`
     *
     * Also, it replaces `{ $ref: '#' }` with link to a schema in OpenAPI `components.schemas` as
     * OpenAPI does not work with recursive `{ $ref: '#' }`
     *
     * @param array<string, mixed> $schemas
     *
     * @return array<string, mixed>
     */
    private static function prepareSchemasForOpenApi(array &$schemas, ?string $schemaName = null): array
    {
        foreach ($schemas as $key => $value) {
            $rootSchemaName = $schemaName ?? $key;

            if ($key === 'type' && is_array($value) && count($value) === self::MAX_TYPES && in_array('null', $value, true)) {
                $schemas[$key] = array_diff($value, ['null'])[0];
                $schemas['nullable'] = true;
            } elseif (is_array($value)) {
                $schemas[$key] = self::prepareSchemasForOpenApi($value, $rootSchemaName);
            }

            if ($key === 'items' && $value === ['$ref' => '#']) {
                $schemas[$key] = ['$ref' => sprintf('#/components/schemas/%s', $rootSchemaName)];
            }
        }

        return $schemas;
    }

    private static function prepareSchemaName(string $folder, string $fileName): string
    {
        return sprintf('%s-%s', $folder, $fileName);
    }
}
