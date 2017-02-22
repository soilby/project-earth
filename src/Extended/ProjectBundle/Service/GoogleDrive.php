<?php

namespace Extended\ProjectBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Extended\ProjectBundle\Classes\HtmlDiff as HtmlDiff;
use Google_Service_Drive;
use Google_Client;
use Extended\ProjectBundle\Entity\ProjectFileGDToken;
use Extended\ProjectBundle\FS\Node;
use Extended\ProjectBundle\Entity\ProjectFileVersion;

class GoogleDrive
{
    
    private $appName = 'My Project';
    private $appScopes = array(
        Google_Service_Drive::DRIVE_METADATA,
        Google_Service_Drive::DRIVE_FILE,
    );
    private $appSecretPath = __DIR__.'/../../../../.googledrive.json';
    
    
    private $container;
    private $entityManager;
    private $cmsManager;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->cmsManager = $container->get('cms.cmsManager');
    }
    
    private $googleSerivce;

    private function getGoogleService()
    {
        if ($this->googleSerivce) {
            return $this->googleSerivce;
        }
        $client = new Google_Client();
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$this->appSecretPath");
        $client->useApplicationDefaultCredentials();
        $client->setApplicationName($this->appName);
        $client->setScopes(implode(' ', $this->appScopes));
        $this->googleSerivce = new Google_Service_Drive($client);
        return $this->googleSerivce;
    }
    
    private function applyTokenChange(ProjectFileGDToken $token)
    {
        if ($token->getPermission() != 1) {
            return false;
        }
        $fileEntity = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFile')->find($token->getFileId());
        $versionEntity = null;
        if ($token->getVersionId() != null) {
            $versionEntity = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFile')->find($token->getVersionId());
        }
        if (empty($versionEntity)) {
            do {
                $fileUploadFileName = md5(time().rand());
            } while (file_exists(Node::FOLDER.$fileUploadFileName));
            $versionEntity = new ProjectFileVersion();
            $versionEntity
                    ->setContentFile($fileUploadFileName)
                    ->setCreateDate(new \DateTime('now'))
                    ->setCreaterId($token->getUserId())
                    ->setFileId($token->getFileId())
                    ->setLocale($token->getLocale());
            $this->entityManager->persist($versionEntity);
        }
        $driveService = $this->getGoogleService();
        $content = $driveService->files->export($token->getToken(), $fileEntity->getMimeType(), array('alt' => 'media'));
        file_put_contents(Node::FOLDER.$versionEntity->getContentFile(), $content->getBody());
        
        $token->setVersionId($versionEntity->getId());
        $this->entityManager->flush();
    }
    
    private function removeExpiredToken(ProjectFileGDToken $token)
    {
        $driveService = $this->getGoogleService();
        $driveService->files->delete($token->getToken());
        
        $this->entityManager->remove($token);
        $this->entityManager->flush();
    }
    
    public function applyChnages()
    {
        $mutex = $this->cmsManager->getConfigValue('project.googledrive.mutex');
        if ($mutex > time()) {
            return;
        }
        $this->cmsManager->setConfigValue('project.googledrive.mutex', time() + 5*60);
        
        $service = $this->getGoogleService();
        $changesToken = $this->cmsManager->getConfigValue('project.googledrive.changestoken');
        if (!$changesToken) {
            $changesToken = $service->changes->getStartPageToken()->startPageToken;
            $this->cmsManager->setConfigValue('project.googledrive.changestoken', $changesToken);
        }
        $changesRaw = $service->changes->listChanges($changesToken);
        $changes = array();
        foreach ($changesRaw->changes as $change) {
            $changes[] = $change->fileId;
        }
        $changes = array_unique($changes);
        
        if (count($changes) > 0) {
            $changesToken = $changesRaw->newStartPageToken;
            $this->cmsManager->setConfigValue('project.googledrive.changestoken', $changesToken);
            $changeTokens = $this->entityManager->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileGDToken t WHERE t.token IN (:tokens)')->setParameter('tokens', $changes)->getResult();
            foreach ($changeTokens as $changeToken) {
                $this->applyTokenChange($changeToken);
            }
        }
        
        $removeTokens = $this->entityManager->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileGDToken t WHERE t.expired < :now')->setParameter('now', new \DateTime('now'))->getResult();
        foreach ($removeTokens as $removeToken) {
            $this->removeExpiredToken($removeToken);
        }
    }
    
    
    public function createViewToken($fileId, $locale, $versionId)
    {
        $file = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFile')->find($fileId);
        if (empty($file)) {
            return null;
        }
        // Если версии нет то взять последнюю
        if ($versionId == null) {
            $lastVersion = $this->entityManager->createQuery('SELECT v.id FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
                'fileid' => $fileId,
                'locale' => $locale,
            ))->setMaxResults(1)->getOneOrNullResult();
            if (!isset($lastVersion['id'])) {
                $lastVersion = $this->entityManager->createQuery('SELECT v.id FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid ORDER BY v.id DESC')->setParameters(array(
                    'fileid' => $fileId,
                ))->setMaxResults(1)->getOneOrNullResult();
            }
            if (!isset($lastVersion['id'])) {
                return null;
            }
            $versionId = $lastVersion['id'];
        }
        // попробовать найти уже такой токен
        $token = $this->entityManager
                ->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileGDToken t WHERE t.fileId = :file AND t.versionId = :version AND t.locale = :locale AND t.permission = 0 AND t.expired > :now')
                ->setParameters(array(
                    'file' => $fileId,
                    'version' => $versionId,
                    'locale' => $locale,
                    'now' => new \DateTime('+1 hour')
                ))->getOneOrNullResult();
        if (!empty($token)) {
            return $token;
        }
        // сгенерить новый
        $version = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFileVersion')->find($versionId);
        if (empty($version)) {
            return null;
        }
        
        $gdType = $file->getMimeType();
        if (in_array($file->getMimeType(), array('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text'))) {
            $gdType = 'application/vnd.google-apps.document';
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))) {
            $gdType = 'application/vnd.google-apps.spreadsheet';
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.openxmlformats-officedocument.presentationml.slideshow'))) {
            $gdType = 'application/vnd.google-apps.presentation';
        }
        
        $driveService = $this->getGoogleService();
        $fileMetadata = new \Google_Service_Drive_DriveFile(array(
            'name' => ($locale == 'en' ? $file->getTitle() : $file->getTitleRu()),
            'mimeType' => $gdType));
        $content = file_get_contents(Node::FOLDER.$version->getContentFile());
        $googleFile = $driveService->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id,webContentLink,webViewLink'
        ));
        $permission = new \Google_Service_Drive_Permission();
        $permission->setRole('reader');
        $permission->setType('anyone');
        $permission->setExpirationTime(new \DateTime('+7 days'));
        $driveService->permissions->create($googleFile->id, $permission);
        
        $token = new ProjectFileGDToken();
        $token
                ->setExpired(new \DateTime('+7 days'))
                ->setFileId($fileId)
                ->setLocale($locale)
                ->setPermission(0)
                ->setToken($googleFile->id)
                ->setLink((!empty($googleFile->webViewLink) ? $googleFile->webViewLink : $googleFile->webContentLink))
                ->setVersionId($versionId);
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        return $token;
    }
    
    

    public function createEditToken($fileId, $locale, $userId)
    {
        $file = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFile')->find($fileId);
        if (empty($file)) {
            return null;
        }
        $token = $this->entityManager
                ->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileGDToken t WHERE t.fileId = :file AND t.userId = :user AND t.locale = :locale AND t.permission = 1 AND t.expired > :now')
                ->setParameters(array(
                    'file' => $fileId,
                    'locale' => $locale,
                    'user' => $userId,
                    'now' => new \DateTime('+1 hour')
                ))->getOneOrNullResult();
        if (!empty($token)) {
            return $token;
        }
        $version = $this->entityManager->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
            'fileid' => $fileId,
            'locale' => $locale,
        ))->setMaxResults(1)->getOneOrNullResult();
        if (empty($version)) {
            return null;
        }
        
        $gdType = $file->getMimeType();
        if (in_array($file->getMimeType(), array('application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text'))) {
            $gdType = 'application/vnd.google-apps.document';
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))) {
            $gdType = 'application/vnd.google-apps.spreadsheet';
        }
        if (in_array($file->getMimeType(), array('application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.openxmlformats-officedocument.presentationml.slideshow'))) {
            $gdType = 'application/vnd.google-apps.presentation';
        }
        
        $driveService = $this->getGoogleService();
        $fileMetadata = new \Google_Service_Drive_DriveFile(array(
            'name' => ($locale == 'en' ? $file->getTitle() : $file->getTitleRu()),
            'mimeType' => $gdType));
        $content = file_get_contents(Node::FOLDER.$version->getContentFile());
        $googleFile = $driveService->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id,webContentLink,webViewLink'
        ));
        $permission = new \Google_Service_Drive_Permission();
        $permission->setRole('writer');
        $permission->setType('anyone');
        $permission->setExpirationTime(new \DateTime('+1 days'));
        $driveService->permissions->create($googleFile->id, $permission);
        
        $token = new ProjectFileGDToken();
        $token
                ->setExpired(new \DateTime('+1 days'))
                ->setFileId($fileId)
                ->setLocale($locale)
                ->setPermission(1)
                ->setUserId($userId)
                ->setToken($googleFile->id)
                ->setLink((!empty($googleFile->webViewLink) ? $googleFile->webViewLink : $googleFile->webContentLink));
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        return $token;
    }
   
}
