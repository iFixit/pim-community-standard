<?php

namespace iFixit\Akeneo\iFixitBundle\EventListener;

use iFixit\Akeneo\iFixitBundle\EventListener\iFixitApi;

use Symfony\Component\EventDispatcher\GenericEvent;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeOptionInterface;
use Psr\Log\LoggerInterface;

class PostSaveListener {
   /**
    * When editing many products or groups at once, several post-save events
    * fire for each product. To de-dupe these, we incr/decr this value as we
    * get pre- and post-save events. We only fire off the export jobs when
    * we get the last post-save event (depth == 0).
    */
   private $preSaveEventDepth = 0;
   // Same thing but for attributes
   private $attrPreSaveEventDepth = 0;

   // List of skus that recieved post-save event
   /** @var \Ds\Set */
   private $savedSkus;

   // List of attributes that recieved post-save events
   /** @var \Ds\Set */
   private $savedAttributes;

   /** @var iFixitApi */
   private $ifixitApi;

   public function __construct(iFixitApi $ifixitApi) {
      $this->ifixitApi = $ifixitApi;
      $this->savedSkus = new \Ds\Set();
      $this->savedAttributes = new \Ds\Set();
   }

   public function onPreSaveAll(GenericEvent $event) {
      $allSubjects = $event->getSubject();
      $subject = $this->head($allSubjects);
      $this->logEvent("Pre save all", $subject);

      if ($subject instanceof ProductInterface) {
         $this->preSaveEventDepth++;
      } else if ($subject instanceof ProductModelInterface) {
         $this->preSaveEventDepth++;
      } else if ($subject instanceof AttributeInterface) {
         $this->attrPreSaveEventDepth++;
      } else if ($subject instanceof AttributeOptionInterface) {
         $this->attrPreSaveEventDepth++;
      }
   }

   public function onPreSave(GenericEvent $event) {
      $subject = $event->getSubject();
      $this->logEvent("Pre save", $subject);

      if ($subject instanceof ProductModelInterface) {
         $this->preSaveEventDepth++;
      }
   }

   public function onPostSave(GenericEvent $event) {
      $subject = $event->getSubject();

      $this->logEvent("Post save", $subject);
      switch (true) {
         case $subject instanceof ProductModelInterface:
            $skus = $this->getSkusFromProductModel($subject);
            $this->savedSkus = $this->savedSkus->merge($skus);
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
         case $subject instanceof AttributeOptionInterface:
            $subject = $subject->getAttribute();
         case $subject instanceof AttributeInterface:
            $attrCode = $subject->getCode();
            $this->savedAttributes->add($attrCode);
            // If we're not in a pre-save-all/post-save-all operation, then
            // notify immediately.
            if (!$this->attrPreSaveEventDepth) {
               $this->notifySavedAttributesChanged();
            }
            break;
      }
   }

   public function onPostSaveAll(GenericEvent $event) {
      $allSubjects = $event->getSubject();
      $subject = $this->head($allSubjects);
      $this->logEvent("Post save all", $subject);

      if ($subject instanceof ProductModelInterface) {
         // post-save-all on an array of groups should be the last event
         // possible so we should reset to 0.
         $this->preSaveEventDepth = 0;
         $this->notifySavedSkusChanged();
      } else if ($subject instanceof ProductInterface) {
         if (--$this->preSaveEventDepth == 0) {
            $this->notifySavedSkusChanged();
         }
      }

      if ($subject instanceof AttributeInterface ||
       $subject instanceof AttributeOptionInterface) {
         if (--$this->attrPreSaveEventDepth == 0) {
           $this->notifySavedAttributesChanged();
         }
      }
   }

   private function notifySavedSkusChanged() {
      $this->notifySkusChanged($this->savedSkus);
      $this->savedSkus->clear();
   }

   private function notifySkusChanged(\Ds\Set $skus) {
      if ($skus->isEmpty()) {
         return;
      }
      // Give lower priority to batch jobs (import from CSV, batch edit), which
      // are run from the CLI
      $priorityAdjustment = php_sapi_name() == 'cli' ? 1 : 0; // higher number = lower priority
      $this->ifixitApi->post("admin/akeneo/skus_changed", [
         "skus" => $skus->toArray(),
         "priorityAdjustment" => $priorityAdjustment,
         "test" => php_sapi_name(),
      ]);
   }

   private function notifySavedAttributesChanged() {
      foreach ($this->savedAttributes as $attrCode) {
         $this->notifyAttributeChanged($attrCode);
      }
      $this->savedAttributes->clear();
   }

   private function notifyAttributeChanged(string $attrCode) {
      $this->ifixitApi->log("Sending attribute_changed hook: $attrCode");
      $this->ifixitApi->post("admin/akeneo/attribute_changed", [
         "code" => $attrCode,
      ]);
   }

   private function getSkusFromProducts(array $products): \Ds\Set {
      return new \Ds\Set(array_map(function($product) {
         return $this->getSkuFromProduct($product);
      }, $products));
   }

   private function getSkusFromProductModel(ProductModelInterface $model): \Ds\Set {
      return $this->getSkusFromProducts($model->getProducts()->getValues());
   }

   private function getSkuFromProduct(ProductInterface $product): string {
      return (string)$product->getValue('sku');
   }

   private function head(?array $collection) {
      return $collection ? reset($collection) : null;
   }

   private function logEvent(string $event, $subject) {
      $subjectType = is_object($subject) ? get_class($subject) : gettype($subject);
      $this->ifixitApi->log("$event: $subjectType");
   }
}
