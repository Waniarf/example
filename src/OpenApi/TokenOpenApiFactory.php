<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Webmozart\Assert\Assert as WebmozartAssert;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final class TokenOpenApiFactory implements CacheableSupportsMethodInterface, OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $this->addTokenEndpoints($openApi);

        return $openApi;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated instanceof CacheableSupportsMethodInterface
            && $this->decorated->hasCacheableSupportsMethod();
    }

    private function addTokenEndpoints(OpenApi $openApi): void
    {
        $this->addGetTokenEndpoint($openApi);
        $this->addRefreshTokenEndpoint($openApi);
    }

    private function addGetTokenEndpoint(OpenApi $openApi): void
    {
        $schemas = $openApi->getComponents()->getSchemas();
        WebmozartAssert::notNull($schemas);

        $pathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'getJwtToken',
                tags: ['Token'],
                responses: [
                    Response::HTTP_OK => [
                        'description' => 'Get JWT token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token-read_token',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get JWT token to login.',
                requestBody: new Model\RequestBody(
                    description: 'Generate new JWT Token',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Token-create_token',
                            ],
                        ],
                    ]),
                ),
            ),
        );

        $openApi->getPaths()->addPath('/api/token/get', $pathItem);
    }

    private function addRefreshTokenEndpoint(OpenApi $openApi): void
    {
        $schemas = $openApi->getComponents()->getSchemas();
        WebmozartAssert::notNull($schemas);

        $pathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'refreshJwtToken',
                tags: ['Token'],
                responses: [
                    Response::HTTP_OK => [
                        'description' => 'Get JWT token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token-read_token',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Refresh JWT token.',
                requestBody: new Model\RequestBody(
                    description: 'Generate new JWT Token',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Token-refresh_token',
                            ],
                        ],
                    ]),
                ),
            ),
        );

        $openApi->getPaths()->addPath('/api/token/refresh', $pathItem);
    }
}
