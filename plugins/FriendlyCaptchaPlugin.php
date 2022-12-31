<?php
/**
 * FriendlyCaptchaPlugin for phplist.
 *
 * This file is a part of FriendlyCaptchaPlugin.
 *
 * @author    Marc Philipp
 * @author    Duncan Cameron
 * @copyright 2022 Marc Philipp
 * @copyright 2022 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 *
 * @see       https://docs.friendlycaptcha.com/
 */
use phpList\plugin\Common\FrontendTranslator;

/**
 * This class registers the plugin with phplist and hooks into the display and validation
 * of subscribe pages.
 */
class FriendlyCaptchaPlugin extends phplistPlugin
{
    /** @var string the name of the version file */
    const VERSION_FILE = 'version.txt';

    /** @var string the site key */
    private $siteKey;

    /** @var string the API key */
    private $apiKey;

    /** @var bool whether the site and API keys have been entered */
    private $keysEntered;

    /*
     *  Inherited from phplistPlugin
     */
    public $name = 'Friendly Captcha Plugin';
    public $description = 'Adds an Friendly Captcha field to subscribe forms';
    public $documentationUrl = 'https://resources.phplist.com/plugin/friendly-captcha';
    public $authors = 'Marc Philipp, Duncan Cameron';
    public $coderoot;

    /**
     * Derive the language code from the subscribe page language file name.
     *
     * @see https://docs.friendlycaptcha.com/#/widget_api?id=data-lang-attribute
     *
     * @param string $languageFile the language file name
     *
     * @return string the language code, or 'en' when it cannot be derived
     */
    private function languageCode($languageFile)
    {
        $fileToCode = array(
            'bulgarian.inc' => 'bg',
            'catalan.inc' => 'ca',
            'croatian.inc' => 'hr',
            'czech.inc' => 'cs',
            'danish.inc' => 'da',
            'dutch.inc' => 'nl',
            'english-gaelic.inc' => 'en',
            'english.inc' => 'en',
            'english-usa.inc' => 'en',
            'estonian.inc' => 'et',
            'finnish.inc' => 'fi',
            'french.inc' => 'fr',
            'german.inc' => 'de',
            'greek.inc' => 'el',
            'hungarian.inc' => 'hu',
            'italian.inc' => 'it',
            'japanese.inc' => 'ja',
            'latinamerican.inc' => 'es',
            'norwegian.inc' => 'no',
            'polish.inc' => 'pl',
            'portuguese.inc' => 'pt',
            'portuguese_pt.inc' => 'pt',
            'romanian.inc' => 'ro',
            'russian.inc' => 'ru',
            'serbian.inc' => 'sr',
            'slovenian.inc' => 'sl',
            'spanish.inc' => 'es',
            'swedish.inc' => 'sv',
            'swissgerman.inc' => 'de',
            'tchinese.inc' => 'zh-TW',
            'ukrainian.inc' => 'uk',
            'usa.inc' => 'en',
            'vietnamese.inc' => 'vi',
        );

        return isset($fileToCode[$languageFile]) ? $fileToCode[$languageFile] : 'en';
    }

    /**
     * Class constructor.
     * Initialises some dynamic variables.
     */
    public function __construct()
    {
        $this->coderoot = __DIR__ . '/' . __CLASS__ . '/';

        parent::__construct();

        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
    }

    /**
     * Provide the dependencies for enabling this plugin.
     *
     * @return array
     */
    public function dependencyCheck()
    {
        global $plugins;

        return array(
            'Common Plugin v3.7.17 or later installed' => (
                phpListPlugin::isEnabled('CommonPlugin')
                && version_compare($plugins['CommonPlugin']->version, '3.7.17') >= 0
            ),
            'phpList version 3.3.0 or later' => version_compare(VERSION, '3.3') > 0,
            'curl extension enabled' => extension_loaded('curl'),
        );
    }

    /**
     * Cache the plugin's config settings.
     * Friendly Captcha will be used only when both the site key and API key have
     * been entered.
     */
    public function activate()
    {
        $this->settings = array(
            'friendlycaptcha_sitekey' => array(
                'description' => s('Friendly Captcha site key'),
                'type' => 'text',
                'value' => '',
                'allowempty' => false,
                'category' => 'FriendlyCaptcha',
            ),
            'friendlycaptcha_apikey' => array(
                'description' => s('Friendly Captcha API key'),
                'type' => 'text',
                'value' => '',
                'allowempty' => false,
                'category' => 'FriendlyCaptcha',
            ),
        );

        parent::activate();

        $this->siteKey = getConfig('friendlycaptcha_sitekey');
        $this->apiKey = getConfig('friendlycaptcha_apikey');
        $this->keysEntered = $this->siteKey !== '' && $this->apiKey !== '';
    }

