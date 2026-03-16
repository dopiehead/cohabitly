<?php

namespace App\Service;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class MercureJwtGenerator
{
    private Configuration $config;

    public function __construct(string $mercureKey)
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($mercureKey)
        );
    }

    public function generate(array $subscribe = [], array $publish = []): string
    {
        $token = $this->config->builder()
            ->issuedAt(new \DateTimeImmutable())
            ->withClaim('mercure', [
                'subscribe' => $subscribe,
                'publish'   => $publish,
            ])
            ->getToken($this->config->signer(), $this->config->signingKey());

        return $token->toString();
    }
}