<?php

namespace Ifixit\Bundle\StorefrontConnectorBundle\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\AttributeInterface;

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
         default:
            return;
      }
   }
}
