<?php

namespace iFixit\Akeneo\iFixitBundle\EventListener;

use iFixit\Akeneo\iFixitBundle\EventListener\iFixitApi;

use Symfony\Component\EventDispatcher\GenericEvent;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\GroupInterface;

class PostSaveListener {
   /**
    * When editing many products or groups at once, several post-save events
    * fire for each product. To de-dupe these, we incr/decr this value as we
    * get pre- and post-save events. We only fire off the export jobs when
    * we get the last post-save event (depth == 0).
    */
   private $preSaveEventDepth = 0;
   // List of skus that recieved post-save event
   /** @var \DS\Set */
   private $savedSkus;

   /** @var iFixitApi */
   private $ifixitApi;

   public function __construct(iFixitApi $ifixitApi) {
      $this->ifixitApi = $ifixitApi;
      $this->savedSkus = new \DS\Set();
   }

   public function onPreSaveAll(GenericEvent $event) {
      $allSubjects = $event->getSubject();
      $subject = $this->head($allSubjects);

      if ($subject instanceof ProductInterface) {
         $this->preSaveEventDepth++;
      } else if ($subject instanceof GroupInterface) {
         $this->preSaveEventDepth++;
      }
   }

   public function onPreSave(GenericEvent $event) {
      $subject = $event->getSubject();

      if ($subject instanceof GroupInterface) {
         $this->preSaveEventDepth++;
      }
   }

   public function onPostSave(GenericEvent $event) {
      $subject = $event->getSubject();

      switch (true) {
         case $subject instanceof GroupInterface:
            if (--$this->preSaveEventDepth == 0) {
               $this->notifySavedSkusChanged();
            }
            break;
         case $subject instanceof ProductInterface:
            if ($this->preSaveEventDepth) {
               $sku = $this->getSkuFromProduct($subject);
               $this->savedSkus->add($sku);
            } else {
               // If we're not inside a multiproduct save then notify
               // about the save immediately
               $skus = $this->getSkusFromProducts([$subject]);
               $this->notifySkusChanged($skus);
            }
            break;
         case $subject instanceof AttributeInterface:
            $this->notifyAttributeChanged($subject);
            break;
      }
   }

   public function onPostSaveAll(GenericEvent $event) {
      $allSubjects = $event->getSubject();
      $subject = $this->head($allSubjects);

      if ($subject instanceof GroupInterface) {
         // post-save-all on an array of groups should be the last event
         // possible so we should reset to 0.
         $this->preSaveEventDepth = 0;
         $this->notifySavedSkusChanged();
      } else if ($subject instanceof ProductInterface) {
         if (--$this->preSaveEventDepth == 0) {
            $this->notifySavedSkusChanged();
         }
      }
   }

   private function notifySavedSkusChanged() {
      $this->notifySkusChanged($this->savedSkus);
      $this->savedSkus->clear();
   }

   private function notifySkusChanged(\DS\Set $skus) {
      if ($skus->isEmpty()) {
         return;
      }
      $this->ifixitApi->post("admin/akeneo/skus_changed", ["skus" => $skus->toArray()]);
   }

   private function notifyAttributeChanged(AttributeInterface $attribute) {
      $this->ifixitApi->post("admin/akeneo/attribute_changed", [
         "code" => $attribute->getCode(),
      ]);
   }

   private function getSkusFromProducts(array $products): \DS\Set {
      return new \DS\Set(array_map(function($product) {
         return $this->getSkuFromProduct($product);
      }, $products));
   }

   private function getSkuFromProduct(ProductInterface $product): string {
      return (string)$product->getValue('sku');
   }

   private function head(?array $collection) {
      return $collection ? reset($collection) : null;
   }
}
