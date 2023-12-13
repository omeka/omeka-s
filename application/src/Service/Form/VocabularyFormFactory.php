<?php
namespace Omeka\Service\Form;

use Omeka\Form\VocabularyForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class VocabularyFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new VocabularyForm;
        $form->setTranslator($services->get('MvcTranslator'));
        $form->setOption('vocabulary', $options['vocabulary'] ?? null);
        return $form;
    }
}
