<?php

namespace App\Services;

use Google\Cloud\Translate\V2\TranslateClient;

class TranslateService
{
    private $database;
    /**
     * @var TranslateClient
     */
    private $text;

    public $translate;

    protected $languages;

    protected $translations=array();

    public function __construct()
    {
        $this->translate = new TranslateClient(array('key'=>config('translatable.google_translate_key')));
    }

    public function languages()
    {
        return $this->translate->languages();
    }

    public function detectLanguage($str)
    {
        $strLanguage = $this->translate->detectLanguage($str);
        return $strLanguage['languageCode'];
    }

    public function detectLanguageBatch(array $str)
    {
        return $this->translate->detectLanguageBatch($str);
    }

    public function translateBatch(array $str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        return $this->translate->translateBatch($str , $options);
    }

    public function translate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        if(isset($options['resource']))
        {
            $lang = strval($options['resource']);
        }else{
            $lang = $this->detectLanguage($str);
        }
        if(isset($options['target'])&&$lang==$options['target'])
        {
            return $str;
        }
        $translate = $this->translate->translate($str , $options);
        return $translate['text'];
    }

    public function pyChatTranslate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        $translate = $this->translate->translate($str , $options);
        return $translate['text'];
    }

    public function onlyTranslate(array $str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        return $this->translate->translateBatch($str , $options);
    }
}
