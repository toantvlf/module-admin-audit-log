<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Cron;

use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Throwable;
use TVTCommerce\AdminAuditLog\Model\Config;

/**
 * Daily housekeeping (see etc/crontab.xml, 02:30 server time): deletes rows
 * older than Model\Config::getRetentionDays(). Uses a direct ResourceConnection
 * DELETE with a bound cutoff timestamp rather than loading a Collection,
 * since this table can grow large and a bulk DELETE is far cheaper than
 * walking every row through the ORM.
 */
class CleanupOldLogs
{
    private const TABLE_NAME = 'tvt_admin_audit_log';

    public function __construct(
        private readonly Config $config,
        private readonly ResourceConnection $resourceConnection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName(self::TABLE_NAME);
            $cutoff = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->modify(sprintf('-%d days', $this->config->getRetentionDays()))
                ->format('Y-m-d H:i:s');

            $connection->delete($table, ['created_at < ?' => $cutoff]);
        } catch (Throwable $e) {
            // A broken cleanup job must never break the cron run itself — log and move on.
            $this->logger->error('TVTCommerce_AdminAuditLog: cleanup cron failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
