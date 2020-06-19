<?php

namespace App\Services;

use Google\Cloud\Translate\V3\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextGlossaryConfig;

class V3TranslateService
{

    public $translate;

    protected $languages;

    private $formattedParent;

    protected $translations=array();

    public function __construct()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.config('common.google_application_credentials'));
        $this->translate = new TranslationServiceClient();
        $this->formattedParent = $this->translate->locationName(config('common.google_project_id'), config('common.google_location'));
    }

    public function languages()
    {
        return $this->translate->languages();
    }

    public function detectLanguage($str , $option=array())
    {
        $options = array('format'=>config('translatable.translate_V3_default_format'));
        $options = $option+$options;
        $detectLanguages = $this->translate->detectLanguage($this->formattedParent , [
            'content' => $str,
            'mimeType' => $options['format']
        ])->getLanguages();
        $languages = array();
        foreach ($detectLanguages as $language)
        {
            array_push($languages , array('confidence'=>$language->getConfidence() , 'code'=>$language->getLanguageCode()));
        }
        $confidences = array_column($languages,'confidence');
        array_multisort($confidences,SORT_DESC,$languages);
        $strLanguage = array_shift($languages);
        return $strLanguage['code'];
    }


    public function translate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_V3_default_format'));
        $options = $option+$options;
        if(isset($options['resource']))
        {
            $resource = strval($options['resource']);
        }else{
            $resource = $this->detectLanguage($str);
        }
        if(isset($options['target'])&&$resource==$options['target'])
        {
            return $str;
        }
        try {
            $glossaryPath = $this->translate->glossaryName(
                config('common.google_project_id'),
                config('common.google_location'),
                config('common.google_glossary_id')
            );
            $glossaryConfig = new TranslateTextGlossaryConfig();
            $glossaryConfig->setGlossary($glossaryPath);
            $response = $this->translate->translateText(
                [$str],
                $options['target'],
                $this->formattedParent,
                [
                    'sourceLanguageCode'=>$resource,
                    'glossaryConfig' => $glossaryConfig,
                    'mimeType' => $options['format']
                ]
            );
            $translates = array();
            $translations = $response->getTranslations();
            foreach ($translations as $translation) {
                array_push($translates , $translation->getTranslatedText());
            }
            $translate = array_shift($translates);
        } finally {
            $this->translate->close();
        }
        return $translate;
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
