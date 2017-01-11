<?php
namespace Extended\ProjectBundle\Classes;

class MimeHelper 
{
    private $types = array(
        'internal' => array('default' => 'Внутренний документ', 'en' => 'Internal document'),
        'word' => array('default' => 'Документ Word 2010-2013', 'en' => 'Document Word 2010-2013'),
        'excel' => array('default' => 'Документ Excel 2010-2013', 'en' => 'Document Excel 2010-2013'),
        'powerpoint' => array('default' => 'Документ PowerPoint 2010-2013', 'en' => 'Document PowerPoint 2010-2013'),
        'image' => array('default' => 'Изображение (gif, jpeg, png, svg)', 'en' => 'Picture (gif, jpeg, png, svg)'),
        'video' => array('default' => 'Видео (mp4, ogg, webm, flv)', 'en' => 'Video (mp4, ogg, webm, flv)'),
        'audio' => array('default' => 'Аудио (mp3, aac, ogg, vorbis)', 'en' => 'Audio (mp3, aac, ogg, vorbis)'),
    );
    
    private $mimeTypes = array(
        'text/html' => 'internal',
        
        'application/msword' => 'word', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word', 
        'application/vnd.oasis.opendocument.text' => 'word',
        
        'application/vnd.ms-excel' => 'excel', 
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
        
        'application/vnd.ms-powerpoint' => 'powerpoint', 
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'powerpoint', 
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'powerpoint',
        
        'image/gif' => 'image',
        'image/jpeg' => 'image',
        'image/pjpeg' => 'image',
        'image/png' => 'image',
        'image/svg+xml' => 'image',
        'image/tiff' => 'image',
        'image/vnd.microsoft.icon' => 'image',
        'image/vnd.wap.wbmp' => 'image',
        'image/webp' => 'image',
        
        'video/mpeg' => 'video',
        'video/mp4' => 'video',
        'video/ogg' => 'video',
        'video/quicktime' => 'video',
        'video/webm' => 'video',
        'video/x-ms-wmv' => 'video',
        'video/x-flv' => 'video',
        'video/3gpp' => 'video',
        'video/3gpp2' => 'video',

        'audio/mp4' => 'audio',
        'audio/aac' => 'audio',
        'audio/mpeg' => 'audio',
        'audio/ogg' => 'audio',
        'audio/vorbis' => 'audio',
        'audio/vnd.wave' => 'audio',
        'audio/webm' => 'audio',
        
    );
    
    private $locale = null;
    
    public function __construct($defaultLocale = null)
    {
        $this->locale = $defaultLocale;
    }
    
    private function getLocaleString($names, $locale)
    {
        if (($locale == null) || !isset($names[$locale])) {
            return $names['default'];
        }
        return $names[$locale];
    }

    public function getTypeName($type, $locale = null)
    {
        if (!isset($this->types[$type])) {
            return '';
        }
        if ($locale == null) {
            $locale = $this->locale;
        }
        return $this->getLocaleString($this->types[$type], $locale);
    }
    
    public function getTypeByMime($mime)
    {
        if (isset($this->mimeTypes[$mime])) {
            return $this->mimeTypes[$mime];
        }
        return '';
    }
    
    public function getTypeNameByMime($mime, $locale = null)
    {
        $type = $this->getTypeByMime($mime);
        return $this->getTypeName($type, $locale);
    }
    
    public function getMimeByType($type)
    {
        return array_keys($this->mimeTypes, $type);
    }
    
    public function getTypes($locale = null)
    {
        if ($locale == null) {
            $locale = $this->locale;
        }
        $types = array();
        foreach ($this->types as $key=>$val) {
            $types[$key] = $this->getLocaleString($val, $locale);
        }
        return $types;
    }

}