<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Tests\Unit\Service;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use T3G\Bundle\Keycloak\Security\KeyCloakAuthenticator;
use T3G\Bundle\Keycloak\Service\AccessTokenRefresher;

class AccessTokenRefresherTest extends TestCase
{
    private Session $session;
    private OAuth2Client&MockObject $client;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->client = $this->createMock(OAuth2Client::class);
    }

    public static function validTokenDataProvider(): \Iterator
    {
        yield 'expires after the leeway' => [16];
        yield 'expires much later' => [300];
    }

    #[DataProvider('validTokenDataProvider')]
    public function testValidTokenIsReturnedAsIs(int $expiresIn): void
    {
        $accessToken = $this->storeAccessToken($expiresIn);
        $this->client->expects(self::never())->method('refreshAccessToken');

        self::assertSame($accessToken, $this->createRefresher()->getAccessToken());
    }

    public static function expiringTokenDataProvider(): \Iterator
    {
        yield 'expires within the leeway' => [15];
        yield 'expires within a second' => [1];
        yield 'expired already' => [-10];
    }

    #[DataProvider('expiringTokenDataProvider')]
    public function testExpiringTokenIsRefreshed(int $expiresIn): void
    {
        $this->storeAccessToken($expiresIn);
        $refreshedToken = new AccessToken(['access_token' => 'refreshed', 'refresh_token' => 'refresh-2', 'expires_in' => 60]);
        $this->client->expects(self::once())->method('refreshAccessToken')->with('refresh-1')->willReturn($refreshedToken);

        self::assertSame($refreshedToken, $this->createRefresher()->getAccessToken());
        self::assertSame($refreshedToken, $this->session->get(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN));
    }

    public function testTokenWithoutExpiryIsNotRefreshed(): void
    {
        $accessToken = new AccessToken(['access_token' => 'token', 'refresh_token' => 'refresh-1']);
        $this->session->set(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN, $accessToken);
        $this->client->expects(self::never())->method('refreshAccessToken');

        self::assertSame($accessToken, $this->createRefresher()->getAccessToken());
    }

    public function testEndedKeycloakSessionInvalidatesSession(): void
    {
        $this->storeAccessToken(1);
        $this->session->set('foo', 'bar');
        $this->client->method('refreshAccessToken')->willThrowException(new IdentityProviderException('Token is not active', 400, '{"error":"invalid_grant","error_description":"Token is not active"}'));

        self::assertNull($this->createRefresher()->getAccessToken());
        self::assertFalse($this->session->has('foo'));
        self::assertFalse($this->session->has(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN));
    }

    public function testOtherRefreshErrorsAreNotSwallowed(): void
    {
        $this->storeAccessToken(1);
        $this->client->method('refreshAccessToken')->willThrowException(new IdentityProviderException('Server error', 500, ['error' => 'server_error']));

        $this->expectException(IdentityProviderException::class);

        $this->createRefresher()->getAccessToken();
    }

    public function testMissingTokenOrSessionYieldsNull(): void
    {
        $this->client->expects(self::never())->method('refreshAccessToken');

        self::assertNull($this->createRefresher()->getAccessToken());
        self::assertNull((new AccessTokenRefresher($this->createClientRegistry(), new RequestStack()))->getAccessToken());
    }

    private function storeAccessToken(int $expiresIn): AccessToken
    {
        $accessToken = new AccessToken(['access_token' => 'token', 'refresh_token' => 'refresh-1', 'expires' => time() + $expiresIn]);
        $this->session->set(KeyCloakAuthenticator::SESSION_KEYCLOAK_ACCESS_TOKEN, $accessToken);

        return $accessToken;
    }

    private function createRefresher(): AccessTokenRefresher
    {
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new AccessTokenRefresher($this->createClientRegistry(), $requestStack);
    }

    private function createClientRegistry(): ClientRegistry
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry->method('getClient')->with('keycloak')->willReturn($this->client);

        return $clientRegistry;
    }
}
