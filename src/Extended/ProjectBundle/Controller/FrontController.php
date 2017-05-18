<?php

namespace Extended\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Extended\ProjectBundle\FS\Node;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Extended\ProjectBundle\Classes\HtmlDiff;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FrontController extends Controller
{
    private function checkPermissions($permission, $projectId, $user, $fileAccessControl = null)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT p.createrId, p.isPublic, p.isLocked FROM ExtendedProjectBundle:Projects p '.
                                  'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $projectId);
        $project = $query->getResult();
        if (!isset($project[0])) {
            return 0;
        }
        $premissionView = 1;
        $premissionEdit = 0;
        $premissionMaster = 0;
        $userRole = 0;
        if (($user != null) && ($user->getBlocked() == 2)) {
            $userRole = 1;
            if ($user->getId() == $project[0]['createrId']) {
                $premissionView = 1;
                $premissionEdit = 1;
                $premissionMaster = 1;
                $userRole = 3;
            } else {
                $query = $em->createQuery('SELECT pu.projectRole, pu.readOnly FROM ExtendedProjectBundle:ProjectUsers pu '.
                                          'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                          'WHERE pu.projectId = :projectid AND pu.userId = :userid AND u.blocked = 2')->setParameter('projectid', $projectId)->setParameter('userid', $user->getId());
                $user = $query->getResult();
                if (isset($user[0])) {
                    $userRole = 2;
                    $premissionView = 1;
                    if ($user[0]['readOnly'] == 0) {
                        $premissionEdit = 1;
                    }
                    if ($user[0]['projectRole'] == 3) {
                        $premissionMaster = 1;
                        $userRole = 3;
                    }
                }
            }
            if ($project[0]['isLocked'] != 0) {
                $premissionEdit = $premissionMaster;
            }
        }
        if ($fileAccessControl === null) {
            if ($permission == 'view') {
                return $premissionView;
            }
            if ($permission == 'edit') {
                return $premissionEdit;
            }
        } else {
            if ($permission == 'view') {
                return ($premissionView && ($fileAccessControl <= $userRole));
            }
            if ($permission == 'edit') {
                return ($premissionEdit && ($fileAccessControl <= $userRole));
            }
        }
        if ($permission == 'create') {
            return $premissionEdit;
        }
        if ($permission == 'remove') {
            return $premissionMaster;
        }
        if ($permission == 'config') {
            return $premissionMaster;
        }
        return 0;
    }


