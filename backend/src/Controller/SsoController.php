<?php

namespace App\Controller;

use App\Entity\SsoCode;
use App\Service\SsoService;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\SsoCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sso')]
class SsoController extends AbstractController
{
    public function __construct(
        private ApplicationRepository $applicationRepository,
        private SsoCodeRepository $ssoCodeRepository,
        private EntityManagerInterface $entityManager,
        private SsoService $ssoService,

    ) {
    }

    /**
     * Generate SSO code and redirect to application
     * GET /sso/authorize?application_id={id}
     */
    #[Route('/authorize', name: 'sso_authorize', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function authorize(Request $request): Response
    {
        $applicationId = $request->query->get('application_id');
        
        if (!$applicationId) {
            return new JsonResponse([
                'success' => false,
                'error' => 'application_id parameter is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('api_auth_login');
        }

        $application = $this->applicationRepository->find($applicationId);
        
        if (!$application) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Application not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$application->isActive()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Application is not active'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$application->getSsoCallbackUrl()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Application does not support SSO (no callback URL configured)'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Generate a cryptographically secure random code
        $code = bin2hex(random_bytes(32)); // 64 characters

        // Get JWT token from cookie
        $jwtToken = $request->cookies->get('access_token', '');

        // Create SSO code entry
        $ssoCode = new SsoCode();
        $ssoCode->setCode($code);
        $ssoCode->setUser($user);
        $ssoCode->setApplication($application);
        $ssoCode->setJwtToken($jwtToken);
        $ssoCode->setExpiresAt(new \DateTime('+30 seconds'));

        $this->entityManager->persist($ssoCode);
        $this->entityManager->flush();

        // Build callback URL with code
        $callbackUrl = $application->getSsoCallbackUrl();
        $separator = strpos($callbackUrl, '?') === false ? '?' : '&';
        $redirectUrl = $callbackUrl . $separator . 'code=' . urlencode($code);

        return new RedirectResponse($redirectUrl);
    }

    /**
     * Verify and exchange SSO code
     * POST /sso/verify
     */
    #[Route('/verify', name: 'sso_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        $code = $data['code'] ?? null;
        $ssoSecret = $data['sso_secret'] ?? null;

        if (!$code || !$ssoSecret) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Missing required parameters: code, application_id, sso_secret'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ssoService->verifySsoCode($code,  $ssoSecret);
            
            $user = $result['user'];
            $jwtToken = $result['jwt'];

            return new JsonResponse([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstName(),
                    'lastname' => $user->getLastName(),
                    'role' => $user->getRoles(),
                    'organization_id' => $user->getOrganization()?->getId(),
                ],
                'jwt' => $jwtToken
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
