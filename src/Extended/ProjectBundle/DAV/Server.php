<?php

namespace Extended\ProjectBundle\DAV;

use Sabre\DAV as OriginalDAV;
use Symfony\Component\HttpFoundation\Request;
use Extended\ProjectBundle\HTTP as HTTP;

class Server extends OriginalDAV\Server
{
    public function __construct($treeOrNode = null, Request $request, $path) 
    {
        parent::__construct($treeOrNode);
        $this->httpResponse = new HTTP\Response();
        $this->httpRequest = new HTTP\Request($request, $path);        
    }
    
    public function exec() 
    {
        parent::exec();
        return $this->httpResponse;
    }
    
    
    
}