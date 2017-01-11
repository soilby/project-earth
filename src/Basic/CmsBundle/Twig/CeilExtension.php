<?php
namespace Basic\CmsBundle\Twig;

class CeilExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            'round' => new \Twig_Filter_Method($this, 'roundFilter'),
            'ceil' => new \Twig_Filter_Method($this, 'ceilFilter'),
            'floor' => new \Twig_Filter_Method($this, 'floorFilter'),
            'md5' => new \Twig_Filter_Method($this, 'md5Filter'),
            'textlimit' => new \Twig_Filter_Method($this, 'textLimitFilter'),
        );
    }
    
    public function getTests()
    {
        return array(
            'equal' => new \Twig_Test_Method($this, 'equalTest'),
        );
    }
    
    public function textLimitFilter($text, $symbcount = 100, $endtext = '...', $delimiter = ' .,-')
    {
        if (strlen($text) < $symbcount) return $text;
        $text = substr($text, 0, $symbcount);
        if ($delimiter != '')
        {
            $position = 0;
            for ($i = 0; $i < strlen($delimiter); $i++)
            {
                if ((strrpos($text, substr($delimiter, $i, 1)) !== false) && (strrpos($text, substr($delimiter, $i, 1)) > $position))
                {
                    $position = strrpos($text, substr($delimiter, $i, 1));
                }
            }
            if ($position != 0) $text = substr($text, 0, $position);
        }
        if ($text != '') $text = $text.$endtext;
        return $text;
    }

    public function roundFilter($number, $base = 1)
    {
        if ($base <= 0) $base = 1;
        if ($base != 1) $round = round($number / $base) * $base; else $round = round($number);
        return $round;
    }
    
    public function ceilFilter($number, $base = 1)
    {
        if ($base <= 0) $base = 1;
        if ($base != 1) $ceil = ceil($number / $base) * $base; else $ceil = ceil($number);
        return $ceil;
    }

    public function floorFilter($number, $base = 1)
    {
        if ($base <= 0) $base = 1;
        if ($base != 1) $floor = floor($number / $base) * $base; else $floor = floor($number);
        return $floor;
    }

    public function md5Filter($string)
    {
        return md5($string);
    }
    
    public function equalTest($var1, $var2)
    {
        return $var1 === $var2;
    }
    
    
    public function getName()
    {
        return 'ceil_extension';
    }
}
