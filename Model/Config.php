<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const PREFIX = 'tvtcommerce_admin_audit_log';
    private const DEFAULT_RETENTION_DAYS = 90;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::PREFIX . '/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getRetentionDays(): int
    {
        $configured = (int) $this->get('general/retention_days');

        return $configured > 0 ? $configured : self::DEFAULT_RETENTION_DAYS;
    }

    public function isGetRequestLoggingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::PREFIX . '/general/log_get_requests',
            ScopeInterface::SCOPE_STORE
        );
    }

    private function get(string $path): mixed
    {
        return $this->scopeConfig->getValue(
            self::PREFIX . '/' . $path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
