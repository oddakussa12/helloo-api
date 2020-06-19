<?php

namespace App\Services;

use Google\Cloud\Translate\V2\TranslateClient;

class TranslateService
{

    /**
     * @var TranslateClient
     */
    public $translate;

    /**
     * @var array
     */
    protected $translations=array();

    /**
     * TranslateService constructor.
     */
    public function __construct()
    {
        $this->translate = new TranslateClient(array('key'=>config('translatable.google_translate_key')));
    }


    /**
     * @return array
     */
    public function languages()
    {
        return $this->translate->languages();
    }

    /**
     * @param $str
     * @return mixed
     */
    public function detectLanguage($str)
    {
        try{
            $strLanguage = $this->translate->detectLanguage($str);
        }catch (\Exception $e)
        {
            $exception = \json_decode($e->getMessage());
            abort(417 , $exception->error->message);
        }
        return $strLanguage['languageCode'];
    }

    /**
     * @param array $str
     * @return array
     */
    public function detectLanguageBatch(array $str)
    {
        return $this->translate->detectLanguageBatch($str);
    }

    /**
     * @param array $str
     * @param array $option
     * @return array
     */
    public function translateBatch(array $str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        return $this->translate->translateBatch($str , $options);
    }

    /**
     * @param $str
     * @param array $option
     * @return mixed
     */
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

    /**
     * @param $str
     * @param array $option
     * @return mixed
     */
    public function pyChatTranslate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        $translate = $this->translate->translate($str , $options);
        return $translate['text'];
    }

    /**
     * @param array $str
     * @param array $option
     * @return array
     */
    public function onlyTranslate(array $str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        return $this->translate->translateBatch($str , $options);
    }
}
