<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TVTCommerce\AdminAuditLog\Model\AuditLog\AuditLog as AuditLogModel;
use TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog as AuditLogResource;

/**
 * NOTE ON THE IMPORT BELOW (do not "simplify" it):
 * The data model class lives at Model/AuditLog/AuditLog.php, i.e. namespace
 * TVTCommerce\AdminAuditLog\Model\AuditLog with class name AuditLog — the
 * segment appears twice (folder, then class). The `use` statement below must
 * include both segments (...\Model\AuditLog\AuditLog) or this collection
 * fatals with "Class not found" the instant it tries to load a row, despite
 * passing php -l and looking correct at a glance.
 *
 * This is the real collection (real AuditLog model items) — used by Controller\Adminhtml\Log\
 * MassDelete, which calls ->delete() on each item. The admin grid does NOT use this class; it
 * uses the dedicated Grid\Collection sibling instead, because Magento's grid DataProvider
 * requires each item to expose getCustomAttributes() (only Document has that, not a real model,
 * and Document has no delete()) — see Grid\Collection for the full explanation.
 */
class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(AuditLogModel::class, AuditLogResource::class);
    }
}
