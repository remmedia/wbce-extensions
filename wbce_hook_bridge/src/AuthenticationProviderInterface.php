<?php
/** Compatibility contract for modular primary login providers on WBCE 1.6.8. */
if (!interface_exists('WbceAuthenticationProviderInterface')) {
    interface WbceAuthenticationProviderInterface { public function getId(); public function getName(); public function authenticate(array $user, array $credentials); }
}