    private function createToken($file, $version, $locale, $user = 0, $permissions = 0)
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($version != null) {
            $token = $em
                    ->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileToken t WHERE t.fileId = :file AND t.versionId = :version AND t.locale = :locale AND t.userId = :user AND t.permission = :permission AND t.expired > :now')
                    ->setParameters(array(
                        'file' => $file,
                        'version' => $version,
                        'locale' => $locale,
                        'user' => $user,
                        'permission' => $permissions,
                        'now' => new \DateTime('+1 hour')
                    ))->getOneOrNullResult();
        } else {
            $token = $em
                    ->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileToken t WHERE t.fileId = :file AND t.versionId IS NULL AND t.locale = :locale AND t.userId = :user AND t.permission = :permission AND t.expired > :now')
                    ->setParameters(array(
                        'file' => $file,
                        'locale' => $locale,
                        'user' => $user,
                        'permission' => $permissions,
                        'now' => new \DateTime('+1 hour')
                    ))->getOneOrNullResult();
        }
        if (!empty($token)) {
            return $token;
        }
        $fileExtension = $em->createQuery('SELECT f.title'.($locale == 'ru' ? 'Ru' : '').' as title, f.extension FROM ExtendedProjectBundle:ProjectFile f WHERE f.id = :file')->setParameter('file', $file)->getOneOrNullResult();
        if (isset($fileExtension['title'])) {
            $fileExtension['title'] = preg_replace('/[^-._[:alnum:]]+/u', '', $fileExtension['title']);
        }
        $tokenName = (!empty($fileExtension['title']) ? $fileExtension['title'] : 'unknown').'.'.(!empty($fileExtension['extension']) ? $fileExtension['extension'] : 'dat');
        $tokenNameIndex = 0;
        do {
            $checkTokenName = $em->createQuery('SELECT count(t.id) FROM ExtendedProjectBundle:ProjectFileToken t WHERE t.token = :name')->setParameter('name', $tokenName)->getSingleScalarResult();
            if ($checkTokenName == 0) {
                break;
            }
            $tokenNameIndex++;
            $tokenName = (!empty($fileExtension['title']) ? $fileExtension['title'] : 'unknown')." ($tokenNameIndex).".(!empty($fileExtension['extension']) ? $fileExtension['extension'] : 'dat');
        } while ($tokenNameIndex < 500);
        $token = new \Extended\ProjectBundle\Entity\ProjectFileToken();
        $token->setExpired(new \DateTime('+1 day'))->setPermission($permissions)->setToken($tokenName)->setUserId($user)->setVersionId($version)->setLocale($locale)->setFileId($file);
        $em->persist($token);
        $em->flush();
        return $token;
    }

    private function sortTree($files, $id = null, $nesting = 0)
    {
        $output = array();
        foreach ($files as $file) {
            if ($file['parentId'] == $id) {
                $file['nesting'] = $nesting;
                $output[] = $file;
                $output = array_merge($output, $this->sortTree($files, $file['id'], $nesting + 1));
            }
        }
        return $output;
    }

    public function getMimeType($file)
    {
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($file);
    }

    public function listAction($projectId, $locale, $user)
    {
        $this->get('google.drive')->applyChnages();

        $em = $this->getDoctrine()->getEntityManager();

        $files = $em->createQuery('SELECT f.id, f.isCollection, f.title, f.titleRu, f.createDate, f.modifyDate, f.parentId, f.mimeType, f.extension, 0 as nesting, f.accessControl FROM ExtendedProjectBundle:ProjectFile f WHERE f.projectId = :project ORDER BY f.modifyDate DESC')->setParameter('project', $projectId)->getResult();
        $files = $this->sortTree($files);

        $filesIds = array();
        foreach ($files as $file) {
            $filesIds[] = $file['id'];
        }
        if (count($filesIds) > 0) {
            $versions = $em->createQuery('SELECT v.id, v.createDate, v.createrId, v.fileId, v.locale, u.login as createrLogin, u.fullName as createrFullName FROM ExtendedProjectBundle:ProjectFileVersion v LEFT JOIN BasicCmsBundle:Users u WITH u.id = v.createrId WHERE v.fileId IN (:ids) ORDER BY v.createDate DESC')->setParameter('ids', $filesIds)->getResult();
        } else {
            $versions = array();
        }
        foreach ($files as $key=>&$file) {
            if (!$this->checkPermissions('view', $projectId, $user, $file['accessControl'])) {
                unset($files[$key]);
            }
            $file['findLocales'] = array();
            $file['versions'] = array();
            foreach ($versions as $version) {
                if ($version['fileId'] == $file['id']) {
                    if (!isset($file['versions'][$version['locale']])) {
                        $file['versions'][$version['locale']] = array();
                    }
                    $file['versions'][$version['locale']][] = $version;
                    if (!in_array($version['locale'], $file['findLocales'])) {
                        $file['findLocales'][] = $version['locale'];
                    }
                }
            }
            $filesIds[] = $file['id'];
        }
        unset($file);
        $files = array_values($files);

        return $this->render('ExtendedProjectBundle:Front:List'.($locale == 'en' ? 'En' : '').'.html.twig', array(
            'files' => $files,
            'versions' => $versions,
            'projectId' => $projectId,
            'currentLocale' => $locale,
            'currentUser' => $user,
            'removePermission' => $this->checkPermissions('remove', $projectId, $user),
            'mimeHelper' => new \Extended\ProjectBundle\Classes\MimeHelper($locale),
            'configPermission' => $this->checkPermissions('config', $projectId, $user),
            'createPermission' => $this->checkPermissions('create', $projectId, $user),
        ));
    }

    private function diff($oldText, $newText)
    {
        $imagereplaces = array();
        preg_match_all('/<[iI][mM][gG]\s+[^>]*/ui', $oldText, $matches);
        foreach ($matches[0] as $match)
        {
            $imagereplaces['image'.md5($match).'endimage'] = $match.'>';
        }
        preg_match_all('/<[iI][mM][gG]\s+[^>]*/ui', $newText, $matches);
        foreach ($matches[0] as $match)
        {
            $imagereplaces['image'.md5($match).'endimage'] = $match.'>';
        }
        $oldverdiff = str_replace(array_values($imagereplaces), array_keys($imagereplaces), $oldText);
        $newverdiff = str_replace(array_values($imagereplaces), array_keys($imagereplaces), $newText);
        $htmldiff = new HtmlDiff($oldverdiff, $newverdiff);
        $htmldiff->build();
        $result = $htmldiff->getDifference();
        unset($htmldiff);
        $result = str_replace(array_keys($imagereplaces), array_values($imagereplaces), $result);
        return $result;
    }


    public function viewAction(Request $request)
    {
        $user = $this->getCurrentUser();
        $fileId = $request->get('id');
        $locale = $request->get('locale');
        $version = $request->get('version');

        if ($locale != 'en') {
            $locale = 'ru';
        }


        $em = $this->getDoctrine()->getEntityManager();

        $file = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($fileId);

        if (!$this->checkPermissions('view', $file->getProjectId(), $user, $file->getAccessControl())) {
            return new Response('View denied');
        }

        if ($file->getMimeType() == 'text/html') {
            if ($version == null) {
                $lastVersions = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
                    'fileid' => $fileId,
                    'locale' => $locale,
                ))->setMaxResults(2)->getResult();
                if (empty($lastVersions)) {
                    $lastVersions = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid ORDER BY v.id DESC')->setParameters(array(
                        'fileid' => $fileId,
                    ))->setMaxResults(1)->getResult();
                }
            } else {
                $lastVersions = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale AND v.id <= :version ORDER BY v.id DESC')->setParameters(array(
                    'fileid' => $fileId,
                    'locale' => $locale,
                    'version' => $version,
                ))->setMaxResults(2)->getResult();
            }
            if (empty($lastVersions)) {
                return new Response('Not found');
            }
            $content = file_get_contents(''.Node::FOLDER.$lastVersions[0]->getContentFile());
            $diffContent = null;
            if (isset($lastVersions[1])) {
                $diffContent = $this->diff(file_get_contents(''.Node::FOLDER.$lastVersions[1]->getContentFile()), $content);
            }



            return $this->render('ExtendedProjectBundle:Front:ViewHtml'.($locale == 'en' ? 'En' : '').'.html.twig', array(
                'file' => $file,
                'lastVersions' => $lastVersions,
                'content' => $content,
                'diffContent' => $diffContent,
                'currentLocale' => $locale,
                'currentUser' => $user,
            ));
        }



        if (in_array($file->getMimeType(), array(
                'application/vnd.google-apps.document',
                'application/vnd.google-apps.presentation',
                'application/vnd.google-apps.spreadsheet'
        ))) {
            $googleToken = $this->get('google.drive')->createViewToken($fileId, $locale, $version);
            if (!empty($googleToken)) {
                $url = $googleToken->getLink();
                if ($url) {
                    return $this->redirect($url);
                }
            }
        }



        /*$lastVersion = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
            'fileid' => $fileId,
            'locale' => $locale,
        ))->setMaxResults(1)->getOneOrNullResult();
        if (empty($lastVersion)) {
            $lastVersion = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid ORDER BY v.id DESC')->setParameters(array(
                'fileid' => $fileId,
            ))->setMaxResults(1)->getOneOrNullResult();
        }*/
        $token = $this->createToken($fileId, $version, $locale, (!empty($user) ? $user->getId() : 0), 0);
        $url = $this->generateUrl('extended_project_sabre_dav', array(
            'path' => $token->getToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        if (in_array($file->getMimeType(), array('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text'))) {
            $url = 'ms-word:ofv|u|'.$url;
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))) {
            $url = 'ms-excel:ofv|u|'.$url;
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.openxmlformats-officedocument.presentationml.slideshow'))) {
            $url = 'ms-powerpoint:ofv|u|'.$url;
        }

        return $this->redirect($url);
    }

    public function viewEmbeddedAction($fileId, $locale, $version, $user, $request)
    {
        if ($locale != 'en') {
            $locale = 'ru';
        }

        $em = $this->getDoctrine()->getEntityManager();

        $file = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($fileId);

        if (!$this->checkPermissions('view', $file->getProjectId(), $user, $file->getAccessControl())) {
            return new Response('View denied');
        }

        if ($file->getMimeType() == 'text/html') {
            if ($version == null) {
                $lastVersions = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
                    'fileid' => $fileId,
                    'locale' => $locale,
                ))->setMaxResults(2)->getResult();
                if (empty($lastVersions)) {
                    $lastVersions = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid ORDER BY v.id DESC')->setParameters(array(
                        'fileid' => $fileId,
                    ))->setMaxResults(1)->getResult();
                }
            } else {
                $lastVersions = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale AND v.id <= :version ORDER BY v.id DESC')->setParameters(array(
                    'fileid' => $fileId,
                    'locale' => $locale,
                    'version' => $version,
                ))->setMaxResults(2)->getResult();
            }
            if (empty($lastVersions)) {
                return new Response('Not found');
            }
            $content = file_get_contents(''.Node::FOLDER.$lastVersions[0]->getContentFile());
            $diffContent = null;
            if (isset($lastVersions[1])) {
                $diffContent = $this->diff(file_get_contents(''.Node::FOLDER.$lastVersions[1]->getContentFile()), $content);
            }



            return $this->render('ExtendedProjectBundle:Front:ViewHtml'.($locale == 'en' ? 'En' : '').'.html.twig', array(
                'file' => $file,
                'lastVersions' => $lastVersions,
                'content' => $content,
                'diffContent' => $diffContent,
                'currentLocale' => $locale,
                'currentUser' => $user,
            ));
        }

        $token = $this->createToken($fileId, $version, $locale, (!empty($user) ? $user->getId() : 0), 0);
        $url = $this->generateUrl('extended_project_sabre_dav', array(
            'path' => $token->getToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->redirect($url);
    }



    public function editAction($fileId, $locale, $user, $request)
    {
        if ($request->getMethod() == 'GET') {
            $this->get('google.drive')->applyChnages();
        }

        $em = $this->getDoctrine()->getEntityManager();

        $file = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($fileId);

        if (!$this->checkPermissions('edit', $file->getProjectId(), $user, $file->getAccessControl())) {
            return new Response('Edit denied', 403);
        }

        $versions = $em->createQuery('SELECT v.id, v.contentFile, v.createDate, v.createrId, v.locale, u.login as createrLogin, u.fullName as createrFullName FROM ExtendedProjectBundle:ProjectFileVersion v LEFT JOIN BasicCmsBundle:Users u WITH u.id = v.createrId WHERE v.fileId = :id ORDER BY v.createDate DESC')
                ->setParameter('id', $fileId)->getResult();

        $findLocales = array();
        foreach ($versions as $version) {
            if (!in_array($version['locale'], $findLocales)) {
                $findLocales[] = $version['locale'];
            }
        }

        if ($file->getIsCollection()) {
            $folders = array();
        } else {
            $folders = $em->createQuery('SELECT f.id, f.title'.($locale != 'en' ? 'Ru' : '').' as title, f.parentId, 0 as nesting FROM ExtendedProjectBundle:ProjectFile f WHERE f.projectId = :project AND f.isCollection != 0 ORDER BY f.modifyDate ASC')->setParameter('project', $file->getProjectId())->getResult();
            $folders = $this->sortTree($folders);
        }

        $htmlContentRu = '';
        $htmlContentEn = '';
        if ($file->getMimeType() == 'text/html') {
            $htmlLastVersionRu = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
                'fileid' => $file->getId(),
                'locale' => 'ru',
            ))->setMaxResults(1)->getOneOrNullResult();
            if (!empty($htmlLastVersionRu)) {
                $htmlContentRu = file_get_contents(''.Node::FOLDER.$htmlLastVersionRu->getContentFile());
            }
            $htmlLastVersionEn = $em->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
                'fileid' => $file->getId(),
                'locale' => 'en',
            ))->setMaxResults(1)->getOneOrNullResult();
            if (!empty($htmlLastVersionEn)) {
                $htmlContentEn = file_get_contents(''.Node::FOLDER.$htmlLastVersionEn->getContentFile());
            }
        }

        $errors = array();
        $result = false;
        if ($request->getMethod() == "POST") {
            $file->setTitle($request->get('title'));
            $file->setTitleRu($request->get('titleRu'));
            if ($file->getIsCollection()) {
                //$file->setParentId(($request->get('parentId') != '' ? $request->get('parentId') : null));
            } else {
                $file->setParentId(($request->get('parentId') != '' ? $request->get('parentId') : null));
            }
            if (!preg_match('/^[^\/\\:\*\?"<>\|\+%!@\s\.][^\/\\:\*\?"<>\|\+%!@]*[^\/\\:\*\?"<>\|\+%!@\s\.]$/ui', $file->getTitle())) {
                $errors['title'] = ($locale == 'en' ? 'File name must contains more than 2 allowed charters' : 'Имя файла должно состоять более чем из 2 разрешенных символов');
            }
            if (!preg_match('/^[^\/\\:\*\?"<>\|\+%!@\s\.][^\/\\:\*\?"<>\|\+%!@]*[^\/\\:\*\?"<>\|\+%!@\s\.]$/ui', $file->getTitleRu())) {
                $errors['titleRu'] = ($locale == 'en' ? 'File name must contains more than 2 allowed charters' : 'Имя файла должно состоять более чем из 2 разрешенных символов');
            }
            if ($file->getMimeType() == 'text/html') {
                $postContentRu = $request->get('contentRu');
                $postContentEn = $request->get('contentEn');
                if ($htmlContentEn != $postContentEn) {
                    do {
                        $fileUploadFileName = md5(time().rand());
                    } while (file_exists(''.Node::FOLDER.$fileUploadFileName));
                    file_put_contents(''.Node::FOLDER.$fileUploadFileName, $postContentEn);
                    $htmlLastVersionEn = new \Extended\ProjectBundle\Entity\ProjectFileVersion();
                    $htmlLastVersionEn
                            ->setContentFile($fileUploadFileName)
                            ->setCreateDate(new \DateTime('now'))
                            ->setCreaterId(($user != null ? $user->getId() : null))
                            ->setFileId($file->getId())
                            ->setLocale('en');
                    $em->persist($htmlLastVersionEn);
                    $htmlContentEn = $postContentEn;
                }
                if ($htmlContentRu != $postContentRu) {
                    do {
                        $fileUploadFileName = md5(time().rand());
                    } while (file_exists(''.Node::FOLDER.$fileUploadFileName));
                    file_put_contents(''.Node::FOLDER.$fileUploadFileName, $postContentRu);
                    $htmlLastVersionRu = new \Extended\ProjectBundle\Entity\ProjectFileVersion();
                    $htmlLastVersionRu
                            ->setContentFile($fileUploadFileName)
                            ->setCreateDate(new \DateTime('now'))
                            ->setCreaterId(($user != null ? $user->getId() : null))
                            ->setFileId($file->getId())
                            ->setLocale('ru');
                    $em->persist($htmlLastVersionRu);
                    $htmlContentRu = $postContentRu;
                }
            }
            if (count($errors) == 0) {
                $em->flush();
                $result = true;
            }
        }

        $urlRu = null;
        $urlEn = null;

        if ($file->getIsCollection() == 0) {
            $urlRu = $this->generateUrl('extended_project_front_edit', array('file' => $fileId, 'locale' => 'ru'));
            $urlEn = $this->generateUrl('extended_project_front_edit', array('file' => $fileId, 'locale' => 'en'));
        }

        return $this->render('ExtendedProjectBundle:Front:Edit'.($locale == 'en' ? 'En' : '').'.html.twig', array(
            'urlRu' => $urlRu,
            'urlEn' => $urlEn,
            'htmlContentRu' => $htmlContentRu,
            'htmlContentEn' => $htmlContentEn,
            'file' => $file,
            'versions' => $versions,
            'findLocales' => $findLocales,
            'folders' => $folders,
            'errors' => $errors,
            'result' => $result,
            'currentLocale' => $locale,
            'currentUser' => $user,
        ));
    }

    public function editRedirectAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $fileId = $request->get('file');
        $locale = $request->get('locale');
        $user = $this->getCurrentUser();

        $file = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($fileId);

        if (!$this->checkPermissions('edit', $file->getProjectId(), $user, $file->getAccessControl())) {
            return new Response('Edit denied', 403);
        }
        if ($file->getIsCollection()) {
            return new Response('Error', 404);
        }
        if (in_array($file->getMimeType(), array(
                'application/vnd.google-apps.document',
                'application/vnd.google-apps.presentation',
                'application/vnd.google-apps.spreadsheet'
        ))) {
            $googleToken = $this->get('google.drive')->createEditToken($fileId, $locale, $user->getId());
            if (!empty($googleToken)) {
                $url = $googleToken->getLink();
                if ($url) {
                    return $this->redirect($url);
                }
            }
        }

        $token = $this->createToken($fileId, null, $locale, (!empty($user) ? $user->getId() : 0), 1);
        $url = $this->generateUrl('extended_project_sabre_dav', array(
            'path' => $token->getToken(),
        ), UrlGeneratorInterface::ABSOLUTE_URL);

        if (in_array($file->getMimeType(), array('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text'))) {
            $url = 'ms-word:ofe|u|'.$url;
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))) {
            $url = 'ms-excel:ofe|u|'.$url;
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.openxmlformats-officedocument.presentationml.slideshow'))) {
            $url = 'ms-powerpoint:ofe|u|'.$url;
        }

        return $this->redirect($url);
    }


    private function getCurrentUser()
    {
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
        return $userEntity;
    }

    public function createFolderAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $project = $request->get('project');
        $locale = $request->get('locale');
        if ($request->headers->get('x-ajaxupload-partial-locale') != '') {
            $locale = $request->headers->get('x-ajaxupload-partial-locale');
        }

        if (!$this->checkPermissions('create', $project, $this->getCurrentUser())) {
            return new Response('Create denied');
        }

        $folder = null;
        if ($request->get('folder') != null) {
            $folder = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectFile')->findOneBy(array('isCollection' => 1, 'id' => $request->get('folder')));
            if (empty($folder)) {
                $folder = null;
            }
        }
        $title = $request->get('title');
        $titleRu = $request->get('titleRu');

        $projectFile = new \Extended\ProjectBundle\Entity\ProjectFile();
        $projectFile
                ->setCreateDate(new \DateTime('now'))
                ->setExtension('')
                ->setIsCollection(1)
                ->setMimeType('')
                ->setModifyDate(new \DateTime('now'))
                ->setParentId(($folder != null ? $folder->getId() : null))
                ->setProjectId($project)
                ->setTitle($title)
                ->setTitleRu($titleRu);
        $em->persist($projectFile);
        $em->flush();
        return new Response('OK');
    }

    public function createDocumentAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $project = $request->get('project');
        $locale = $request->get('locale');
        if ($request->headers->get('x-ajaxupload-partial-locale') != '') {
            $locale = $request->headers->get('x-ajaxupload-partial-locale');
        }
        $documentType = $request->get('documentType');
        $mimeType = 'text/html';
        $mimeHelper = new \Extended\ProjectBundle\Classes\MimeHelper();
        $mimeTypes = $mimeHelper->getMimeByType($documentType);
        if (count($mimeTypes) > 0) {
            $mimeType = $mimeTypes[0];
        }
        $user = $this->getCurrentUser();

        if (!$this->checkPermissions('create', $project, $user)) {
            return new Response('Create denied');
        }

        $folder = null;
        if ($request->get('folder') != null) {
            $folder = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectFile')->findOneBy(array('isCollection' => 1, 'id' => $request->get('folder')));
            if (empty($folder)) {
                $folder = null;
            }
        }
        $title = $request->get('title');
        $titleRu = $request->get('titleRu');

        $projectFile = new \Extended\ProjectBundle\Entity\ProjectFile();
        $projectFile
                ->setCreateDate(new \DateTime('now'))
                ->setExtension('html')
                ->setIsCollection(0)
                ->setMimeType($mimeType)
                ->setModifyDate(new \DateTime('now'))
                ->setParentId(($folder != null ? $folder->getId() : null))
                ->setProjectId($project)
                ->setTitle($title)
                ->setTitleRu($titleRu);
        $em->persist($projectFile);
        $em->flush();
        do {
            $fileUploadFileName = md5(time().rand());
        } while (file_exists(''.Node::FOLDER.$fileUploadFileName));
        file_put_contents(''.Node::FOLDER.$fileUploadFileName, '');
        $projectFileVersion = new \Extended\ProjectBundle\Entity\ProjectFileVersion();
        $projectFileVersion
                ->setContentFile($fileUploadFileName)
                ->setCreateDate(new \DateTime('now'))
                ->setCreaterId(($user != null ? $user->getId() : null))
                ->setFileId($projectFile->getId())
                ->setLocale($locale);
        $em->persist($projectFileVersion);
        $em->flush();
        return new Response('OK');
    }

    public function uploadAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $project = $request->get('project');
        $file = $request->get('file');
        $locale = $request->get('locale');
        if ($request->headers->get('x-ajaxupload-partial-locale') != '') {
            $locale = $request->headers->get('x-ajaxupload-partial-locale');
        }

        $projectFile = null;
        if ($file != null) {
            $projectFile = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($file);
            if (!empty($projectFile)) {
                $project = $projectFile->getProjectId();
            }
        }

        if (!$this->checkPermissions((!empty($projectFile) ? 'edit' : 'create'), $project, $this->getCurrentUser(), (!empty($projectFile) ? $projectFile->getAccessControl() : null))) {
            return new Response('Create denied');
        }

        $folder = null;
        if ($request->headers->get('x-ajaxupload-partial-folder') != '') {
            $folder = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectFile')->findOneBy(array('isCollection' => 1, 'id' => $request->headers->get('x-ajaxupload-partial-folder')));
            if (empty($folder)) {
                $folder = null;
            }
        }
        if ($request->headers->get('x-ajaxupload-partial-module') == 'ajaxupload.partial') {
            $session = $request->getSession()->get('ajaxupload.partial');
            if (!is_array($session)) {
                $session = array();
            }
            $fileUploadId = $request->headers->get('x-ajaxupload-partial-id');
            $fileUploadSize = $request->headers->get('x-ajaxupload-partial-size');
            $fileUploadOriginalName = urldecode($request->headers->get('x-ajaxupload-partial-filename'));
            $fileUploadPosition = $request->headers->get('x-ajaxupload-partial-position');
            $fileUploadFileName = null;
            if (isset($session[$fileUploadId])) {
                if ($fileUploadSize != $session[$fileUploadId]['size'] || $fileUploadOriginalName != $session[$fileUploadId]['originalName']) {
                    return new Response(json_encode(array('result' => 'Error', 'message' => 'Файлы не соотвествуют')));
                } else {
                    $fileUploadFileName = $session[$fileUploadId]['fileName'];
                }
            } else {
                if ($folder != null) {
                    $count = $em->createQuery('SELECT COUNT(e.id) FROM ExtendedProjectBundle:ProjectFile e WHERE (e.title = :title OR e.titleRu = :title) AND e.extension = :extension AND e.parentId = :parent')->setParameters(array(
                        'title' => pathinfo($fileUploadOriginalName, PATHINFO_FILENAME),
                        'extension' => pathinfo($fileUploadOriginalName, PATHINFO_EXTENSION),
                        'parent' => $folder->getId(),
                    ))->getSingleScalarResult();
                } else {
                    $count = $em->createQuery('SELECT COUNT(e.id) FROM ExtendedProjectBundle:ProjectFile e WHERE (e.title = :title OR e.titleRu = :title) AND e.extension = :extension AND e.parentId IS NULL')->setParameters(array(
                        'title' => pathinfo($fileUploadOriginalName, PATHINFO_FILENAME),
                        'extension' => pathinfo($fileUploadOriginalName, PATHINFO_EXTENSION),
                    ))->getSingleScalarResult();
                }
                if ($count != 0) {
                    return new Response(json_encode(array('result' => 'Error', 'message' => 'Файл уже существует в этой папке')));
                }
                do {
                    $fileUploadFileName = md5(time().rand());
                } while (file_exists(''.Node::FOLDER.$fileUploadFileName));
                $session[$fileUploadId] = array(
                    'fileName' => $fileUploadFileName,
                    'originalName' => $fileUploadOriginalName,
                    'size' => $fileUploadSize
                );
                $request->getSession()->set('ajaxupload.partial', $session);
            }
            if ($fileUploadFileName) {
                if (file_exists(''.Node::FOLDER.$fileUploadFileName)) {
                    $fileUploadCurrentPosition = filesize(''.Node::FOLDER.$fileUploadFileName);
                } else {
                    $fileUploadCurrentPosition = 0;
                }
                if ($fileUploadCurrentPosition != $fileUploadPosition) {
                    return new Response(json_encode(array('next' => $fileUploadCurrentPosition)));
                }
                $f = fopen(''.Node::FOLDER.$fileUploadFileName, 'a+b');
                $data = $request->getContent();
                fwrite($f, $data);
                fclose($f);
                $fileUploadCurrentPosition += strlen($data);
                if ($fileUploadCurrentPosition < $fileUploadSize) {
                    return new Response(json_encode(array('next' => $fileUploadCurrentPosition)));
                }
                if (empty($projectFile)) {
                    $projectFile = new \Extended\ProjectBundle\Entity\ProjectFile();
                    $projectFile
                            ->setCreateDate(new \DateTime('now'))
                            ->setExtension(pathinfo($fileUploadOriginalName, PATHINFO_EXTENSION))
                            ->setIsCollection(0)
                            ->setMimeType($this->getMimeType(''.Node::FOLDER.$fileUploadFileName))
                            ->setModifyDate(new \DateTime('now'))
                            ->setParentId(($folder != null ? $folder->getId() : null))
                            ->setProjectId($project)
                            ->setTitle(pathinfo($fileUploadOriginalName, PATHINFO_FILENAME))
                            ->setTitleRu(pathinfo($fileUploadOriginalName, PATHINFO_FILENAME));
                    $em->persist($projectFile);
                    $em->flush();
                } else {
                    $projectFile
                            ->setExtension(pathinfo($fileUploadOriginalName, PATHINFO_EXTENSION))
                            ->setIsCollection(0)
                            ->setMimeType($this->getMimeType(''.Node::FOLDER.$fileUploadFileName))
                            ->setModifyDate(new \DateTime('now'));
                    $em->flush();
                }

                $user = $this->getCurrentUser();

                $projectFileVersion = new \Extended\ProjectBundle\Entity\ProjectFileVersion();
                $projectFileVersion
                        ->setContentFile($fileUploadFileName)
                        ->setCreateDate(new \DateTime('now'))
                        ->setCreaterId(($user != null ? $user->getId() : null))
                        ->setFileId($projectFile->getId())
                        ->setLocale($locale);
                $em->persist($projectFileVersion);
                $em->flush();
                return new Response(json_encode(array('result' => 'OK', 'message' => '')));
            }
        } else {
            /*$file = $this->get('request_stack')->getMasterRequest()->files->get('file');
            $tmpfile = $file->getPathName();
            $fileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
            if (!is_array($fileInfo)) $fileInfo = array();
            if (!isset($fileInfo[$file->getClientOriginalName()]) || ($fileInfo[$file->getClientOriginalName()] == $documentId) || ($fileInfo[$file->getClientOriginalName()] == 'proj'.$projectId))
            {
                if (move_uploaded_file($tmpfile, $projectFolder.'/'.$file->getClientOriginalName()))
                {
                    if ($documentId !== null) $fileInfo[$file->getClientOriginalName()] = $documentId; else $fileInfo[$file->getClientOriginalName()] = 'proj'.$projectId;
                    file_put_contents($projectFolder.'/'.'.folderinfo', serialize($fileInfo));
                } else {$operationResult = 'Error';$operationMessage = 'Ошибка загрузки файла';}
            } else {$operationResult = 'Error';$operationMessage = 'Файл уже существует для другого документа';}*/
        }
    }

    public function removeVersionAction(Request $request)
    {
        $id = $request->get('id');
        $em = $this->getDoctrine()->getEntityManager();
        // найти версию
        $version = $em->getRepository('ExtendedProjectBundle:ProjectFileVersion')->find($id);
        $file = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($version->getFileId());
        // проверить права
        if (!$this->checkPermissions('remove', $file->getProjectId(), $this->getCurrentUser())) {
            return new Response('Access denied', 403);
        }
        // удалить версию
        $em->remove($version);
        $em->flush();
        // если версия последняя - удалить весь файл
        $versionsCount = $em->createQuery('SELECT count(v.id) FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :file')->setParameter('file', $file->getId())->getSingleScalarResult();
        if ($versionsCount == 0) {
            $em->remove($file);
            $em->flush();
        }
        return new Response('OK');
    }

    public function changeAccessControlAction(Request $request)
    {
        $id = $request->get('id');
        $access = $request->get('access');
        $em = $this->getDoctrine()->getEntityManager();
        // найти версию
        $file = $em->getRepository('ExtendedProjectBundle:ProjectFile')->find($id);
        // проверить права
        if (!$this->checkPermissions('config', $file->getProjectId(), $this->getCurrentUser())) {
            return new Response('Access denied', 403);
        }
        // установить знавение
        $file->setAccessControl($access);
        $em->flush();
        return new Response('OK');
    }


}
