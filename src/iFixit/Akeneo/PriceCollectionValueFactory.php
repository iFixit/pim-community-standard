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
      if (!\is_array($data)) {
         throw InvalidPropertyTypeException::arrayExpected(
            $attribute->code(),
            static::class,

            $data
         );
      }

      foreach ($data as $price) {
         if (!\is_array($price)) {
            throw InvalidPropertyTypeException::arrayOfArraysExpected(
               $attribute->code(),
               static::class,
               $data
            );
         }

         if (!array_key_exists('amount', $price)) {
            throw InvalidPropertyTypeException::arrayKeyExpected(
               $attribute->code(),
               'amount',
               static::class,
               $data
            );
         }

         if (!isset($price['currency'])) {
            throw InvalidPropertyTypeException::arrayKeyExpected(
               $attribute->code(),
               'currency',
               static::class,
               $data
            );
         }
      }

      return $this->createWithoutCheckingData($attribute, $channelCode, $localeCode, $data);
   }

   public function supportedAttributeType(): string {
      return $this->akeneoValueFactory->supportedAttributeType();
   }
}
