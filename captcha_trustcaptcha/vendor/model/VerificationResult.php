<?php

namespace TrustComponent\TrustCaptcha;

class VerificationResult {
    public $captchaId;
    public $verificationId;
    public $verificationPassed;
    public $score;
    public $decisionType;
    public $decisionAction;
    public $gatewayFailoverActive;
    public $riskScoringEnabled;
    public $minimalDataModeEnabled;
    public $origin;
    public $ipAddress;
    public $countryCode;
    public $deviceFamily;
    public $operatingSystem;
    public $browser;
    public $verificationStartedAt;
    public $verificationFinishedAt;
    public $resultExpiresAt;
    public $resultFirstFetchedAt;
    public $resultLastFetchedAt;

    public function __construct($jsonData) {

        // Modified for WBCE: malformed upstream responses must not produce PHP warnings.
        $data = is_array($jsonData) ? $jsonData : json_decode((string)$jsonData, true);
        if (!is_array($data)) {
            $data = [];
        }

        $this->captchaId = $data['captchaId'] ?? '';
        $this->verificationId = $data['verificationId'] ?? '';
        $this->verificationPassed = $data['verificationPassed'] ?? false;
        $this->score = $data['score'] ?? 0.0;
        $this->decisionType = $data['decisionType'] ?? '';
        $this->decisionAction = $data['decisionAction'] ?? '';
        $this->gatewayFailoverActive = $data['gatewayFailoverActive'] ?? false;
        $this->riskScoringEnabled = $data['riskScoringEnabled'] ?? false;
        $this->minimalDataModeEnabled = $data['minimalDataModeEnabled'] ?? false;
        $this->origin = $data['origin'] ?? '';
        $this->ipAddress = $data['ipAddress'] ?? '';
        $this->countryCode = $data['countryCode'] ?? '';
        $this->deviceFamily = $data['deviceFamily'] ?? '';
        $this->operatingSystem = $data['operatingSystem'] ?? '';
        $this->browser = $data['browser'] ?? '';
        $this->verificationStartedAt = $data['verificationStartedAt'] ?? '';
        $this->verificationFinishedAt = $data['verificationFinishedAt'] ?? '';
        $this->resultExpiresAt = $data['resultExpiresAt'] ?? '';
        $this->resultFirstFetchedAt = $data['resultFirstFetchedAt'] ?? '';
        $this->resultLastFetchedAt = $data['resultLastFetchedAt'] ?? '';
    }
}
