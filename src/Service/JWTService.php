<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Service;

use Jose\Bundle\JoseFramework\Services\JWSVerifier;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\JWSSerializerManagerFactory;
use Jose\Component\Signature\Signature;
use T3G\Bundle\Keycloak\Exception\NoTokenException;

class JWTService
{
    private JWSVerifier $verifier;

    private JWKSet $set;

    private JWSSerializerManager $serializerManager;

    private ?string $token;

    public function __construct(JWSSerializerManagerFactory $JWSSerializerManagerFactory, JWSVerifier $JWSVerifier, JWKSet $JWKSet)
    {
        $this->verifier = $JWSVerifier;
        $this->set = $JWKSet;
        $this->serializerManager = $JWSSerializerManagerFactory->create(['jws_compact']);
    }

    public function verify(string $token): bool
    {
        $this->token = $token;
        $jws = $this->serializerManager->unserialize($this->token);
        $result = $this->verifier->verifyWithKeySet($jws, $this->set, 0);
        if (!$result) {
            $this->token = null;
        }
        return $result;
    }

    public function getPayload(): string
    {
        $this->checkToken();
        return $this->serializerManager->unserialize($this->token)->getPayload();
    }

    public function getSignature(int $index = 0): Signature
    {
        $this->checkToken();
        return $this->serializerManager->unserialize($this->token)->getSignature($index);
    }

    /**
     * @return Signature[]
     */
    public function getSignatures(): array
    {
        $this->checkToken();
        return $this->serializerManager->unserialize($this->token)->getSignatures();
    }

    protected function checkToken(): void
    {
        if (null === $this->token) {
            throw new NoTokenException('no token set, please run JSTService->verify() first');
        }
    }
}
