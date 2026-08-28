<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Renders the audit log grid (view/adminhtml/ui_component/tvt_admin_audit_log_listing.xml).
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'TVTCommerce_AdminAuditLog::view';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TVTCommerce_AdminAuditLog::log');
        $resultPage->getConfig()->getTitle()->prepend(__('Admin Audit Log'));

        return $resultPage;
    }
}
