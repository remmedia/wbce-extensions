<?php

namespace TrustComponent\TrustCaptcha;

class VerificationToken {
    public $verificationId;
    public $clientFailover;

    public function __construct(string $verificationId, bool $clientFailover = false) {
        $this->verificationId = $verificationId;
        $this->clientFailover = $clientFailover;
    }
}
