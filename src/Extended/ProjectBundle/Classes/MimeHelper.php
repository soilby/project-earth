<?php
namespace Extended\ProjectBundle\Classes;

class MimeHelper
{
    private $types = array(
        'internal' => array('default' => 'Внутренний документ', 'en' => 'Internal document'),
        'googledoc' => array('default' => 'Документ Google Docs', 'en' => 'Document Google Docs'),
        'googlesheets' => array('default' => 'Документ Google Sheets', 'en' => 'Document Google Sheets'),
        'googleslides' => array('default' => 'Документ Google Slides', 'en' => 'Document Google Slides'),
        'word' => array('default' => 'Документ Word 2010-2013', 'en' => 'Document Word 2010-2013'),
        'excel' => array('default' => 'Документ Excel 2010-2013', 'en' => 'Document Excel 2010-2013'),
        'powerpoint' => array('default' => 'Документ PowerPoint 2010-2013', 'en' => 'Document PowerPoint 2010-2013'),
        'image' => array('default' => 'Изображение {{(gif, jpeg, png, svg)}}', 'en' => 'Picture {{(gif, jpeg, png, svg)}}'),
        'video' => array('default' => 'Видео {{(mp4, ogg, webm, flv)}}', 'en' => 'Video {{(mp4, ogg, webm, flv)}}'),
        'audio' => array('default' => 'Аудио {{(mp3, aac, ogg, vorbis)}}', 'en' => 'Audio {{(mp3, aac, ogg, vorbis)}}'),
        'archive' => array('default' => 'Архив {{(zip, rar, tar)}}', 'en' => 'Archive {{(zip, rar, tar)}}'),
    );

    private $mimeTypes = array(
        'text/html' => 'internal',

        'application/vnd.google-apps.document' => 'googledoc',
        'application/vnd.google-apps.spreadsheet' => 'googlesheets',
        'application/vnd.google-apps.presentation' => 'googleslides',

        'application/msword' => 'word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        'application/vnd.oasis.opendocument.text' => 'word',

        'application/vnd.ms-excel' => 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',

        'application/vnd.ms-powerpoint' => 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'powerpoint',

        'image/gif' => 'image.gif',
        'image/jpeg' => 'image.jpg',
        'image/pjpeg' => 'image.jpg',
        'image/png' => 'image.png',
        'image/svg+xml' => 'image.svg',
        'image/tiff' => 'image.tiff',
        'image/vnd.microsoft.icon' => 'image.ico',
        'image/vnd.wap.wbmp' => 'image.bmp',
        'image/webp' => 'image.webp',

        'video/mpeg' => 'video.mpg',
        'video/mp4' => 'video.mp4',
        'video/ogg' => 'video.ogg',
        'video/quicktime' => 'video.quicktime',
        'video/webm' => 'video.webm',
        'video/x-ms-wmv' => 'video.wmv',
        'video/x-flv' => 'video.flv',
        'video/3gpp' => 'video.3gp',
        'video/3gpp2' => 'video.3gp',

        'audio/mp4' => 'audio.mp4',
        'audio/aac' => 'audio.aac',
        'audio/mpeg' => 'audio.mpg',
        'audio/ogg' => 'audio.ogg',
        'audio/vorbis' => 'audio.vorbis',
        'audio/vnd.wave' => 'audio.wav',
        'audio/webm' => 'audio.webm',

        'application/zip' => 'archive.zip',
        'application/gzip' => 'archive.zip',
        'application/x-rar-compressed' => 'archive.rar',
        'application/tar' => 'archive.tar',
        'application/tar+gzip' => 'archive.tar+gz',
        'application/x-gzip' => 'archive.zip',
        'application/x-gtar' => 'archive.tar',
        'application/x-tgz' => 'archive.tar+gz',

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

    public function getTypeName($type, $locale = null, $raw = false)
    {
        if (!isset($this->types[$type])) {
            return '';
        }
        if ($locale == null) {
            $locale = $this->locale;
        }
        $name = $this->getLocaleString($this->types[$type], $locale);
        if ($raw == false) {
            $name = strtr($name, array('{{' => '', '}}' => ''));
        }
        return $name;
    }

    public function getTypeByMime($mime)
    {
        if (isset($this->mimeTypes[$mime])) {
            $typename = $this->mimeTypes[$mime];
            if (strpos($this->mimeTypes[$mime], '.') !== false) {
                $typename = substr($typename, 0, strpos($typename, '.'));
            }
            return $typename;
        }
        return '';
    }

    public function getTypeNameByMime($mime, $locale = null)
    {

        if (isset($this->mimeTypes[$mime])) {
            $typename = $this->mimeTypes[$mime];
            $subtype = '';
            if (strpos($this->mimeTypes[$mime], '.') !== false) {
                $subtype = substr($typename, strpos($typename, '.') + 1);
                $typename = substr($typename, 0, strpos($typename, '.'));
            }
            $type = $this->getTypeName($typename, $locale, true);
            if ($subtype == '') {
                $type = strtr($type, array('{{' => '', '}}' => ''));
            } else {
                $type = preg_replace('/{{[^}]+}}/u', $subtype, $type);
            }
            return $type;
        }
        return '';
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
            $types[$key] = strtr($this->getLocaleString($val, $locale), array('{{' => '', '}}' => ''));
        }
        return $types;
    }

    public function isUploadedType($type)
    {
        return !in_array($type, array('internal', 'googledoc', 'googlesheets', 'googleslides'));
    }

}