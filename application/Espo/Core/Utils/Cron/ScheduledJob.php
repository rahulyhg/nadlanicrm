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

namespace Nadlani\Core\Utils\Cron;
use \PDO;
use \Nadlani\Core\Utils\Config;
use \Nadlani\Core\ORM\EntityManager;

class ScheduledJob
{
    private $config;

    private $entityManager;

    public function __construct(Config $config, EntityManager $entityManager)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Get active Scheduler Job List
     *
     * @return EntityCollection
     */
    public function getActiveScheduledJobList()
    {
        return $this->getEntityManager()->getRepository('ScheduledJob')->select([
            'id', 'scheduling', 'job', 'name'
        ])->where([
            'status' => 'Active'
        ])->find();
    }

    /**
     * Add record to ScheduledJobLogRecord about executed job
     *
     * @param string $scheduledJobId
     * @param string $status
     *
     * @return string ID of created ScheduledJobLogRecord
     */
    public function addLogRecord($scheduledJobId, $status, $runTime = null, $targetId = null, $targetType = null)
    {
        if (!isset($runTime)) {
            $runTime = date('Y-m-d H:i:s');
        }

        $entityManager = $this->getEntityManager();

        $scheduledJob = $entityManager->getEntity('ScheduledJob', $scheduledJobId);

        if (!$scheduledJob) {
            return;
        }

        $scheduledJob->set('lastRun', $runTime);
        $entityManager->saveEntity($scheduledJob, ['silent' => true]);

        $scheduledJobLog = $entityManager->getEntity('ScheduledJobLogRecord');
        $scheduledJobLog->set(array(
            'scheduledJobId' => $scheduledJobId,
            'name' => $scheduledJob->get('name'),
            'status' => $status,
            'executionTime' => $runTime,
            'targetId' => $targetId,
            'targetType' => $targetType
        ));
        $scheduledJobLogId = $entityManager->saveEntity($scheduledJobLog);

        return $scheduledJobLogId;
    }
}