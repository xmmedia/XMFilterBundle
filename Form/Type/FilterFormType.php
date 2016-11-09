<?php

/*
 * (c) XM Media Inc. <dhein@xmmedia.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XM\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use XM\FilterBundle\Component\FilterComponent;

/**
 * Provides some defaults for a list filter form.
 *
 * @author Darryl Hein, XM Media Inc. <dhein@xmmedia.com>
 */
class FilterFormType extends AbstractType
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }

    /**
     * Keep the prefix short/simple.
     *
     * @return string
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return FilterComponent::FORM_BLOCK_NAME;
    }
}