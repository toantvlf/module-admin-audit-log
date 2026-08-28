<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog\CollectionFactory;

/**
 * Deletes the log entries selected in the grid massaction (see
 * view/adminhtml/ui_component/tvt_admin_audit_log_listing.xml's "delete" action).
 *
 * Uses Magento\Ui\Component\MassAction\Filter — the same helper every core
 * MassDelete controller uses (e.g. Magento\Catalog\Controller\Adminhtml\Product\MassDelete)
 * — instead of reading the "selected" request param directly. A raw "selected" read only
 * covers "check individual rows"; it silently deletes nothing when the admin uses the
 * grid's "Select all N entries" link (which spans every page and submits via "excluded"
 * ids + exclude-mode instead of a populated "selected" array).
 */
class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'TVTCommerce_AdminAuditLog::manage';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(__('Please select at least one log entry.'));

            return $resultRedirect->setPath('*/*/index');
        }

        $deletedCount = 0;
        foreach ($collection as $auditLog) {
            $auditLog->delete();
            $deletedCount++;
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 log entry(-ies) have been deleted.', $deletedCount)
        );

        return $resultRedirect->setPath('*/*/index');
    }
}
