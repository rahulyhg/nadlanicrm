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

namespace Nadlani\Core\Utils\Authentication;

use Nadlani\Core\Exceptions\Error;
use Nadlani\Core\Utils\Config;
use Nadlani\Core\ORM\EntityManager;
use Nadlani\Core\Utils\Auth;

class LDAP extends Nadlani
{
    private $utils;

    private $ldapClient;

    /**
     * User field name  => option name (LDAP attribute)
     *
     * @var array
     */
    protected $ldapFieldMap = array(
        'userName' => 'userNameAttribute',
        'firstName' => 'userFirstNameAttribute',
        'lastName' => 'userLastNameAttribute',
        'title' => 'userTitleAttribute',
        'emailAddress' => 'userEmailAddressAttribute',
        'phoneNumber' => 'userPhoneNumberAttribute',
    );

    /**
     * User field name => option name
     *
     * @var array
     */
    protected $userFieldMap = array(
        'teamsIds' => 'userTeamsIds',
        'defaultTeamId' => 'userDefaultTeamId',
    );

    /**
     * User field name => option name
     *
     * @var array
     */
    protected $portalUserFieldMap = array(
        'portalsIds' => 'portalUserPortalsIds',
        'portalRolesIds' => 'portalUserRolesIds',
    );

    public function __construct(Config $config, EntityManager $entityManager, Auth $auth)
    {
        parent::__construct($config, $entityManager, $auth);

        $this->utils = new LDAP\Utils($config);
    }

    protected function getUtils()
    {
        return $this->utils;
    }

    protected function getLdapClient()
    {
        if (!isset($this->ldapClient)) {
            $options = $this->getUtils()->getLdapClientOptions();

            try {
                $this->ldapClient = new LDAP\Client($options);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('LDAP error: ' . $e->getMessage());
            }
        }

        return $this->ldapClient;
    }

    /**
     * LDAP login
     *
     * @param  string $username
     * @param  string $password
     * @param  \Nadlani\Entities\AuthToken $authToken
     *
     * @return \Nadlani\Entities\User | null
     */
    public function login($username, $password, \Nadlani\Entities\AuthToken $authToken = null, $params = [], $request)
    {
        if (!$password) return;

        $isPortal = !empty($params['isPortal']);

        if ($authToken) {
            return $this->loginByToken($username, $authToken);
        }

        if ($isPortal) {
            $useLdapAuthForPortalUser = $this->getUtils()->getOption('portalUserLdapAuth');
            if (!$useLdapAuthForPortalUser) {
                return parent::login($username, $password, $authToken, $params, $request);
            }
        }

        $ldapClient = $this->getLdapClient();

        /* Login LDAP system user (ldapUsername, ldapPassword) */
        try {
            $ldapClient->bind();
        } catch (\Exception $e) {
            $options = $this->getUtils()->getLdapClientOptions();
            $GLOBALS['log']->error('LDAP: Could not connect to LDAP server ['.$options['host'].'], details: ' . $e->getMessage());

            $adminUser = $this->adminLogin($username, $password);
            if (!isset($adminUser)) {
                return null;
            }

            $GLOBALS['log']->info('LDAP: Administrator ['.$username.'] was logged in by Nadlani method.');
        }

        if (!isset($adminUser)) {
            try {
                $userDn = $this->findLdapUserDnByUsername($username);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Error while finding DN for ['.$username.'], details: ' . $e->getMessage() . '.');
            }

            if (!isset($userDn)) {
                $GLOBALS['log']->error('LDAP: Authentication failed for user ['.$username.'], details: user is not found.');

                $adminUser = $this->adminLogin($username, $password);
                if (!isset($adminUser)) {
                    return null;
                }

                $GLOBALS['log']->info('LDAP: Administrator ['.$username.'] was logged in by Nadlani method.');
            }

            $GLOBALS['log']->debug('User ['.$username.'] is found with this DN ['.$userDn.'].');

            try {
                $ldapClient->bind($userDn, $password);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('LDAP: Authentication failed for user ['.$username.'], details: ' . $e->getMessage());
                return null;
            }
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne([
            'whereClause' => [
                'userName' => $username,
                'type!=' => ['api', 'system']
            ]
        ]);

        if (!isset($user) && $this->getUtils()->getOption('createNadlaniUser')) {
            $userData = $ldapClient->getEntry($userDn);
            $user = $this->createUser($userData, $isPortal);
        }

        return $user;
    }

    /**
     * Login by authorization token
     *
     * @param  string $username
     * @param  \Nadlani\Entities\AuthToken $authToken
     *
     * @return \Nadlani\Entities\User | null
     */
    protected function loginByToken($username, \Nadlani\Entities\AuthToken $authToken = null)
    {
        if (!isset($authToken)) {
            return null;
        }

        $userId = $authToken->get('userId');
        $user = $this->getEntityManager()->getEntity('User', $userId);

        $tokenUsername = $user->get('userName');
        if (strtolower($username) != strtolower($tokenUsername)) {
            $GLOBALS['log']->alert('Unauthorized access attempt for user ['.$username.'] from IP ['.$_SERVER['REMOTE_ADDR'].']');
            return null;
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
            )
        ));

