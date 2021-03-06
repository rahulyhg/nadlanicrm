<?php
/************************************************************************
 * This file is part of NadlaniCrm.
 *
 * NadlaniCrm - Open Source CRM application.
 * Copyright (C) 2014-2018 Pablo Rotem
 * Website: https://www.facebook.com/sites4u2
 *
 * NadlaniCrm is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NadlaniCrm is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NadlaniCrm. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "NadlaniCrm" word.
 ************************************************************************/

namespace Nadlani\EntryPoints;

use \Nadlani\Core\Exceptions\NotFound;
use \Nadlani\Core\Exceptions\Forbidden;
use \Nadlani\Core\Exceptions\BadRequest;

class ChangePassword extends \Nadlani\Core\EntryPoints\Base
{
    public static $authRequired = false;

    public function run()
    {
        $requestId = $_GET['id'];
        if (empty($requestId)) {
            throw new BadRequest();
        }

        $config = $this->getConfig();
        $themeManager = $this->getThemeManager();

        $p = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where(array(
            'requestId' => $requestId
        ))->findOne();

        if (!$p) {
            throw new NotFound();
        }

        $runScript = "
            app.getController('PasswordChangeRequest', function (controller) {
                controller.doAction('passwordChange', '{$requestId}');
            });
        ";

        $this->getClientManager()->display($runScript);
    }

    protected function getThemeManager()
    {
        return $this->getContainer()->get('themeManager');
    }
}

