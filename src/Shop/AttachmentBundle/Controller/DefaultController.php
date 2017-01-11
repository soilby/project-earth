<?php

namespace Shop\AttachmentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
// *******************************************
// AJAX загрузка файла
// *******************************************    

    public function attachmentAjaxAddAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
         $query = $em->createQuery('SELECT a.id, a.attachment FROM ShopAttachmentBundle:ProductAttachment a WHERE a.productId IS NULL AND a.uploadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
         $files = $query->getResult();
         if (is_array($files))
         {
             $removeids = array();
             foreach ($files as $file)
             {
                 @unlink('..'.$file['attachment']);
                 $removeids[] = $file['id'];
             }
             if (count($removeids) > 0)
             {
                 $query = $em->createQuery('DELETE FROM ShopAttachmentBundle:ProductAttachment a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                 $query->execute();
             }
         }
         unset($files);
        $userId = $this->getUser()->getId();
        $file = $this->getRequest()->files->get('attachment');
        $tmpfile = $file->getPathName();
        $source = "";
        $basepath = '../secured/productattachment/';
        $name = $this->getUser()->getId().'_'.md5(time()).'.dat';
        if (!move_uploaded_file($tmpfile, $basepath . $name)) return new Response(json_encode(array('fileid' => '', 'filename' => '', 'error' => 'Ошибка добавления файла')));
        $attachent = new \Shop\AttachmentBundle\Entity\ProductAttachment();
        $attachent->setAttachment('/secured/productattachment/'.$name);
        $attachent->setEnabled(0);
        $attachent->setFileName($file->getClientOriginalName());
        $attachent->setOrdering(0);
        $attachent->setProductId(null);
        $attachent->setUploadDate(new \DateTime('now'));
        $em->persist($attachent);
        $em->flush();
        return new Response(json_encode(array('fileid' => $attachent->getId(), 'filename' => $attachent->getFileName(), 'error' => '')));
    }
    
// *******************************************
// AJAX удаление файла
// *******************************************    
    
    public function attachmentAjaxRemoveAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $attachent = $this->getDoctrine()->getRepository('ShopAttachmentBundle:ProductAttachment')->find($id);
        if (empty($attachent)) return new Response('Ошибка удаления');
        @unlink('..'.$attachent->getAttachment());
        $em->remove($attachent);
        $em->flush();
        return new Response('');
    }

// *******************************************
// Скачивание файла
// *******************************************    
    
    public function attachmentDownloadAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $attachent = $this->getDoctrine()->getRepository('ShopAttachmentBundle:ProductAttachment')->find($id);
        if (empty($attachent)) return new Response('Page not found', 404);
        if (!file_exists('..'.$attachent->getAttachment())) return new Response('');
        $resp = new Response();
        $resp->headers->set('Content-Type','application/octet-stream');
        $resp->headers->set('Last-Modified',gmdate('r', filemtime('..'.$attachent->getAttachment())));
        $resp->headers->set('Content-Length',filesize('..'.$attachent->getAttachment()));
        $resp->headers->set('Connection','close');
        if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE" ) > 0 )
            $resp->headers->set('Content-Disposition','attachment; filename="' . rawurlencode(basename($attachent->getFileName()))  . '";' );
        else
            $resp->headers->set('Content-Disposition','attachment; filename*=UTF-8\'\'' . rawurlencode(basename($attachent->getFileName())) .';');
        $resp->setContent(file_get_contents('..'.$attachent->getAttachment()));
        return $resp;
    }
    
}
