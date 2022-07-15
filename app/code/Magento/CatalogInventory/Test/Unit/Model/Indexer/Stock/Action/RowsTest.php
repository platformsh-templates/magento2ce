<?php
/**
 * @category    Magento
 * @package     Magento_CatalogInventory
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventory\Test\Unit\Model\Indexer\Stock\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RowsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Action\Rows
     */
    protected $_model;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_model = $objectManager->getObject(\Magento\CatalogInventory\Model\Indexer\Stock\Action\Rows::class);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Could not rebuild index for empty products array
     */
    public function testEmptyIds()
    {
        $this->_model->execute(null);
    }
}
