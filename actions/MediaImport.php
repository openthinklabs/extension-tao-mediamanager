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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoMediaManager\actions;

use oat\taoMediaManager\model\FileImporter;
use oat\taoMediaManager\model\MediaService;
use oat\taoMediaManager\model\SharedStimulusImporter;

/**
 * This controller provide the actions to import medias
 */
class MediaImport extends \tao_actions_Import
{

    private $importHandlers;

    public function __construct()
    {

        parent::__construct();
        $this->service = MediaService::singleton();
    }

    /**
     * overwrite the parent index to add the import handlers
     *
     * @requiresRight id WRITE
     * @see tao_actions_Import::index()
     */
    public function index()
    {
        $id = null;
        if ($this->hasRequestParameter('classUri')) {
            $id = \tao_helpers_Uri::decode($this->getRequestParameter('classUri'));
        }

        $this->setAvailableImportHandlers($id);
        parent::index();

    }

    /**
     * @requiresRight id WRITE
     */
    public function editMedia()
    {
        $id = null;
        if ($this->hasRequestParameter('instanceUri')) {
            $id = $this->getRequestParameter('instanceUri');
        } else {
            $id = $this->getRequestParameter('id');
        }
        $this->setAvailableImportHandlers($id);
        parent::index();
    }

    protected function getAvailableImportHandlers()
    {
        return $this->importHandlers;
    }

    protected function setAvailableImportHandlers($id)
    {
        $this->importHandlers = array(
            new FileImporter($id),
            new SharedStimulusImporter($id)
        );

        return $this;
    }


    /**
     * get the main class
     * @return \core_kernel_classes_Class
     */
    protected function getRootClass()
    {
        return new \core_kernel_classes_Class(MEDIA_URI);
    }
}
