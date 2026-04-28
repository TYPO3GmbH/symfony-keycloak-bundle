<?php declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\XmlFileLoader;

return function (RoutingConfigurator $routes): void {
    foreach (debug_backtrace() as $trace) {
        if (isset($trace['object']) && $trace['object'] instanceof XmlFileLoader && 'doImport' === $trace['function']) {
            if (__DIR__ === dirname(realpath($trace['args'][3]))) {
                trigger_deprecation('t3g/symfony-keycloak-bundle', '4.0', 'The "routes.xml" routing configuration file is deprecated, import "routes.php" instead.');

                break;
            }
        }
    }

    $routes->add('t3g_keycloak_login', '/login')
        ->controller('keycloak.typo3.com.login_controller::login')
    ;
    $routes->add('t3g_keycloak_oauthCallback', '/oauth/callback}')
        ->controller('keycloak.typo3.com.login_controller::oauthCallback')
    ;
    $routes->add('t3g_keycloak_logout', '/oauth/logout}')
        ->controller('keycloak.typo3.com.login_controller::oauthLogout')
    ;
};
