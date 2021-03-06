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

namespace tests\integration\Nadlani\Attachment;

class RemoveDuplicateTest extends \tests\integration\Core\BaseTestCase
{

    public function testRemoveDuplicate()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $fileStorageManager = $this->getContainer()->get('fileStorageManager');

        $attachment = $entityManager->getEntity('Attachment');

        $attachment->set([
            'name' => 'test.txt',
            'contents' => 'Hello Test'
        ]);

        $entityManager->saveEntity($attachment);

        $copy = $entityManager->getRepository('Attachment')->getCopiedAttachment($attachment);

        $entityManager->removeEntity($copy);

        $this->assertTrue($fileStorageManager->isFile($attachment));
    }

    public function testRemoveOriginal()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $fileStorageManager = $this->getContainer()->get('fileStorageManager');

        $attachment = $entityManager->getEntity('Attachment');

        $attachment->set([
            'name' => 'test.txt',
            'contents' => 'Hello Test'
        ]);

        $entityManager->saveEntity($attachment);

        $copy = $entityManager->getRepository('Attachment')->getCopiedAttachment($attachment);

        $entityManager->removeEntity($attachment);

        $this->assertTrue($fileStorageManager->isFile($copy));
    }

    public function testRemoveOriginalAndDuplicate()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $fileStorageManager = $this->getContainer()->get('fileStorageManager');

        $attachment = $entityManager->getEntity('Attachment');

        $attachment->set([
            'name' => 'test.txt',
            'contents' => 'Hello Test'
        ]);

        $entityManager->saveEntity($attachment);

        $copy = $entityManager->getRepository('Attachment')->getCopiedAttachment($attachment);

        $entityManager->removeEntity($attachment);
        $entityManager->removeEntity($copy);

        $this->assertFalse($fileStorageManager->isFile($copy));
    }
}
