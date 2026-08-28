<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog\Grid;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use TVTCommerce\AdminAuditLog\Model\ResourceModel\AuditLog as AuditLogResource;

/**
 * Dedicated grid-only collection — separate from the "real" AuditLog\Collection sibling, exactly
 * like Magento\Cms\Model\ResourceModel\Block\Grid\Collection is separate from
 * Magento\Cms\Model\ResourceModel\Block\Collection. Two things are required together for a
 * custom AbstractCollection-based grid to work, verified directly against real Magento core:
 *
 * 1. Implements SearchResultInterface — DataProvider::searchResultToOutput() hard type-hints its
 *    argument as SearchResultInterface.
 * 2. Uses the generic Document class (not the real AuditLog model) as its item type —
 *    searchResultToOutput() also calls $item->getCustomAttributes() on every row, which only
 *    Document (via Magento\Framework\Api\AttributeValueFactory) implements. The real AuditLog
 *    model has no such method and would fatal with "Call to undefined method" the instant the
 *    grid renders any row — this only stayed hidden while the table had zero rows.
 *
 * The real AuditLog\Collection (real model items, used by Controller\Adminhtml\Log\MassDelete's
 * ->delete() calls) is kept untouched and unused by the grid.
 */
class Collection extends AbstractCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface|null
     */
    private $aggregations;

    protected function _construct(): void
    {
        $this->_init(Document::class, AuditLogResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritDoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * This collection is always driven by the grid's own filters/paging (see
     * DataProvider\Reporting::search()), never by an externally-built SearchCriteria — same as
     * Magento\Cms\Model\ResourceModel\Block\Grid\Collection::getSearchCriteria().
     *
     * @inheritDoc
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @inheritDoc
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Items are already populated by the collection's own load(), not injected externally.
     *
     * @inheritDoc
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
