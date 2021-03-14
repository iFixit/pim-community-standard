<?php

namespace iFixit\Akeneo;

use Akeneo\Pim\Enrichment\Component\Product\Comparator\Attribute\PricesComparator as AkeneoPricesComparator;

/**
 * Extends Akeneo's comparator to allow partial updates to prices. So setting
 * one currency via a CSV doesn't erase all other currencies.
 */
class PricesComparator extends AkeneoPricesComparator {
   /**
    * {@inheritdoc}
    *
    * Merges the currency values in $data with what's in $originals
    * in a way that an amount of NULL for a particular currency will
    * remove that value from the output.
    *
    * The values are interpreted in $data to be that a currency with value NULL
    * means clear out the value for that currency. Note: output is not allowed
    * to have nulls, we just filter the currency values that get set to null.
    *
    * Originals:
    * CAD => 12.99, EUR => 14.75
    *
    * Data:
    * USD => 10.00, CAD => null
    *
    * Output:
    * USD => 10.00, EUR => 14.75
    *
    * Note: only returns non-null if incoming data results in a change.
    */
   public function compare($data, $originals) {
      $default = ['locale' => null, 'scope' => null, 'data' => []];
      $originals = array_merge($default, $originals);

      $dataPrices = [];
      $originalPrices = [];

      // Build a currency => amount map for incoming data
      foreach ($data['data'] as $price) {
         $dataPrices[$price['currency']] = $price['amount'];
         if (is_numeric($price['amount'])) {
            $dataPrices[$price['currency']] = number_format($price['amount'], 4);
         }
         // Fill in nulls here so that these two arrays end up with the same
         // keys and thus end up comparable
         $originalPrices[$price['currency']] = null;
      }

      // Build a currency => amount map for existing data
      foreach ($originals['data'] as $price) {
         $originalPrices[$price['currency']] = $price['amount'];
         if (is_numeric($price['amount'])) {
            $originalPrices[$price['currency']] = number_format($price['amount'], 4);
         }
      }

      ksort($originalPrices);
      ksort($dataPrices);

      if ($dataPrices !== $originalPrices) {
         $merged = array_merge($originalPrices, $dataPrices);
         $data['data'] = $this->hashToCurrencyGroup($merged);
         return $data;
      }

      return null;
   }

   /**
    * Turn an array like ['USD' => 1.50, 'CAD' => 2.99] to
    * [['currency' => 'USD', 'amount' => 1.50],
    *  ['currency' => 'CAD', 'amount' => 2.99]]
    */
   private function hashToCurrencyGroup(array $hash): array {
      $output = [];
      foreach ($hash as $currency => $amount) {
         // Omit nulls from output
         if (is_null($amount)) {
            continue;
         }
         $output[] = [
            'currency' => $currency,
            'amount' => $amount
         ];
      }
      return $output;
   }
}
