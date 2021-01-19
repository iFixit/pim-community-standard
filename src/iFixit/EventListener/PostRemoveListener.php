<?php

namespace iFixit\Akeneo\iFixitBundle\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeOptionInterface;

class PostRemoveListener {
   /** @var iFixitApi */
   private $ifixitApi;

   public function __construct(iFixitApi $ifixitApi) {
      $this->ifixitApi = $ifixitApi;
   }

   public function onPostRemove(GenericEvent $event) {
      $subject = $event->getSubject();

      switch (true) {
         case $subject instanceof ProductInterface:
            $sku = (string)$subject->getValue('sku');
            $this->ifixitApi->post("admin/akeneo/sku_removed", ['sku' => $sku]);
            break;
         case $subject instanceof AttributeInterface:
            $code = $subject->getCode();
            $this->ifixitApi->post("admin/akeneo/attribute_removed", ['code' => $code]);
            break;
         case $subject instanceof AttributeOptionInterface:
            $code = $subject->getAttribute()->getCode();
            $this->ifixitApi->post("admin/akeneo/attribute_changed", ['code' => $code]);
            break;
         default:
            return;
      }
   }
}
