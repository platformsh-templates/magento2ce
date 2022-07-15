<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProduct\Plugin\Model\Attribute\Backend;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Skip validate attributes used for create configurable product
 */
class AttributeValidation
{
    /**
     * @var Configurable
     */
    private $configurableProductType;

    /**
     * AttributeValidation constructor.
     * @param Configurable $configurableProductType
     */
    public function __construct(
        Configurable $configurableProductType
    ) {
        $this->configurableProductType = $configurableProductType;
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $entity
     * @return bool
     */
    public function aroundValidate(
        \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend $subject,
        \Closure $proceed,
        \Magento\Framework\DataObject $entity
    ) {
        $attribute = $subject->getAttribute();
        if ($entity instanceof ProductInterface
            && $entity->getTypeId() == Configurable::TYPE_CODE
            && in_array(
                $attribute->getAttributeId(),
                $this->configurableProductType->getUsedProductAttributeIds($entity),
                true
            )
        ) {
            return true;
        }
        return $proceed($entity);
    }
}
