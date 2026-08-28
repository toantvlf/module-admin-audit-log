<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AuditLog extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('tvt_admin_audit_log', 'log_id');
    }
}
