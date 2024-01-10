<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @author      Peter Martin
 * @copyright   Copyright 2016-2024 Peter Martin. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @link        https://data2site.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Utilities\IpHelper;

/**
 * hCaptcha Plugin
 * Using the https://www.hcaptcha.com/ CAPTCHA service
 *
 * @since  1.0.0
 */
class PlgCaptchaHcaptcha extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Application object.
     *
     * @var    CMSApplication
     * @since  4.0.0
     */
    protected $app;

    /**
     * Reports the privacy related capabilities for this plugin to site administrators.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function onPrivacyCollectAdminCapabilities()
    {
        $this->loadLanguage();

        return [
            Text::_('PLG_CAPTCHA_HCAPTCHA') => [
                Text::_('PLG_CAPTCHA_HCAPTCHA_PRIVACY_CAPABILITY_IP_ADDRESS'),
            ]
        ];
    }

    /**
     * Initialise the captcha
     *
     * @return  boolean    True on success, false otherwise
     *
     * @throws  Exception
     * @since   1.0.0
     */
    public function onInit()
    {
        // If there is no Public Key set, then this plugin is no use, so exit
        if ($this->params->get('publicKey', '') === '') {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_NO_PUBLIC_KEY'));
        }

        // Load the JavaScript from hCaptcha
        $this->app->getDocument()->getWebAssetManager()
            ->registerAndUseScript('plg_captcha_hcaptcha.api', 'https://hcaptcha.com/1/api.js', [], ['defer' => true]);

        return true;
    }

    /**
     * Gets the challenge HTML
     *
     * @param string $name The name of the field. Not Used.
     * @param string $id The id of the field.
     * @param string $class The class of the field.
     *
     * @return  string  The HTML to be embedded in the form.
     * @throws  Exception
     * @since   1.0.0
     */
    public function onDisplay($name = null, $id = 'hcaptcha', $class = '')
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $ele = $dom->createElement('div');
        $ele->setAttribute('id', $id);
        $ele->setAttribute('class', 'h-captcha required');
        $ele->setAttribute('data-sitekey', $this->params->get('publicKey', ''));
        $ele->setAttribute('data-theme', $this->params->get('theme', 'light'));
        $ele->setAttribute('data-size', $this->params->get('size', 'normal'));

        $dom->appendChild($ele);

        return $dom->saveHTML($ele);
    }

    /**
     * Calls an HTTP POST function to verify if the user's guess was correct
     *
     * @param string $code Answer provided by user. Not needed for the Hcaptcha implementation
     *
     * @return  boolean
     * @throws  Exception
     * @since   1.0.0
     */
    public function onCheckAnswer($code = null)
    {
        $input = Factory::getApplication()->input;
        $privateKey = $this->params->get('privateKey');
        $remoteIp = IpHelper::getIp();
        $hCaptchaResponse = $code ?? $input->get('h-captcha-response', '', 'cmd');

        // Check for Private Key
        if (empty($privateKey)) {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_NO_PRIVATE_KEY'));
        }

        // Check for IP
        if (empty($remoteIp)) {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_NO_IP'));
        }

        if (empty($hCaptchaResponse)) {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_EMPTY_SOLUTION'));
        }

        return $this->getResponse($privateKey, $remoteIp, $hCaptchaResponse);
    }

    /**
     * Get the hCaptcha response.
     *
     * @param string $privateKey The private key for authentication.
     * @param string $remoteIp The remote IP of the visitor.
     * @param string $hCaptchaResponse The response received from Google.
     *
     * @return  bool True if response is good | False if response is bad.
     *
     * @throws  \RuntimeException
     * @since   1.4.0
     */
    private function getResponse(string $privateKey, string $remoteIp, string $hCaptchaResponse)
    {
        try {
            $verifyResponse = HttpFactory::getHttp()->get(
                'https://hcaptcha.com/siteverify?secret=' . $privateKey .
                '&response=' . $hCaptchaResponse .
                '&remoteip=' . $remoteIp
            );
        } catch (RuntimeException $e) {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_CANT_CONNECT_TO_HCAPTCHA_SERVERS'));
        }

        if ($verifyResponse->code !== 200 || $verifyResponse->body === '') {
            throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_INVALID_RESPONSE'));
        }

        $responseData = json_decode($verifyResponse->body);

        if ($responseData->success) {
            return true;
        }

        throw new \RuntimeException(Text::_('PLG_CAPTCHA_HCAPTCHA_ERROR_INCORRECT_CAPTCHA'));
    }
}
