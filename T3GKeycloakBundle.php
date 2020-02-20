<?php


namespace T3G\Bundle\Keycloak;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use T3G\Bundle\Keycloak\DependencyInjection\T3GKeycloakExtension;

final class T3GKeycloakBundle extends Bundle
{
    /**
     * Overridden to allow for the custom extension alias.
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new T3GKeycloakExtension();
        }
        return $this->extension;
    }
}
