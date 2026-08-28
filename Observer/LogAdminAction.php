<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Observer;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;
use Throwable;
use TVTCommerce\AdminAuditLog\Model\AuditLog\AuditLogFactory;
use TVTCommerce\AdminAuditLog\Model\Config;
use TVTCommerce\AdminAuditLog\Model\ParamSanitizer;
use TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog as AuditLogResource;

/**
 * Fires on controller_action_postdispatch_adminhtml (see etc/adminhtml/events.xml) —
 * i.e. after EVERY admin controller action, not just AI Copilot/Merchandiser actions.
 * Records who did what and when. A broken audit log must never break the admin
 * action it is observing, so persistence failures are logged and swallowed.
 */
class LogAdminAction implements ObserverInterface
{
    private const MAX_PARAMS_LENGTH = 4000;
    private const TRUNCATED_SUFFIX = '…[truncated]';
    private const UNKNOWN_ADMIN_USERNAME = 'unknown';

    /**
     * Full action names to never log, even sanitized — these are credential-entry
     * requests (login/logout), matched as a case-insensitive substring.
     */
    private const SKIPPED_FULL_ACTION_NAME_NEEDLES = [
        'admin_auth_login',
        'adminhtml_auth_login',
        'admin_auth_logout',
        'adminhtml_auth_logout',
        'backend_auth_login',
        'backend_auth_logout',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly AdminSession $adminSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly ParamSanitizer $paramSanitizer,
        private readonly AuditLogFactory $auditLogFactory,
        private readonly AuditLogResource $auditLogResource,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $request = $this->getHttpRequest($observer);
            if ($request === null) {
                return;
            }

            $fullActionName = (string) $request->getFullActionName();
            $method = strtoupper((string) $request->getMethod());

            if ($this->shouldSkip($fullActionName, $method)) {
                return;
            }

            $this->persist($fullActionName, $method, $request);
        } catch (Throwable $e) {
            $this->logger->error('TVTCommerce_AdminAuditLog: failed to record admin action', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getHttpRequest(Observer $observer): ?HttpRequest
    {
        $controllerAction = $observer->getEvent()->getControllerAction();
        if ($controllerAction === null) {
            return null;
        }

        $request = $controllerAction->getRequest();

        return $request instanceof HttpRequest ? $request : null;
    }

    private function shouldSkip(string $fullActionName, string $method): bool
    {
        $normalizedActionName = strtolower($fullActionName);

        foreach (self::SKIPPED_FULL_ACTION_NAME_NEEDLES as $needle) {
            if (str_contains($normalizedActionName, $needle)) {
                return true;
            }
        }

        return $method === 'GET' && !$this->config->isGetRequestLoggingEnabled();
    }

    private function persist(string $fullActionName, string $method, HttpRequest $request): void
    {
        $rawParams = $method === 'POST' ? (array) $request->getPostValue() : (array) $request->getParams();
        $sanitizedParams = $this->paramSanitizer->sanitize($rawParams);

        /** @var \TVTCommerce\AdminAuditLog\Model\AuditLog\AuditLog $auditLog */
        $auditLog = $this->auditLogFactory->create();
        $auditLog->setData([
            'admin_user_id' => $this->getAdminUserId(),
            'admin_username' => $this->getAdminUsername(),
            'full_action_name' => $fullActionName,
            'request_method' => $method,
            'params' => $this->formatParams($sanitizedParams),
            'ip' => (string) ($this->remoteAddress->getRemoteAddress() ?: ''),
        ]);

        $this->auditLogResource->save($auditLog);
    }

    private function getAdminUserId(): ?int
    {
        $user = $this->adminSession->getUser();

        return $user !== null && $user->getId() !== null ? (int) $user->getId() : null;
    }

    private function getAdminUsername(): string
    {
        $user = $this->adminSession->getUser();
        $username = $user !== null ? (string) $user->getUsername() : '';

        return $username !== '' ? $username : self::UNKNOWN_ADMIN_USERNAME;
    }

    /**
     * "field: value; nested.field: value" — see ParamSanitizer::formatForDisplay() — is what
     * actually shows an admin what an action changed, directly in the grid cell, instead of
     * a JSON blob they'd have to copy out and pretty-print themselves.
     *
     * @param array<array-key, mixed> $sanitizedParams
     */
    private function formatParams(array $sanitizedParams): string
    {
        $formatted = $this->paramSanitizer->formatForDisplay($sanitizedParams);

        if (strlen($formatted) <= self::MAX_PARAMS_LENGTH) {
            return $formatted;
        }

        $truncateAt = self::MAX_PARAMS_LENGTH - strlen(self::TRUNCATED_SUFFIX);

        return substr($formatted, 0, max(0, $truncateAt)) . self::TRUNCATED_SUFFIX;
    }
}
