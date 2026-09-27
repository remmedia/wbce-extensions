<?php
interface WbceAuthFactorProviderInterface{public function getId();public function isRequired(array $user);public function renderChallenge(array $user,$error='');public function verify(array $user,array $input);}
