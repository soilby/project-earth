<?php

namespace Extended\KnowledgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class FrontController extends Controller
{
    private function checkUser()
    {
        $userid = $this->get('request_stack')->getMasterRequest()->getSession()->get('front_system_autorized');
        $userEntity = null;
        if ($userid != null) {
            $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
        }
        if (empty($userEntity)) {
            $userid = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keytwo');
            $userpass = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keyone');
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
            $userEntity = $query->getResult();
            if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) {
                $userEntity = $userEntity[0];
            }
        }
        if (empty($userEntity)) {
            return false;
        }
        if ($userEntity->getBlocked() != 2) {
            return false;
        }
        $this->container->get('cms.cmsManager')->setCurrentUser($userEntity);
        return true;
    }
    

    public function loadInfoAction()
    {
        if ($this->checkUser() == false) {
            return null;
        }
        $locale = $this->get('request_stack')->getMasterRequest()->get('locale');
        $type = $this->get('request_stack')->getMasterRequest()->get('type');
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        $textId = $this->get('request_stack')->getMasterRequest()->get('textid');
        $result = $this->container->get('object.knowledge')->getKnowledgeParameters($locale, $type, $id, $textId);
        return new JsonResponse($result);
    }        
    
    public function addInfoAction()
    {
        if ($this->checkUser() == false) {
            return null;
        }
        $type = $this->get('request_stack')->getMasterRequest()->get('type');
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        $textId = $this->get('request_stack')->getMasterRequest()->get('textid');
        $postfields = $this->get('request_stack')->getMasterRequest()->get('actionfields');
        
        $knowledgeTitle = (isset($postfields['knowledgetitle']) ? $postfields['knowledgetitle'] : null);
        $knowledgeTypes = (isset($postfields['knowledgetypes']) ? $postfields['knowledgetypes'] : null);
        $knowledgeDescription = (isset($postfields['knowledgedescription']) ? $postfields['knowledgedescription'] : null);
        
        $objectEnt = $this->container->get('object.knowledge')->checkKnowledgeObject($type, $id, $textId);

        $result = array();
        if ($objectEnt == false) {
            $this->container->get('object.knowledge')->addKnowledgeObject($type, $id, $textId, $knowledgeTitle, $knowledgeTypes);
            $result['result'] = 'OK';
        } else {
            $this->container->get('object.knowledge')->changeKnowledgeObject($type, $id, $textId, $knowledgeTitle, $knowledgeTypes, $knowledgeDescription);
            $result['result'] = 'OK';
        }
        return new JsonResponse($result);
    }        
    
}
