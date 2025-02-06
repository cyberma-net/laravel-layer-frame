<?php

namespace Cyberma\LayerFrame\Languages;

use Cyberma\LayerFrame\Exceptions\CodeException;
use Cyberma\LayerFrame\Exceptions\Exception;

class Language
{

    private $envLang = 'en_US';   //environment language set by Gettext plugin

    private $dataLang = 'en_US';  //language for data stream - API

    private $fallbackLang = 'en_US';   // which language to user, if the value doesn't exist in the desired language

    private $nullValue = null;   //what should be returned, if the value doesn't exist in the fallback language  null or '' are mappropriate values

    private $fallbackValue = null;   //value that can be used instead of fallback language;  e.g.  __  to highlight the missing entry

    private $allLangs = false;

    private array $supportedLanguages;


    public function __construct(array $supportedLanguages = ['en_US'])
    {
        $this->supportedLanguages = $supportedLanguages;
    }


    /**
     * @param $attr
     * @return mixed
     * @throws CodeException
     */
    public function __get($attr)
    {
        switch($attr) {

            case 'envLang' : return $this->envLang; break;
            case 'fallbackLang' : return $this->fallbackLang; break;
            case 'fallbackValue' : return $this->fallbackValue; break;
            case 'nullValue' : return $this->nullValue; break;
            case 'dataStreamLang' :
            case 'dataLang' : return $this->dataLang; break;
            case 'allLangs' : return $this->allLangs; break;

            default :
                throw new CodeException('Requested attribute $'. $attr . ' does not exist in the class: ' . self::class, 'lf2113',  ['class' => static::class]) ;
        }
    }


    public function setEnvLang (string $lang)
    {
        $this->envLang = in_array($lang, $this->supportedLanguages )
            ? $lang
            : 'en_US';
    }


    public function getEnvLang() : string
    {
        return $this->envLang;
    }


    public function getDataStreamLanguage(): string
    {
        return $this->dataLang;
    }

    /**
     * @param string $dataStreamLanguage
     * @param null $fallbackLanguage
     * @throws Exception
     */
    public function setDataStreamLanguage(string $dataStreamLanguage, $fallbackLanguage = null) : void
    {
        if(!in_array($dataStreamLanguage, $this->supportedLanguages )) {
            $this->dataLang = 'en_US';
            throw new Exception(_('Unsupported language was requested.'), 'lf2109', [], 400);
        }

        if(in_array($fallbackLanguage, $this->supportedLanguages )) {
            $this->fallbackLang = $fallbackLanguage;
        }

        $this->dataLang = $dataStreamLanguage;
    }

    /**
     * @return null
     */
    public function getFallbackLanguage()
    {
        return $this->fallbackLang;
    }

    /**
     * @param null $fallbackLanguage
     */
    public function setFallbackLanguage(string $fallbackLanguage): void
    {
        $this->fallbackLang = $fallbackLanguage;
    }

    /**
     * @param string|array $attribute
     * @return null|string|array
     */
    public function extractLSAttribute($attribute)
    {
        if(!is_array($attribute))
            return $attribute;

        if($this->allLangs)
            return $attribute;

        if(array_key_exists($this->dataLang, $attribute))
            return $attribute[$this->dataLang];

        if(!is_null($this->fallbackValue)) {
            return $this->fallbackValue;
        }

        if(array_key_exists($this->fallbackLang, $attribute)) {
            return empty($attribute[$this->fallbackLang])
                ? $this->nullValue
                : $attribute[$this->fallbackLang];
        }

        return $this->nullValue;
    }

    /**
     * @param null $nullValue
     */
    public function setNullValue($nullValue): void
    {
        $this->nullValue = $nullValue;
    }

    /**
     * @param $value
     */
    public function setFallbackValue($value): void
    {
        $this->fallbackValue = $value;
    }

    /**
     * @param $value
     */
    public function setAllLangs($value): void
    {
        $this->allLangs = $value;
    }


    public function toDBArray() : string
    {
         return $this->dataLang;
    }
}
