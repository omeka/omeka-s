<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;
use Omeka\Captcha\CaptchaInterface;

/**
 * A CAPTCHA form element used to verify whether a user is human.
 *
 * Renders and verifies using the active CAPTCHA provider. When no provider is
 * active, the element renders nothing and is not required. Check for an active
 * provider (Omeka\CaptchaManager::getActive()) before adding this element.
 *
 * Get this element from the FormElementManager, or add it by type to a form
 * that came from the FormElementManager. A form created with "new" builds its
 * elements without their factories, so this element would have no provider.
 */
class Captcha extends Element implements InputProviderInterface
{
    /**
     * @var array
     */
    protected $attributes = [
        'type' => 'captcha',
        'name' => 'captcha',
    ];

    /**
     * @var ?CaptchaInterface
     */
    protected $captcha;

    /**
     * @var ?string The remote IP address
     */
    protected $remoteIp;

    public function setCaptcha(?CaptchaInterface $captcha)
    {
        $this->captcha = $captcha;
        if ($captcha) {
            parent::setName($captcha->getResponseName());
        }
        return $this;
    }

    public function getCaptcha(): ?CaptchaInterface
    {
        return $this->captcha;
    }

    public function setRemoteIp(?string $remoteIp)
    {
        $this->remoteIp = $remoteIp;
        return $this;
    }

    /**
     * Set the element name.
     *
     * The name must match the field the provider's widget submits, so it can't
     * be changed while a provider is set.
     */
    public function setName(string $name)
    {
        if ($this->captcha) {
            $name = $this->captcha->getResponseName();
        }
        return parent::setName($name);
    }

    public function getInputSpecification()
    {
        if (!$this->captcha) {
            return [
                'name' => $this->getName(),
                'required' => false,
            ];
        }
        return [
            'name' => $this->getName(),
            'required' => true,
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    // Don't ask the provider to verify an empty response.
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'You must verify that you are human by completing the CAPTCHA.', // @translate
                        ],
                    ],
                ],
                [
                    'name' => 'Callback',
                    'options' => [
                        'callback' => [$this, 'isValid'],
                        'messages' => [
                            'callbackValue' => 'Could not verify that you are a human.', // @translate
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Verify the CAPTCHA response with the provider.
     *
     * @param string $value
     * @return bool
     */
    public function isValid($value)
    {
        return $this->captcha->verify((string) $value, $this->remoteIp);
    }
}
