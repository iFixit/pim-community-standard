<?php declare(strict_types = 1);

namespace Ifixit\Bundle\StorefrontConnectorBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pim\Component\Catalog\Repository\GroupRepositoryInterface;
use Pim\Component\Catalog\Factory\GroupFactory;
use Pim\Component\Catalog\Model\GroupInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;

class CloneProductModelController {
   /** @var GroupFactory */
   protected $groupFactory;

   /** @var GroupRepositoryInterface */
   protected $groupRepository;

   /** @var SaverInterface */
   protected $groupSaver;

   public function __construct(
      GroupRepositoryInterface $groupRepository,
      GroupFactory $groupFactory,
      SaverInterface $groupSaver
   ) {
      $this->groupRepository = $groupRepository;
      $this->groupFactory = $groupFactory;
      $this->groupSaver = $groupSaver;
   }

   /**
    * @AclAncestor("pim_enrich_group_create")
    */
   public function cloneProductModel(Request $request): Response {
      $data = $this->getDecodedContent($request->getContent());
      $sourceGroupCode = $this->extractRequired("sourceCode", $data);
      $destGroupCode = $this->extractRequired("destCode", $data);
      $sourceGroup = $this->getProductGroup($sourceGroupCode);
      $destGroup = $this->cloneGroup($sourceGroup, $destGroupCode);
      $this->groupSaver->save($destGroup);

      return new JsonResponse(['success' => true], 204);
   }


   /**
    * Get the JSON decoded content. If the content is not a valid JSON, it throws an error 400.
    *
    * @param string $content content of a request to decode
    *
    * @throws BadRequestHttpException
    */
   protected function getDecodedContent(string $content): array {
      $decodedContent = json_decode($content, true);

      if (null === $decodedContent) {
         throw new BadRequestHttpException('Invalid json message received');
      }

      return $decodedContent;
   }

   protected function getProductGroup($code): GroupInterface {
      $group = $this->groupRepository->findOneByIdentifier($code);
      if (!$group) {
         throw new \NotFoundHttpException("Product group {$code} not found");
      }

      return $group;
   }

   protected function cloneGroup(GroupInterface $sourceGroup, string $destGroupCode) {
      $newGroup = $this->groupFactory->createGroup('VARIANT');
      $newGroup->getProductTemplate()->setValuesData(
       $sourceGroup->getProductTemplate()->getValuesData());
      $newGroup->setCode($destGroupCode);
      $newGroup->setAxisAttributes($sourceGroup->getAxisAttributes()->getValues());
      return $newGroup;
   }

   protected function getProductTemplateValuesData(GroupInterface $group): array {
      return $group->getProductTemplate()->getValuesData();
   }

   protected function extractRequired(string $field, array $data) {
      if (!array_key_exists($field, $data)) {
         throw new BadRequestHttpException("Body missing $field field");
      }
      if (!is_string($data[$field])) {
         throw new BadRequestHttpException("Field $field should be a string");
      }
      return $data[$field];
   }
}
