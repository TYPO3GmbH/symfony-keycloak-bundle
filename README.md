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
    providers:
        keycloak:
            id: keycloak.typo3.com.user.provider
    firewalls:
        main:
            anonymous: true
            logout:
                path: /logout
                success_handler: keycloak.typo3.com.logout.handler
            guard:
                authenticators:
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
    app_url: 'localhost'
    keycloak:
        jku_url:                'https://login.typo3.com/auth/realms/TYPO3/protocol/openid-connect/certs'
        logout_url:             'https://login.typo3.com/auth/realms/TYPO3/protocol/openid-connect/logout'
        logout_redirect_route:  'home'
        user_provider_class:    T3G\Bundle\Keycloak\Security\KeyCloakUserProvider
        default_roles:
            # Defaults:
            - ROLE_USER
            - ROLE_OAUTH_USER
        role_mapping:
            # Default:
            typo3.gmbh.member:   ROLE_ADMIN
```
