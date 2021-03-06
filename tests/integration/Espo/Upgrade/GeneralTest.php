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

namespace tests\integration\Nadlani\Upgrade;

class GeneralTest extends \tests\integration\Core\BaseTestCase
{
    protected $dataFile = 'InitData.php';

    protected $userName = 'admin';
    protected $password = '1';

    protected $packagePath = 'Upgrade/General.zip';

    public function testUpload()
    {
        $fileData = file_get_contents($this->normalizePath($this->packagePath));
        $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

        $upgradeManager = new \Nadlani\Core\UpgradeManager($this->getContainer());
        $upgradeId = $upgradeManager->upload($fileData);

        $this->assertStringMatchesFormat('%x', $upgradeId);
        $this->assertFileExists('data/upload/upgrades/' . $upgradeId . 'z');
        $this->assertFileExists('data/upload/upgrades/' . $upgradeId);
        //$this->assertDirectoryExists('data/upload/upgrades/' . $upgradeId);

        return $upgradeId;
    }

    public function testInstall()
    {
        $upgradeId = $this->testUpload();

        $upgradeManager = new \Nadlani\Core\UpgradeManager($this->getContainer());
        $upgradeManager->install(array('id' => $upgradeId));

        $this->assertFileNotExists('data/upload/upgrades/' . $upgradeId . 'z');
        $this->assertFileNotExists('data/upload/upgrades/' . $upgradeId);
        $this->assertFileExists('data/.backup/upgrades/' . $upgradeId);

        $this->assertFileExists('custom/Nadlani/Custom/test.php');
        $this->assertFileNotExists('vendor/zendframework');
        $this->assertFileNotExists('extension.php');
        $this->assertFileNotExists('upgrade.php');

        return $upgradeId;
    }

    /**
     * @expectedException \Nadlani\Core\Exceptions\Error
     */
    public function testUninstall()
    {
        $upgradeId = $this->testInstall();

        $upgradeManager = new \Nadlani\Core\UpgradeManager($this->getContainer());
        $upgradeManager->uninstall(array('id' => $upgradeId));
    }

    /**
     * @expectedException \Nadlani\Core\Exceptions\Error
     */
    public function testDelete()
    {
        $upgradeId = $this->testInstall();

        $upgradeManager = new \Nadlani\Core\UpgradeManager($this->getContainer());
        $upgradeManager->delete(array('id' => $upgradeId));
    }
}
