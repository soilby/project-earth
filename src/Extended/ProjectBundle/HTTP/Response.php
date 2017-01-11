<?php

namespace Extended\ProjectBundle\HTTP;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response extends SymfonyResponse 
{
    
    protected $stream = null;

    public function getStatusMessage($code, $httpVersion = '1.1') 
    {
       return 'HTTP/'.$httpVersion.' '.$code.' '.SymfonyResponse::$statusTexts[$code];
    }

    public function sendStatus($code) 
    {
        $this->setStatusCode($code);
        return true;
    }

    public function setHeader($name, $value, $replace = true) 
    {
        $this->headers->set($name, $value, $replace);
        return true;
    }
    
    public function setHeaders(array $headers) 
    {
        $this->headers->add($headers);
    }

    public function sendBody($body) 
    {
        if (is_resource($body)) {
            $this->setContent('');
            $this->stream = $body;
        } else {
            $this->setContent($body);
            $this->stream = null;
        }
    }

    public function sendContent()
    {
        if ($this->stream != null) {
            $out = fopen('php://output', 'wb');
            while (!feof($this->stream)) {
                fwrite($out, fread($this->stream, 524288));
            }
            fclose($out);
        } else {
            parent::sendContent();
        }
    }
    
}
