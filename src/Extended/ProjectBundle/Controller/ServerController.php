<?php

namespace Extended\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sabre\DAV as OriginalDAV;
use Extended\ProjectBundle\DAV as DAV;

class ServerController extends Controller
{
    public function execAction(\Symfony\Component\HttpFoundation\Request $request, $path)
    {
        // Now we're creating a whole bunch of objects
        $rootDirectory = new \Extended\ProjectBundle\FS\Directory('', $this->getDoctrine()->getEntityManager());

        // The server object is responsible for making sense out of the WebDAV protocol
        $server = new DAV\Server($rootDirectory, $request, $path);

        // If your server is not on your webroot, make sure the following line has the
        // correct information
        //$server->setBaseUri('/url/to/server.php');

        // The lock manager is reponsible for making sure users don't overwrite
        // each others changes.
        $lockBackend = new OriginalDAV\Locks\Backend\File('secured/projectfiles/locks');
        $lockPlugin = new OriginalDAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // This ensures that we get a pretty index in the browser, but it is
        // optional.
        $server->addPlugin(new OriginalDAV\Browser\Plugin());

        // All we need to do now, is to fire up the server
        return $server->exec();
    }
}
