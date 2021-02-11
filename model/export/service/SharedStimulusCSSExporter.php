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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\export\service;

use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;

use oat\taoMediaManager\model\fileManagement\FileManagement;
use oat\taoMediaManager\model\sharedStimulus\service\StoreService;
use oat\taoMediaManager\model\sharedStimulus\specification\SharedStimulusResourceSpecification;
use ZipArchive;

class SharedStimulusCSSExporter extends ConfigurableService
{
    use OntologyAwareTrait;

    /**
     * stylesheets folder name inside zip archive
     */
    public const CSS_ZIP_DIR_NAME = 'CSS';

    public function pack(core_kernel_classes_Resource $mediaResource, string $link, ZipArchive $zip): void
    {
        if (!$this->getSharedStimulusResourceSpecification()->isSatisfiedBy($mediaResource)) {
            return;
        }

        $fileManager = $this->getFileManagement();
        $cssPath = dirname($link) . DIRECTORY_SEPARATOR . StoreService::CSS_DIR_NAME;

        if (!$fileManager->pathExists($cssPath)) {
            return;
        }

        $files = $fileManager->fetchDirectory($cssPath);
        if (!count($files)) {
            return;
        }

        $zip->addEmptyDir(self::CSS_ZIP_DIR_NAME);
        foreach ($files as $file) {
            $content = $this->getFileContent($cssPath . DIRECTORY_SEPARATOR . $file['basename']);
            $zip->addFromString(self::CSS_ZIP_DIR_NAME . DIRECTORY_SEPARATOR . $file['basename'], $content);
        }
    }

    private function getFileContent($path): string
    {
        return $this->getFileManagement()->getFileStream($path)->getContents();
    }


    private function getFileManagement(): FileManagement
    {
        return $this->getServiceManager()->get(FileManagement::SERVICE_ID);
    }

    private function getSharedStimulusResourceSpecification(): SharedStimulusResourceSpecification
    {
        return $this->getServiceLocator()->get(SharedStimulusResourceSpecification::class);
    }
}
