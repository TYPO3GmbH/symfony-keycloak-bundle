# Installation

## Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require t3g/symfony-keycloak-bundle
```

## Step 2: Add the firewall to security.yaml

Update your security.yaml like this

```yaml
# config/packages/security.yaml
security:
    enable_authenticator_manager: true
    providers:
        keycloak:
            id: keycloak.typo3.com.user.provider
    firewalls:
        main:
            provider: keycloak
            logout:
                path: /logout
                target: home
            custom_authenticators:
                - T3G\Bundle\Keycloak\Security\KeyCloakAuthenticator
```

```yaml
# config/routes.yaml
logout:
  path: /logout
```

## Step 3: Enable the Bundle

Then, enable the bundle and its dependencies by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

**NOTE**: The order is *very* important here.

```php
// config/bundles.php

return [
    // ...
    Jose\Bundle\JoseFramework\JoseFrameworkBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Http\HttplugBundle\HttplugBundle::class => ['all' => true],
    T3G\Bundle\Keycloak\T3GKeycloakBundle::class => ['all' => true],
];
```

## Step 5: Create a login controller

In order to log in, a simple login controller will suffice:

```php
<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process.
     */
    #[Route(path: '/login', name: 'login', methods: ['GET'])]
    public function login(ClientRegistry $clientRegistry): RedirectResponse
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        return $clientRegistry
            ->getClient('keycloak')
            ->redirect([
                'openid', 'profile', 'roles', 'email', // the scopes you want to access
            ]);
    }

    /**
     * This route must match the authentication route in your bundle configuration.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/oauth/callback', name: 'oauth_callback', methods: ['GET'])]
    public function checkLogin(): RedirectResponse
    {
        // fallback in case the authenticator does not redirect
        return $this->redirectToRoute('dashboard');
    }
}
```

# Configuration

```bash
# displays the default config values
php bin/console config:dump-reference t3g_keycloak

# displays the actual config values used by your application
php bin/console debug:config t3g_keycloak 
```

## Default configuration

```yaml
# Default configuration for extension with alias: "t3g_keycloak"
t3g_keycloak:
    keycloak:
        jku_url: 'https://login.typo3.com/realms/TYPO3/protocol/openid-connect/certs'
        user_provider_class: T3G\Bundle\Keycloak\Security\KeyCloakUserProvider
        default_roles:
            # Defaults:
            - ROLE_USER
            - ROLE_OAUTH_USER
    routes:
        # redirect_route passed to keycloak
        authentication: oauth_callback
        # route to redirect to after successful authentication
        success: dashboard
```

### Role Mapping

```yaml
t3g_keycloak:
    keycloak:
        role_mapping:
            my-role: ROLE_ADMIN
```