        return $user;
    }

    /**
     * Login user with administrator rights
     *
     * @param  string $username
     * @param  string $password
     * @return \Nadlani\Entities\User | null
     */
    protected function adminLogin($username, $password)
    {
        $hash = $this->getPasswordHash()->hash($password);

        $user = $this->getEntityManager()->getRepository('User')->findOne([
            'whereClause' => [
                'userName' => $username,
                'password' => $hash,
                'type' => ['admin', 'super-admin']
            ]
        ]);

        return $user;
    }

    /**
     * Create Nadlani user with data gets from LDAP server
     *
     * @param  array $userData LDAP entity data
     * @param  boolean $isPortal Is portal user
     *
     * @return \Nadlani\Entities\User
     */
    protected function createUser(array $userData, $isPortal = false)
    {
        $GLOBALS['log']->info('Creating new user ...');
        $data = array();

        // show full array of the LDAP user
        $GLOBALS['log']->debug('LDAP: user data: ' .print_r($userData, true));

        //set values from ldap server
        $ldapFields = $this->loadFields('ldap');
        foreach ($ldapFields as $espo => $ldap) {
            $ldap = strtolower($ldap);
            if (isset($userData[$ldap][0])) {
                $GLOBALS['log']->debug('LDAP: Create a user wtih ['.$espo.'] = ['.$userData[$ldap][0].'].');
                $data[$espo] = $userData[$ldap][0];
            }
        }

        //set user fields
        if ($isPortal) {
            $userFields = $this->loadFields('portalUser');
            $userFields['type'] = 'portal';
        } else {
            $userFields = $this->loadFields('user');
        }

        foreach ($userFields as $fieldName => $fieldValue) {
            $data[$fieldName] = $fieldValue;
        }

        $this->getAuth()->useNoAuth();

        $user = $this->getEntityManager()->getEntity('User');
        $user->set($data);

        $this->getEntityManager()->saveEntity($user);

        return $this->getEntityManager()->getEntity('User', $user->id);
    }

    /**
     * Find LDAP user DN by his username
     *
     * @param  string $username
     *
     * @return string | null
     */
    protected function findLdapUserDnByUsername($username)
    {
        $ldapClient = $this->getLdapClient();
        $options = $this->getUtils()->getOptions();

        $loginFilterString = '';
        if (!empty($options['userLoginFilter'])) {
            $loginFilterString = $this->convertToFilterFormat($options['userLoginFilter']);
        }

        $searchString = '(&(objectClass='.$options['userObjectClass'].')('.$options['userNameAttribute'].'='.$username.')'.$loginFilterString.')';
        $result = $ldapClient->search($searchString, null, LDAP\Client::SEARCH_SCOPE_SUB);
        $GLOBALS['log']->debug('LDAP: user search string: "' . $searchString . '"');

        foreach ($result as $item) {
            return $item["dn"];
        }
    }

    /**
     * Check and convert filter item into LDAP format
     *
     * @param  string $filter E.g. "memberof=CN=externalTesters,OU=groups,DC=espo,DC=local"
     *
     * @return string
     */
    protected function convertToFilterFormat($filter)
    {
        $filter = trim($filter);
        if (substr($filter, 0, 1) != '(') {
            $filter = '(' . $filter;
        }
        if (substr($filter, -1) != ')') {
            $filter = $filter . ')';
        }
        return $filter;
    }

    /**
     * Load fields for a user
     *
     * @param  string $type
     *
     * @return array
     */
    protected function loadFields($type)
    {
        $options = $this->getUtils()->getOptions();

        $typeMap = $type . 'FieldMap';

        $fields = array();
        foreach ($this->$typeMap as $fieldName => $fieldValue) {
            if (isset($options[$fieldValue])) {
                $fields[$fieldName] = $options[$fieldValue];
            }
        }

        return $fields;
    }
}
