<?php

declare(strict_types=1);

/*
 * This file is part of the ConnectHolland CookieConsentBundle package.
 * (c) Connect Holland.
 */

namespace ConnectHolland\CookieConsentBundle\Form;

use ConnectHolland\CookieConsentBundle\Cookie\CookieChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CookieConsentType extends AbstractType
{
    /**
     * @var CookieChecker
     */
    protected $cookieChecker;

    /**
     * @var array
     */
    protected $cookieCategories;

    /**
     * @var array
     */
    protected $cookieCategoriesFlatten;

    /**
     * @var bool
     */
    protected $cookieConsentSimplified;

    public function __construct(CookieChecker $cookieChecker, array $cookieCategories, bool $cookieConsentSimplified = false)
    {
        $this->cookieChecker           = $cookieChecker;
        $this->cookieCategories        = $cookieCategories;
        $this->cookieConsentSimplified = $cookieConsentSimplified;
        $this->cookieCategoriesFlatten = $this->flattenCookieCategories($cookieCategories);
    }

    /**
     * Build the cookie consent form.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->cookieCategoriesFlatten as $category) {
            $builder->add($category, ChoiceType::class, [
                'expanded' => true,
                'multiple' => false,
                'data'     => $this->cookieChecker->isCategoryAllowedByUser($category) ? 'true' : 'false',
                'choices'  => [
                    ['ch_cookie_consent.yes' => 'true'],
                    ['ch_cookie_consent.no' => 'false'],
                ],
            ]);
        }

        if ($this->cookieConsentSimplified === false) {
            $builder->add('save', SubmitType::class, ['label' => 'ch_cookie_consent.save', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
            $builder->add('customize', SubmitType::class, ['label' => 'ch_cookie_consent.customize', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
        }

        $builder->add('use_only_functional_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_only_functional_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn']]);
        $builder->add('use_all_cookies', SubmitType::class, ['label' => 'ch_cookie_consent.use_all_cookies', 'attr' => ['class' => 'btn ch-cookie-consent__btn ch-cookie-consent__btn--secondary']]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (isset($data['use_all_cookies'])) {
                foreach ($this->cookieCategoriesFlatten as $category) {
                    $data[$category] = 'true';
                }
            }

            $event->setData($data);
        });
    }

    /**
     * Default options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'CHCookieConsentBundle',
        ]);
    }

    /**
     * Remove nested arrays.
     */
    private function flattenCookieCategories($cookieCategories, $cookieCategoriesFlatten = [])
    {
        foreach ($cookieCategories as $category) {
            if (is_array($category)) {
                $cookieCategoriesFlatten = $this->flattenCookieCategories($category, $cookieCategoriesFlatten);
            } else {
                $cookieCategoriesFlatten[] = $category;
            }
        }

        return $cookieCategoriesFlatten;
    }
}
