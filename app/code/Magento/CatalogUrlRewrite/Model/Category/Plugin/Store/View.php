<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogUrlRewrite\Model\Category\Plugin\Store;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\Model\AbstractModel;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Plugin which is listening store resource model and on save or on delete replace catalog url rewrites
 *
 * @see \Magento\Store\Model\ResourceModel\Store
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @package Magento\CatalogUrlRewrite\Model\Category\Plugin\Store
 */
class View
{
    /**
     * @var \Magento\UrlRewrite\Model\UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator
     */
    protected $categoryUrlRewriteGenerator;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator
     */
    protected $productUrlRewriteGenerator;

    /**
     * @var AbstractModel
     */
    private $origStore;

    /**
     * @param UrlPersistInterface $urlPersist
     * @param CategoryFactory $categoryFactory
     * @param ProductFactory $productFactory
     * @param CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator
     * @param ProductUrlRewriteGenerator $productUrlRewriteGenerator
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        CategoryFactory $categoryFactory,
        ProductFactory $productFactory,
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator
    ) {
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->categoryFactory = $categoryFactory;
        $this->productFactory = $productFactory;
    }

    /**
     * @param \Magento\Store\Model\ResourceModel\Store $object
     * @param AbstractModel $store
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Store\Model\ResourceModel\Store $object,
        AbstractModel $store
    ) {
        $this->origStore = $store;
    }

    /**
     * Regenerate urls on store after save
     *
     * @param \Magento\Store\Model\ResourceModel\Store $object
     * @param \Magento\Store\Model\ResourceModel\Store $store
     * @return \Magento\Store\Model\ResourceModel\Store
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        \Magento\Store\Model\ResourceModel\Store $object,
        \Magento\Store\Model\ResourceModel\Store $store
    ) {
        if ($this->origStore->isObjectNew() || $this->origStore->dataHasChangedFor('group_id')) {
            if (!$this->origStore->isObjectNew()) {
                $this->urlPersist->deleteByData([UrlRewrite::STORE_ID => $this->origStore->getId()]);
            }

            $this->urlPersist->replace(
                $this->generateCategoryUrls($this->origStore->getRootCategoryId(), $this->origStore->getId())
            );

            $this->urlPersist->replace(
                $this->generateProductUrls(
                    $this->origStore->getWebsiteId(),
                    $this->origStore->getOrigData('website_id'),
                    $this->origStore->getId()
                )
            );
        }
        return $store;
    }

    /**
     * Generate url rewrites for products assigned to website
     *
     * @param int $websiteId
     * @param int $originWebsiteId
     * @param int $storeId
     * @return array
     */
    protected function generateProductUrls($websiteId, $originWebsiteId, $storeId)
    {
        $urls = [];
        $websiteIds = $websiteId != $originWebsiteId && $originWebsiteId !== null
            ? [$websiteId, $originWebsiteId]
            : [$websiteId];
        $collection = $this->productFactory->create()
            ->getCollection()
            ->addCategoryIds()
            ->addAttributeToSelect(['name', 'url_path', 'url_key', 'visibility'])
            ->addWebsiteFilter($websiteIds);
        foreach ($collection as $product) {
            $product->setStoreId($storeId);
            /** @var \Magento\Catalog\Model\Product $product */
            $urls = array_merge(
                $urls,
                $this->productUrlRewriteGenerator->generate($product)
            );
        }
        return $urls;
    }

    /**
     * @param int $rootCategoryId
     * @param int $storeId
     * @return array
     */
    protected function generateCategoryUrls($rootCategoryId, $storeId)
    {
        $urls = [];
        $categories = $this->categoryFactory->create()->getCategories($rootCategoryId, 1, false, true);
        foreach ($categories as $category) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category->setStoreId($storeId);
            $urls = array_merge(
                $urls,
                $this->categoryUrlRewriteGenerator->generate($category)
            );
        }
        return $urls;
    }

    /**
     * Delete unused url rewrites
     *
     * @param \Magento\Store\Model\ResourceModel\Store $subject
     * @param \Magento\Store\Model\ResourceModel\Store $result
     * @param AbstractModel $store
     * @return \Magento\Store\Model\ResourceModel\Store
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        \Magento\Store\Model\ResourceModel\Store $subject,
        \Magento\Store\Model\ResourceModel\Store $result,
        AbstractModel $store
    ) {
        $this->urlPersist->deleteByData([UrlRewrite::STORE_ID => $store->getId()]);

        return $result;
    }
}
