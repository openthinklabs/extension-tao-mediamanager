<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoMediaManager\model;

use oat\oatbox\Configurable;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\media\MediaManagement;
use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\oatbox\log\LoggerAwareTrait;
use oat\generis\model\OntologyAwareTrait;

class MediaSource extends Configurable implements MediaManagement
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const SCHEME_NAME = 'taomedia://mediamanager/';

    protected $mediaService;

    protected $fileManagementService;


    /**
     * Returns the lanuage URI to be used
     * @return string
     */
    protected function getLanguage()
    {
        return $this->hasOption('lang')
            ? $this->getOption('lang')
            : ''
        ;
    }

    public function getRootClass()
    {
        return $this->getClass($this->getRootClassUri());
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaManagement::add
     */
    public function add($source, $fileName, $parent, $mimetype = null)
    {
        if (!file_exists($source)) {
            throw new \tao_models_classes_FileNotFoundException($source);
        }

        $clazz = $this->getOrCreatePath($parent);

        $service = $this->getMediaService();
        $instanceUri = $service->createMediaInstance($source, $clazz->getUri(), $this->getLang(), $fileName, $mimetype);

        return $this->getFileInfo($instanceUri);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaManagement::delete
     */
    public function delete($link)
    {
        return $this->getMediaService()->deleteResource($this->getResource(\tao_helpers_Uri::decode($link)));
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaBrowser::getDirectory
     */
    public function getDirectory($parentLink = '', $acceptableMime = array(), $depth = 1)
    {
        if ($parentLink == '') {
            $class = $this->getClass($this->getRootClassUri());

        } else {
            $class = $this->getClass(\tao_helpers_Uri::decode($parentLink));
        }

        $data = array(
            'path' => self::SCHEME_NAME . \tao_helpers_Uri::encode($class->getUri()),
            'label' => $class->getLabel()
        );
        if ($depth > 0) {
            $children = array();
            foreach ($class->getSubClasses() as $subclass) {
                $children[] = $this->getDirectory($subclass->getUri(), $acceptableMime, $depth - 1);
            }

            $filter = [];

            if (count($acceptableMime) > 0) {
                $filter = array_merge($filter, [MediaService::PROPERTY_MIME_TYPE => $acceptableMime]);
            }

            foreach ($class->searchInstances($filter) as $instance) {
                try{
                    $children[] = $this->getFileInfo($instance->getUri());
                }catch(\tao_models_classes_FileNotFoundException $e){
                    \common_Logger::e($e->getMessage());
                }
            }
            $data['children'] = $children;
        } else {
            $data['parent'] = $parentLink;
        }
        return $data;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaBrowser::getFileInfo
     */
    public function getFileInfo($link)
    {
        // get the media link from the resource
        $resource = $this->getResource(tao_helpers_Uri::decode($this->removeSchemaFromUriOrLink($link)));
        $properties = [
            $this->getProperty(MediaService::PROPERTY_LINK),
            $this->getProperty(MediaService::PROPERTY_MIME_TYPE),
            $this->getProperty(MediaService::PROPERTY_ALT_TEXT)
        ];

        $propertiesValues = $resource->getPropertiesValues($properties);

        $fileLink = $propertiesValues[MediaService::PROPERTY_LINK][0] ?? null;
        $mime = $propertiesValues[MediaService::PROPERTY_MIME_TYPE][0] ?? null;
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        $fileLink = $this->getFileSourceUnserializer()->unserialize($fileLink);

        if (!isset($mime, $fileLink)){
            throw new \tao_models_classes_FileNotFoundException($link);
        }

        // add the alt text to file array
        $altArray = $propertiesValues[MediaService::PROPERTY_ALT_TEXT] ?? null;
        $alt = $resource->getLabel();
        if (count($altArray) > 0) {
            $alt = (string)$altArray[0];
        }

        return [
            'name' => $resource->getLabel(),
            'uri' => self::SCHEME_NAME . \tao_helpers_Uri::encode($link),
            'mime' => (string) $mime,
            'size' => $this->getFileManagement()->getFileSize($fileLink),
            'alt' => $alt,
            'link' => $fileLink
        ];
    }

    /**
     * @param string $link
     * @return \Psr\Http\Message\StreamInterface
     * @throws \core_kernel_persistence_Exception
     * @throws \tao_models_classes_FileNotFoundException
     */
    public function getFileStream($link)
    {
        $resource = new \core_kernel_classes_Resource(\tao_helpers_Uri::decode($link));
        $fileLink = $resource->getOnePropertyValue(new \core_kernel_classes_Property(MediaService::PROPERTY_LINK));
        if (is_null($fileLink)) {
            throw new \tao_models_classes_FileNotFoundException($link);
        }
        $fileLink = $fileLink instanceof \core_kernel_classes_Resource ? $fileLink->getUri() : (string)$fileLink;
        return $this->getFileManagement()->getFileStream($fileLink);

    }

    /**
     * (non-PHPdoc)
     *
     * @see \oat\tao\model\media\MediaBrowser::download
     * @deprecated
     */
    public function download($link)
    {
        $this->logInfo('Deprecated, creates tmpfiles');
        $stream = $this->getFileStream($link);
        $filename = tempnam(sys_get_temp_dir(), 'media');
        $fh = fopen($filename, 'w');
        while (!$stream->eof()) {
            fwrite($fh, $stream->read(1048576));
        }
        fclose($fh);
        return $filename;
    }

    /**
     * @param string $link
     * @return string
     * @throws \core_kernel_persistence_Exception
     * @throws \tao_models_classes_FileNotFoundException
     */
    public function getBaseName($link)
    {
        $stream = $this->getFileStream($link);
        $filename = $stream->getMetadata('uri');

        if ($filename === 'php://temp') {
            // We are currently retrieving a remote resource (e.g. on Amazon S3).
            $fileinfo = $this->getFileInfo($link);
            $filename = $fileinfo['link'];
        }

        return basename($filename);
    }

    /**
     * Force the mime-type of a resource
     *
     * @param string $link
     * @param string $mimeType
     * @return boolean
     */
    public function forceMimeType($link, $mimeType)
    {
        $resource = new \core_kernel_classes_Resource(\tao_helpers_Uri::decode($link));
        return $resource->editPropertyValues(new \core_kernel_classes_Property(MediaService::PROPERTY_MIME_TYPE), $mimeType);
    }

    /**
     *
     * @param string $path
     * @return \core_kernel_classes_Class
     */
    private function getOrCreatePath($path)
    {
        if ($path === '') {
            $clazz = $this->getRootClass();
        } else {
            $clazz = $this->getClass(\tao_helpers_Uri::decode($path));
            if (!$clazz->isSubClassOf($this->getRootClass()) && !$clazz->equals($this->getRootClass()) && !$clazz->exists()) {
                // consider $path to be a label
                $found = false;
                foreach($this->getRootClass()->getSubClasses() as $subclass){
                    if($subclass->getLabel() === $path){
                        $found = true;
                        $clazz = $subclass;
                        break;
                    }
                }
                if (!$found) {
                    $clazz = $this->getRootClass()->createSubClass($path);
                }
            }
        }
        return $clazz;
    }

    /**
     * Get the service Locator
     *
     * @return ServiceManager
     */
    protected function getServiceLocator()
    {
        return ServiceManager::getServiceManager();
    }

    protected function getRootClassUri()
    {
        return $this->hasOption('rootClass') ? $this->getOption('rootClass') : MediaService::singleton()->getRootClass();
    }

    protected function getLang()
    {
        return $this->hasOption('lang') ? $this->getOption('lang') : '';
    }

    /**
     * @return MediaService
     */
    protected function getMediaService()
    {
        if (!$this->mediaService) {
            $this->mediaService = MediaService::singleton();
        }
        return $this->mediaService;
    }

    /**
     * @return FileManagement
     */
    protected function getFileManagement()
    {
        if (!$this->fileManagementService) {
            $this->fileManagementService = $this->getServiceLocator()->get(FileManagement::SERVICE_ID);
        }
        return $this->fileManagementService;
    }
}
