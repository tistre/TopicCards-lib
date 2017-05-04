<?php

namespace TopicCards\Model;


trait LanguageTrait
{
    protected $language = false;


    public function getLanguage()
    {
        return $this->language;
    }


    public function setLanguage($language)
    {
        $this->language = $language;
        
        return 1;
    }


    public function getAllLanguage()
    {
        return
            [
                'language' => $this->getLanguage()
            ];
    }


    public function setAllLanguage(array $data)
    {
        $data = array_merge(
            [
                'language' => false
            ], $data);

        return $this->setLanguage($data['language']);
    }
}
