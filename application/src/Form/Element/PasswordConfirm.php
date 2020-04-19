<?php
namespace Omeka\Form\Element;

use Laminas\Form\Fieldset;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\InputFilter\InputFilterProviderInterface;

class PasswordConfirm extends Fieldset implements InputFilterProviderInterface
{
    /**
     * @var array
     */
    protected $passwordConfig = [];

    public function init()
    {
        $config = $this->getPasswordConfig();
        $translator = $this->getTranslator();

        $requirements = [];
        if (isset($config['min_length']) && is_numeric($config['min_length'])) {
            $requirements[] = sprintf($translator->translate('be a minimum of %s characters in length.'),
                $config['min_length']);
        }
        if (isset($config['min_lowercase']) && is_numeric($config['min_lowercase'])) {
            $requirements[] = sprintf($translator->translate('contain at least %s lowercase characters.'),
                $config['min_lowercase']);
        }
        if (isset($config['min_uppercase']) && is_numeric($config['min_uppercase'])) {
            $requirements[] = sprintf($translator->translate('contain at least %s uppercase characters.'),
                $config['min_uppercase']);
        }
        if (isset($config['min_number']) && is_numeric($config['min_number'])) {
            $requirements[] = sprintf($translator->translate('contain at least %s numbers.'),
                $config['min_number']);
        }
        if (isset($config['min_symbol']) && is_numeric($config['min_symbol'])
            && isset($config['symbol_list']) && is_string($config['symbol_list'])
            && strlen($config['symbol_list'])
        ) {
            $requirements[] = sprintf($translator->translate('contain at least %1$s symbols: %2$s'),
                $config['min_symbol'], $config['symbol_list']);
        }

        $requirementsHtml = $translator->translate('Password must:');
        $requirementsHtml .= '<ul>';
        foreach ($requirements as $requirement) {
            $requirementsHtml .= '<li>' . $requirement . '</li>';
        }
        $requirementsHtml .= '</ul>';

        $this->add([
            'name' => 'password',
            'type' => 'Password',
            'options' => [
                'label' => 'Password', // @translate
                'info' => $requirementsHtml,
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'password',
            ],
        ]);
        $this->add([
            'name' => 'password-confirm',
            'type' => 'Password',
            'options' => [
                'label' => 'Confirm password', // @translate
            ],
            'attributes' => [
                'id' => 'password-confirm',
            ],
        ]);
    }

    public function getInputFilterSpecification()
    {
        $isRequired = (bool) $this->getOption('password_confirm_is_required');
        return [
            [
                'name' => 'password',
                'required' => $isRequired,
                'validators' => [
                    [
                        'name' => 'Callback',
                        'options' => [
                            'message' => 'The password is invalid', // @translate
                            'callback' => [$this, 'passwordIsValid'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'password-confirm',
                'required' => $isRequired,
                'validators' => [
                    [
                        'name' => 'Identical',
                        'options' => [
                            'token' => 'password',
                            'messages' => [
                                'notSame' => 'The passwords did not match', // @translate
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Set this fieldset's labels.
     *
     * @param string $passwordLabel
     * @param string $passwordConfirmLabel
     * @return self
     */
    public function setLabels($passwordLabel, $passwordConfirmLabel)
    {
        $this->get('password')->setLabel($passwordLabel);
        $this->get('password-confirm')->setLabel($passwordConfirmLabel);
        return $this;
    }

    /**
     * Set whether this fieldset is required.
     *
     * Sets the "required" attribute on the password input tags and sets the
     * "password_confirm_is_required" option that sets the "required" flag on
     * the input filter.
     *
     * @param bool $isRequired
     * @return self
     */
    public function setIsRequired($isRequired)
    {
        $isRequired = (bool) $isRequired;
        $this->get('password')->setAttribute('required', $isRequired);
        $this->get('password-confirm')->setAttribute('required', $isRequired);
        $this->setOption('password_confirm_is_required', $isRequired);
        return $this;
    }

    /**
     * Check whether a password is valid.
     *
     * @param string $password
     * @return bool
     */
    public function passwordIsValid($password)
    {
        $config = $this->getPasswordConfig();

        /**
         * Count lowercase characters, including multibyte characters.
         *
         * @param string $string
         */
        $countLowercase = function ($string) {
            $stringUppercase = mb_strtoupper($string);
            $similar = similar_text($string, $stringUppercase);
            return strlen($string) - $similar;
        };
        /**
         * Count uppercase characters, including multibyte characters.
         *
         * @param string $string
         */
        $countUppercase = function ($string) {
            $stringLowercase = mb_strtolower($string);
            $similar = similar_text($string, $stringLowercase);
            return strlen($string) - $similar;
        };

        // Validate minimum password length.
        if (isset($config['min_length']) && is_numeric($config['min_length'])) {
            if (strlen($password) < $config['min_length']) {
                return false;
            }
        }
        // Validate minimum lowercase character count.
        if (isset($config['min_lowercase']) && is_numeric($config['min_lowercase'])) {
            if ($countLowercase($password) < $config['min_lowercase']) {
                return false;
            }
        }
        // Validate minimum uppercase character count.
        if (isset($config['min_uppercase']) && is_numeric($config['min_uppercase'])) {
            if ($countUppercase($password) < $config['min_uppercase']) {
                return false;
            }
        }
        // Validate minimum number character count.
        if (isset($config['min_number']) && is_numeric($config['min_number'])) {
            if (preg_match_all('/[0-9]/', $password) < $config['min_number']) {
                return false;
            }
        }
        // Validate minimum symbol character count.
        if (isset($config['min_symbol']) && is_numeric($config['min_symbol'])
            && isset($config['symbol_list']) && is_string($config['symbol_list'])
            && strlen($config['symbol_list'])
        ) {
            $symbolCount = 0;
            foreach (str_split($config['symbol_list']) as $symbol) {
                $symbolCount += substr_count($password, $symbol);
            }
            if ($symbolCount < $config['min_symbol']) {
                return false;
            }
        }

        // The password is valid.
        return true;
    }

    /**
     * @param array $passwordConfig
     */
    public function setPasswordConfig(array $passwordConfig)
    {
        $this->passwordConfig = $passwordConfig;
    }

    /**
     * @return array
     */
    public function getPasswordConfig()
    {
        return $this->passwordConfig;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator()
    {
        return $this->translator;
    }
}
