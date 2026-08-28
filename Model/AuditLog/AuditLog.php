<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Model\AuditLog;

use Magento\Framework\Model\AbstractModel;
use TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog as AuditLogResource;

/**
 * Data model for a single tvt_admin_audit_log row — backs the admin grid
 * (see view/adminhtml/ui_component/tvt_admin_audit_log_listing.xml).
 * Rows are written by Observer\LogAdminAction via the resource model directly.
 */
class AuditLog extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(AuditLogResource::class);
    }
}
