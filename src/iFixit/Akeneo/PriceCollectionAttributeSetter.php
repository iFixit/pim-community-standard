<?php

namespace iFixit\Akeneo;

use Akeneo\Pim\Enrichment\Component\Product\Builder\EntityWithValuesBuilderInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Enrichment\Component\Product\Updater\Adder\PriceCollectionAttributeAdder;
use Akeneo\Pim\Enrichment\Component\Product\Updater\Setter\AbstractAttributeSetter;

class PriceCollectionAttributeSetter extends AbstractAttributeSetter {
   /** @var PriceCollectionAttributeAdder */
   protected $priceAdder;

   /**
    * @param EntityWithValuesBuilderInterface $entityWithValuesBuilder
    * @param PriceCollectionAttributeAdder $priceAdder
    * @param string[] $supportedTypes
    */
   public function __construct(
      EntityWithValuesBuilderInterface $entityWithValuesBuilder,
      PriceCollectionAttributeAdder $priceAdder,
      array $supportedTypes
   ) {
      parent::__construct($entityWithValuesBuilder);
      $this->priceAdder = $priceAdder;
      $this->supportedTypes = $supportedTypes;
   }

   /**
    * {@inheritdoc}
    */
   public function setAttributeData(
       EntityWithValuesInterface $entityWithValues,
       AttributeInterface $attribute,
       $data,
       array $options = []
   ) {
      $options = $this->resolver->resolve($options);
      $this->priceAdder->addAttributeData($entityWithValues, $attribute, $data, $options);
   }
}
