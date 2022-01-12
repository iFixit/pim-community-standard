<?php
declare(strict_types=1);

namespace iFixit\Akeneo;

use Akeneo\Pim\Enrichment\Component\Product\Factory\Value\PriceCollectionValueFactory as AkeneoPriceCollectionValueFactory;
use Akeneo\Pim\Enrichment\Component\Product\Factory\Value\ValueFactory;
use Akeneo\Pim\Enrichment\Component\Product\Model\PriceCollection;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductPrice;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Value\PriceCollectionValue;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Query\PublicApi\AttributeType\Attribute;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyTypeException;

class PriceCollectionValueFactory implements ValueFactory {
   private $akeneoValueFactory;

   /**
    * We're decorating the service from Akeneo because the class we want to 
    * alter was marked as final.
    */
   public function __construct(AkeneoPriceCollectionValueFactory $akeneoValueFactory) {
      $this->akeneoValueFactory = $akeneoValueFactory;
   }

   public function createWithoutCheckingData(Attribute $attribute, ?string $channelCode, ?string $localeCode, $data): ValueInterface {
      return $this->akeneoValueFactory->createWithoutCheckingData($attribute, $channelCode, $localeCode, $data);
   }

   public function createByCheckingData(Attribute $attribute, ?string $channelCode, ?string $localeCode, $data): ValueInterface {
      return $this->akeneoValueFactory->createByCheckingData($attribute, $channelCode, $localeCode, $data);
   }

   public function supportedAttributeType(): string {
      return $this->akeneoValueFactory->supportedAttributeType();
   }
}
