Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 1: Make sure the config is present

The configuration should be present *before* installing the bundle.

```yaml
# config/packages/t3g_keycloak.yaml
t3g_keycloak:
  keycloak:
    # The name of the route to redirect back to from Keycloak
    redirect_route: home
    # Set both of these in the .env.local
    oauth:
      client_id: '%env(OAUTH_CLIENT_ID)%'
      client_secret: '%env(OAUTH_CLIENT_SECRET)%'
    # Key/value array of ldap role against the role it will represent in your app
    role_mapping:
      typo3.gmbh.member: ROLE_ADMIN
    # The default roles every user gets
    default_roles: ['ROLE_USER', 'ROLE_OAUTH_USER']
```

### Step 2: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require t3g/symfony-keycloak-bundle
```

### Step 3: Add the firewall to security.yaml

Update your security.yaml like this

```yaml
# config/packages/security.yaml
security:
    providers:
        keycloak:
            id: keycloak.typo3.com.user.provider
    firewalls:
        main:
            anonymous: true
            logout:
                path: /logout
                target: home
            guard:
                authenticators:
                    - T3G\Bundle\Keycloak\Security\KeyCloakAuthenticator
```

```yaml
# config/routes.yaml
logout:
  path: /logout
```

### Step 4: Enable the Bundle

Then, enable the bundle and its dependencies by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

**NOTE**: The order is *very* important here.

```php
// config/bundles.php

return [
    // ...
    T3G\Bundle\Keycloak\T3GKeycloakBundle::class => ['all' => true],
    Jose\Bundle\JoseFramework\JoseFrameworkBundle::class => ['all' => true],
    KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle::class => ['all' => true],
    Http\HttplugBundle\HttplugBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
];
```

### Step 5: Create a login controller

In order to log in, a simple login controller will suffice:

```php
<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process.
     *
     * @Route("/login", name="login")
     * @param ClientRegistry $clientRegistry
     * @return RedirectResponse
     */
    public function login(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('keycloak')
            ->redirect([
                'profile roles email', // the scopes you want to access
            ], []);
    }
}
```
