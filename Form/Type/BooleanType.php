<?php

namespace XM\FilterBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use XM\FilterBundle\Form\DataTransformer\BooleanTypeToBooleanTransformer;

class BooleanType extends AbstractType
{
    const VALUE_FALSE = 0;
    const VALUE_TRUE = 1;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new BooleanTypeToBooleanTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, array(
            'value' => $options['value'],
            'checked' => $form->getViewData(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $emptyData = function (FormInterface $form, $viewData) {
            return $viewData;
        };

        $resolver->setDefaults(array(
            'value' => '1',
            'empty_data' => $emptyData,
            'compound' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'checkbox';
    }
}