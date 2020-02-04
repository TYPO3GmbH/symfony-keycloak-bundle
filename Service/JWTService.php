<?php

namespace T3G\Bundle\Keycload\Service;

use App\Exception\NoTokenException;
use Jose\Bundle\JoseFramework\Services\JWSVerifier;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\JWSSerializerManagerFactory;
use Jose\Component\Signature\Signature;
use League\OAuth2\Client\Token\AccessToken;

class JWTService
{
    /**
     * @var JWSVerifier
     */
    private $verifier;

    /**
     * @var JWKSet
     */
    private $set;

    /**
     * @var JWSSerializerManager
     */
    private $serializerManager;

    /**
     * @var string
     */
    private $token;

    public function __construct(JWSSerializerManagerFactory $JWSSerializerManagerFactory, JWSVerifier $JWSVerifier, JWKSet $JWKSet)
    {
        $this->verifier = $JWSVerifier;
        $this->set = $JWKSet;
        $this->serializerManager = $JWSSerializerManagerFactory->create(['jws_compact']);
    }

    public function verify(AccessToken $token): bool
    {
        $this->token = $token->getToken();
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
        if ($this->token === null) {
            throw new NoTokenException('no token set, please run JSTService->verify() first');
        }
    }
}
