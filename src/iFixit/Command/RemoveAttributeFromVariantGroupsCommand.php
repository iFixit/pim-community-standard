<?
declare(strict_types = 1);

namespace iFixit\Akeneo\iFixitBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Pim\Bundle\CatalogBundle\Entity\ProductTemplate;

/**
 * Remove an attribute from every variant group (product template)
 */
class RemoveAttributeFromVariantGroupsCommand extends ContainerAwareCommand {
   private $groupRepository;
   private $saver;

   protected function configure() {
      $this->setName('ifixit:remove-attribute-from-variant-groups')
      ->setDescription('Remove an attribute from every variant group (product template)')
      ->addArgument('attribute_code',
         InputArgument::REQUIRED,
         'Attribute Code'
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {
      $groupRepository = $this->getContainer()->get('pim_catalog.repository.group');
      $saver = $this->getContainer()->get('pim_catalog.saver.group');
      $detacher = $this->getContainer()->get('akeneo_storage_utils.doctrine.object_detacher');
      $attributeCode = $input->getArgument('attribute_code');

      $output->writeln("<info>Beginning removal of $attributeCode from every variant group</info>");

      foreach ($groupRepository->getAllVariantGroups() as $variantGroup) {
         $template = $variantGroup->getProductTemplate();
         if ($template->hasValueForAttributeCode($attributeCode)) {
            $this->removeAttributeFromTemplate($template, $attributeCode);
            $saver->save($variantGroup);
            $output->writeln("<info>Removed $attributeCode from variant group:{$variantGroup->getCode()}</info>");
            $products = $variantGroup->getProducts();
            $detacher->detachAll($products->toArray());
            $detacher->detach($variantGroup);
            $detacher->detach($template);
            gc_collect_cycles();
         }
      }

      $output->writeln("<info>Finished removing $attributeCode</info>");
   }

   private function removeAttributeFromTemplate(
    ProductTemplate $template, string $attributeCode) {
      $valuesData = $template->getValuesData();
      unset($valuesData[$attributeCode]);
      $template->setValuesData($valuesData);
   }
}
