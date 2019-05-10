<?php

namespace UniteCMS\CoreBundle\Form;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use UniteCMS\CoreBundle\Entity\Setting;

class FieldableFormType extends AbstractType
{
    /**
     * @var TokenStorage $tokenStorage
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    public function __construct(TokenStorage $tokenStorage, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // Handle content locales
        if (!empty($options['locales'])) {

            // if this fieldable has exactly one possible locale, add it as hidden field.
            if (count($options['locales']) == 1) {
                $builder->add('locale', HiddenType::class, ['data' => $options['locales'][0]]);

            // if this fieldable is from type setting and locale is already set, we don't allow the user to change it
            } elseif(!empty($options['content']) && is_object($options['content']) && $options['content'] instanceof Setting && !empty($options['content']->getLocale())) {
                $builder->add('locale', HiddenType::class, ['data' => $options['content']->getLocale()]);

            // if this fieldable has more than one possible locale, render a selection list.
            } else {
                $choices = [];
                foreach ($options['locales'] as $locale) {
                    $choices[Intl::getLocaleBundle()->getLocaleName($locale)] = $locale;
                }
                $builder->add('locale', ChoiceType::class, ['choices' => $choices]);
            }
        }

        /**
         * @var FieldableFormField $field
         */
        foreach ($options['fields'] as $field) {
            try {
                $builder->add(
                    $field->getFieldType()->getIdentifier($field->getFieldDefinition()),
                    $field->getFieldType()->getFormType($field->getFieldDefinition()),
                    $field->getFieldType()->getFormOptions($field->getFieldDefinition())
                );
            } catch (\Exception $e) {
                $this->logger->error('Field could not be added to this fieldable form.', ['exception' => $e]);
                $builder->add(
                    $field->getFieldType()->getIdentifier($field->getFieldDefinition()),
                    FieldExceptionFormType::class,
                    [
                        'exception' => $e,
                    ]
                );
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('fields');
        $resolver->setDefined('locales');
        $resolver->setDefined('content');
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getProviderKey() == "api") {
            $resolver->setDefault('csrf_protection', false);
        }
    }
}
