<?php declare(strict_types=1);
/**
 * DataTypeRdf
 *
 * Implement common W3C RDF datatypes (html, xml, boolean) in order to simplify
 * user input and to give more semanticity to values of resources.
 *
 * @copyright Daniel Berthereau, 2018-2023
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */
namespace DataTypeRdf;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    use TraitModule;

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translate = $services->get('ControllerPluginManager')->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.62')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.62'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
    }

    public function install(ServiceLocatorInterface $services): void
    {
        if ($services->get('Omeka\ModuleManager')->getModule('RdfDatatype')) {
            require_once __DIR__ . '/data/scripts/upgrade_from_rdfdatatype.php';
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
        // TODO Waiting fix omeka/omeka-s#.
        $sharedEventManager->attach(
            '*',
            'view.layout',
            [$this, 'addDataTypeRdfCssFix']
        );
    }

    public function addDataTypeRdfCssFix(Event $event): void
    {
        /** @var \Laminas\View\Renderer\PhpRenderer $view */
        $view = $event->getTarget();
        $view->headLink()
            ->appendStylesheet($view->assetUrl('css/data-type-rdf-fix.css', 'DataTypeRdf'));
    }
}
