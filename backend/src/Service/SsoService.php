<?php

namespace App\Service;

use App\Repository\SsoCodeRepository;
use App\Repository\ApplicationRepository;

class SsoService 
{
    public function __construct(
        private SsoCodeRepository $ssoCodeRepository,
        private ApplicationRepository $applicationRepository,
    ) {
    }

    public function verifySsoCode(string $code, string $ssoSecret): array
    {
        // Find the SSO code
        $ssoCode = $this->ssoCodeRepository->findOneBy(['code' => $code]);
        if(!$ssoCode) {
            throw new \InvalidArgumentException('Code invalid');
        }

        $application = $ssoCode->getApplication();

        if ($application->getSsoSecret() !== $ssoSecret) {
            throw new \InvalidArgumentException('Invalid SSO secret');
        }

        // Validate code
        if ($ssoCode->isExpired()) {
            throw new \InvalidArgumentException('Code expired');
        }

        if ($ssoCode->isUsed()) {
            throw new \InvalidArgumentException('Code already used');
        }

        // Verify application matches
        if ($ssoCode->getApplication()->getId() !== $application->getId()) {
            throw new \InvalidArgumentException('Unauthorized application');
        }

        // Get user data before deletion
        $user = $ssoCode->getUser();
        $jwtToken = $ssoCode->getJwtToken();

        // Delete code immediately after use
        $this->ssoCodeRepository->deleteUsedCode($code);

        return [
            'user' => $user,
            'jwt' => $jwtToken,
        ];
    }
}