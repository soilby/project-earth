<?php

namespace Extended\ChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
// *******************************************
// Фронт-контроллер скачивания
// *******************************************    
    
    public function downloadAttachmentAction() 
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $fileent = $this->getDoctrine()->getRepository('ExtendedChatBundle:ChatMessage')->find($id);
        if (empty($fileent)) return new Response('File not found', 404);
        // Проверить доступ к файлу
        $userid = $this->get('request_stack')->getMasterRequest()->getSession()->get('front_system_autorized');
        $userEntity = null;
        if ($userid != null) $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
        if (empty($userEntity))
        {
            $userid = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keytwo');
            $userpass = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keyone');
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
            $userEntity = $query->getResult();
            if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) $userEntity = $userEntity[0];
        }
        if (empty($userEntity)) return new Response('Access denied', 403);
        if ($userEntity->getBlocked() != 2) return new Response('Access denied', 403);
        $linkEnt = $this->getDoctrine()->getRepository('ExtendedChatBundle:ChatUser')->findOneBy(array('userId' => $userEntity->getId(), 'chatId' => $fileent->getChatId(), 'chatType' => $fileent->getChatType()));
        if (empty($linkEnt)) return new Response('Access denied', 403);
        // Выдать файл
        $filePath = '.'.$fileent->getAttachment();
        $newFileName = $fileent->getAttachmentFilename();
        if (is_file($filePath)) 
        {
            header("Content-type: ".$fileent->getAttachmentMimetype());
            $fp = fopen($filePath, 'rb');
            $size = filesize($filePath);
            $length = $size;
            $start = 0;
            $end = $size - 1;
            if (isset($_SERVER['HTTP_RANGE']))
            {
                $c_start = $start;
                $c_end = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) 
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    die;
                }
                if (substr($range, 0, 1) == '-')
                {
                    $c_start = $size - substr($range, 1);
                } else
                {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1]) ) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size)
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    die;
                }
                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
                header('ETag: "' . md5(microtime()) . '"');
                header("Content-Range: bytes $start-$end/$size");
            }
            header("Content-Length: $length");
            header('Connection: Close');
            if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE" ) > 0 ) header('Content-Disposition: attachment; filename="'.rawurlencode(basename($newFileName)).'";');
            else header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode(basename($newFileName)).';');

            $buffer = 1024 * 512;
            while (!feof($fp) && ($p = ftell($fp)) <= $end)
            {
                if ($p + $buffer > $end)
                {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(600);
                echo fread($fp, $buffer);
                flush();
            }
            fclose($fp);
        } else
        {
            return new Response('File not found', 404);
        }
        die;
    }
    
    
}
