<?php

namespace TopicCards\Model;


trait LanguageTrait
{
    /** @var string */
    protected $language = '';


    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * @param string $language
     * @return self
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        
        return $this;
    }


    /**
     * @return array
     */
    public function getAllLanguage()
    {
        return
            [
                'language' => $this->getLanguage()
            ];
    }


    /**
     * @param array $data
     * @return self
     */
    public function setAllLanguage(array $data)
    {
        $data = array_merge(
            [
                'language' => false
            ], $data);

        $this->setLanguage($data['language']);
        
        return $this;
    }
}
