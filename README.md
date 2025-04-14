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

login:
    alias: t3g_keycloak_login
```

## Step 3: Enable the Bundle

Then, enable the bundle and its dependencies by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

**NOTE**: The order is *very* important here.

```php
// config/bundles.php

return [
    // ...
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle::class => ['all' => true],
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
    keycloak:
        user_provider_class: T3G\Bundle\Keycloak\Security\KeyCloakUserProvider
        default_roles:
            # Defaults:
            - ROLE_USER
            - ROLE_OAUTH_USER
        clientId: '%env(KEYCLOAK_CLIENT_ID)%'
```

### Role Mapping

```yaml
t3g_keycloak:
    keycloak:
        role_mapping:
            my-role: ROLE_ADMIN
```

### Routes
```yaml
t3g_keycloak:
    routes:
        # route to redirect to after successful authentication
        success: home
        # redirect_route passed to keycloak
        authentication: t3g_keycloak_oauthCallback
        # route of the Symfony logout handling
        logout_route: logout
```
