<?php

namespace Extended\ProjectBundle\HTTP;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request {
    
    protected $request;
    protected $path;

    public function __construct(SymfonyRequest $request, $path) 
    {
        $this->request = $request;
        if (substr($path, 0, 1) != '/') {
            $path = '/'.$path;
        }
        $this->path = $path;
    }

    public function getHeader($name) 
    {
        return $this->request->headers->get($name);
    }

    public function getHeaders() 
    {
        return $this->request->headers->all();
    }

    public function getMethod() 
    {
        return $this->request->getMethod();
    }

    public function getUri() 
    {
        return $this->path;
    }

    public function getAbsoluteUri() 
    {
        return $this->request->getSchemeAndHttpHost().$this->path;
    }

    public function getQueryString() 
    {
        return $this->request->getQueryString();
    }

    public function getBody($asString = false) 
    {
        return $this->request->getContent(!$asString);
    }

    public function setBody($body,$setAsDefaultInputStream = false) 
    {
    }

    public function getPostVars() 
    {
        return $this->request->request->all();
    }

    public function getRawServerValue($field) 
    {
        return $this->request->server->get($field);
    }

    public function getHTTPVersion() 
    {
        $protocol = $this->getRawServerValue('SERVER_PROTOCOL');
        if ($protocol==='HTTP/1.0') {
            return '1.0';
        } else {
            return '1.1';
        }
    }
    
}

