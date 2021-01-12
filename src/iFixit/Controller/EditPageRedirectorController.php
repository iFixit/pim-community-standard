<?php declare(strict_types = 1);

namespace iFixit\Akeneo\iFixitBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductModelRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;

class EditPageRedirectorController {
   /** @var ProductModelRepositoryInterface */
   protected $modelRepository;

   /** @var ProductRepositoryInterface */
   protected $productRepository;

   public function __construct(
      ProductModelRepositoryInterface $modelRepository,
      ProductRepositoryInterface $productRepository
   ) {
      $this->modelRepository = $modelRepository;
      $this->productRepository = $productRepository;
   }

   public function redirectToProduct($sku): Response {
      $product = $this->productRepository->findOneByIdentifier($sku);
      if (!$product) {
         throw new \NotFoundHttpException("Product {$sku} not found");
      }
      return new RedirectResponse("/#/enrich/product/{$product->getId()}");
   }

   public function redirectToProductModel($productcode): Response {
      $productModel = $this->modelRepository->findOneByIdentifier($productcode);
      if (!$productModel) {
         throw new \NotFoundHttpException("Product {$productcode} not found");
      }
      return new RedirectResponse("/#/enrich/product-model/{$productModel->getId()}");
   }
}
