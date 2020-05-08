<?php

namespace Matthias\SymfonyConsoleForm\Console\Input;

use Matthias\SymfonyConsoleForm\Form\FormUtil;
use ReflectionObject;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormBasedInputDefinitionFactory implements InputDefinitionFactory
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function createForFormType(string $formType, array &$resources = []): InputDefinition
    {
        $resources[] = new FileResource(__FILE__);

        $form = $this->formFactory->create($formType);

        $actualFormType = $form->getConfig()->getType()->getInnerType();
        $reflection = new ReflectionObject($actualFormType);
        $resources[] = new FileResource($reflection->getFileName());

        $inputDefinition = new InputDefinition();

        foreach ($form->all() as $name => $field) {
            if (!$this->isFormFieldSupported($field)) {
                continue;
            }

            $type = InputOption::VALUE_REQUIRED;
            $default = $field->getConfig()->getOption('data', null);
            $description = FormUtil::label($field);

            $inputDefinition->addOption(new InputOption($name, null, $type, $description, $default));
        }

        return $inputDefinition;
    }

    private function isFormFieldSupported(FormInterface $field): bool
    {
        if ($field->getConfig()->getCompound()) {
            if ($field->getConfig()->getType()->getInnerType() instanceof RepeatedType) {
                return true;
            }

            return false;
        }

        return true;
    }
}
