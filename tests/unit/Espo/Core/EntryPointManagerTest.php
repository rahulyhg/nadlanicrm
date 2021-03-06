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

namespace tests\unit\Nadlani\Core;

use tests\unit\ReflectionHelper;


class EntryPointManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $filesPath= 'tests/unit/testData/EntryPoints';

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\\Nadlani\\Core\\Container')->disableOriginalConstructor()->getMock();

        $this->object = new \Nadlani\Core\EntryPointManager($this->objects['container']);

        $this->reflection = new ReflectionHelper($this->object);

        $fileManager = new \Nadlani\Core\Utils\File\Manager();
        $this->reflection->setProperty('fileManager', $fileManager);

        $this->reflection->setProperty('cacheFile', 'tests/unit/testData/EntryPoints/cache/entryPoints.php');
        $this->reflection->setProperty('paths', array(
            'corePath' => 'tests/unit/testData/EntryPoints/Nadlani/EntryPoints',
            'modulePath' => 'tests/unit/testData/EntryPoints/Nadlani/Modules/Crm/EntryPoints',
        ));
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    function testGet()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\unit\testData\EntryPoints\Nadlani\EntryPoints\Test',
        ));

        $this->assertEquals('\tests\unit\testData\EntryPoints\Nadlani\EntryPoints\Test', $this->reflection->invokeMethod('getClassName', array('test')) );
    }


    function testRun()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\unit\testData\EntryPoints\Nadlani\EntryPoints\Test',
        ));

        $this->assertNull( $this->reflection->invokeMethod('run', array('test')) );
    }

}

?>
