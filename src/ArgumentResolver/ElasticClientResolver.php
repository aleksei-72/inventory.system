<?php

namespace App\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;

class ElasticClientResolver implements ArgumentValueResolverInterface
{

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return Client::class === $argument->getType();
    }

    public function resolve(?Request $request, ?ArgumentMetadata $argument): \Generator
    {
        $client = ClientBuilder::create()
            ->setHosts(json_decode($_ENV['ELASTIC_HOSTS'], true))
            ->build();

        yield $client;
    }
}