    /**
     * Provide the Friendly Captcha html to be included in a subscription page.
     *
     * @param array $pageData subscribe page fields
     * @param int   $userId   user id
     *
     * @return string
     */
    public function displaySubscriptionChoice($pageData, $userID = 0)
    {
        if (empty($pageData['friendlycaptcha_include'])) {
            return '';
        }

        if (!$this->keysEntered) {
            return '';
        }

        $languageCode = 'en';
        if (isset($pageData['language_file'])) {
            $languageCode = $this->languageCode($pageData['language_file']);
        }

        $theme = empty($pageData['friendlycaptcha_dark_mode']) ? '' : 'dark';

        $format = <<<'END'
<div class="frc-captcha %s" data-sitekey="%s" data-lang="%s" data-callback="friendlyCaptchaCallback"></div>
<script type="module" src="https://unpkg.com/friendly-challenge@0.9.9/widget.module.min.js" async defer></script>
<script nomodule src="https://unpkg.com/friendly-challenge@0.9.9/widget.min.js" async defer></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementsByName("subscribe")[0].disabled = "disabled";
    }, false);

    function friendlyCaptchaCallback(solution) {
        document.getElementsByName("subscribe")[0].disabled = null;
    }
</script>
END;

        return sprintf($format, $theme, $this->siteKey, $languageCode);
    }

    /**
     * Provide additional validation when a subscribe page has been submitted.
     *
     * @param array $pageData subscribe page fields
     *
     * @return string an error message to be displayed or an empty string
     *                when validation is successful
     */
    public function validateSubscriptionPage($pageData)
    {
        if (empty($pageData['friendlycaptcha_include'])) {
            return '';
        }

        if ($_GET['p'] == 'asubscribe' && !empty($pageData['friendlycaptcha_not_asubscribe'])) {
            return '';
        }

        if (!$this->keysEntered) {
            return '';
        }

        if (empty($_POST['frc-captcha-solution'])) {
            $translator = new FrontendTranslator($pageData, $this->coderoot);

            return $translator->s('Please complete the Friendly Captcha');
        }

        $data = [
            'solution' => $_POST['frc-captcha-solution'],
            'secret' => $this->apiKey,
            'sitekey' => $this->siteKey,
        ];
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://api.friendlycaptcha.com/api/v1/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
        $responseData = json_decode($response);

        return $responseData->success ? '' : implode(', ', $responseData->{'errors'});
    }

    /**
     * Provide html for the Friendly Captcha options when editing a subscribe page.
     *
     * @param array $pageData subscribe page fields
     *
     * @return string additional html
     */
    public function displaySubscribepageEdit($pageData)
    {
        $include = isset($pageData['friendlycaptcha_include']) ? (bool) $pageData['friendlycaptcha_include'] : false;
        $notAsubscribe = isset($pageData['friendlycaptcha_not_asubscribe']) ? (bool) $pageData['friendlycaptcha_not_asubscribe'] : true;
        $darkMode = isset($pageData['friendlycaptcha_dark_mode']) ? (bool) $pageData['friendlycaptcha_dark_mode'] : false;
        $html =
            CHtml::label(s('Include Friendly Captcha in the subscribe page'), 'friendlycaptcha_include')
            . CHtml::checkBox('friendlycaptcha_include', $include, array('value' => 1, 'uncheckValue' => 0))
            . '<p></p>'
            . CHtml::label(s('Do not validate Friendly Captcha for asubscribe'), 'friendlycaptcha_not_asubscribe')
            . CHtml::checkBox('friendlycaptcha_not_asubscribe', $notAsubscribe, array('value' => 1, 'uncheckValue' => 0))
            . CHtml::label(s('Enable dark mode'), 'friendlycaptcha_dark_mode')
            . CHtml::checkBox('friendlycaptcha_dark_mode', $darkMode, array('value' => 1, 'uncheckValue' => 0));

        return $html;
    }

    /**
     * Save the Friendly Captcha settings.
     *
     * @param int $id subscribe page id
     */
    public function processSubscribePageEdit($id)
    {
        global $tables;

        Sql_Query(
            sprintf('
                REPLACE INTO %s
                (id, name, data)
                VALUES
                (%d, "friendlycaptcha_include", "%s"),
                (%d, "friendlycaptcha_not_asubscribe", "%s"),
                (%d, "friendlycaptcha_dark_mode", "%s")
                ',
                $tables['subscribepage_data'],
                $id,
                $_POST['friendlycaptcha_include'],
                $id,
                $_POST['friendlycaptcha_not_asubscribe'],
                $id,
                $_POST['friendlycaptcha_dark_mode']
            )
        );
    }
}
