<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Schema\Schema;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Webmozart\Assert\Assert as WebmozartAssert;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final class CustomSchemasOpenApiFactory implements CacheableSupportsMethodInterface, OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        return $this->addCustomSchemas($openApi);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated instanceof CacheableSupportsMethodInterface
            && $this->decorated->hasCacheableSupportsMethod();
    }

    private function addCustomSchemas(OpenApi $openApi): OpenApi
    {
        $schemas = $openApi->getComponents()->getSchemas();
        WebmozartAssert::notNull($schemas);

        $schemas = new ArrayObject(array_merge((array) $schemas, Schema::allSchemas()));

        return $openApi->withComponents($openApi->getComponents()->withSchemas($schemas));
    }
}
