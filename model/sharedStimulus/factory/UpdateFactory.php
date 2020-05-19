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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model\sharedStimulus\factory;

use InvalidArgumentException;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\taoMediaManager\model\sharedStimulus\UpdateCommand;
use Psr\Http\Message\ServerRequestInterface;

class UpdateFactory extends ConfigurableService
{

    public function patchStimulusByRequest(ServerRequestInterface $request, User $user): UpdateCommand
    {
        $parsedBody = json_decode((string)$request->getBody(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Incorrect request format');
        }

        return new UpdateCommand(
            $parsedBody['id'],
            $parsedBody['body'],
            $user->getIdentifier()
        );
    }
}
