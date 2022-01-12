<?php

namespace iFixit\Akeneo;

use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\FlatToStandard\ColumnsMerger as AkeneoColumnsMerger;
use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\FlatToStandard\AttributeColumnInfoExtractor;
use Akeneo\Pim\Structure\Component\AttributeTypes;

/**
 * Merge columns for single value that can be provided in many columns like prices and metric
 *
 * These two values supports two different formats, we standardize here to the one column format
 *
 * For Prices
 *   - '10 EUR, 24 USD' or
 * or
 *   - 'price-EUR': '10',
 *   - 'price-USD': '24',
 *
 * For Metrics
 *   - 'weight': '10 KILOGRAM',
 * or
 *   - 'weight': '10',
 *   - 'weight-unit': 'KILOGRAM',
 *   - 'weight': '24'
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ColumnsMerger extends AkeneoColumnsMerger {
}
