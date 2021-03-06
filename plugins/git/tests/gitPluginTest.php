<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

class GitPlugin4Tests extends GitPlugin {
    public function dump_ssh_keys_gerrit(array $params) {
        parent::dump_ssh_keys_gerrit($params);
    }
}

class GitPlugin_PropagateUserKeysToGerritTest extends TuleapTestCase {
    
    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getGerritServerFactory'
        );
        $this->plugin = partial_mock('GitPlugin4Tests', $mocked_methods, array($id));

        $this->user_account_manager = mock('Git_UserAccountManager');
        $this->plugin->setUserAccountManager($this->user_account_manager);

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->plugin)->getGerritServerFactory()->returns($this->gerrit_server_factory);

        $this->logger = mock('BackendLogger');
        $this->plugin->setLogger($this->logger);

        $this->user = mock('PFUser');
    }


    public function testItDoesntSynchronizeSSHKeysOnRootDaily() {
        $params = array();
        expect($this->user_account_manager)->synchroniseSSHKeys()->never();

        $this->plugin->dump_ssh_keys_gerrit($params);
    }

    public function testItLogsAnErrorIfNoUserIsPassed() {
        $params = array(
            'original_keys' => '',
        );
        
        expect($this->logger)->error()->once();
        $this->plugin->dump_ssh_keys_gerrit($params);
    }

    public function testItLogsAnErrorIfUserIsInvalid() {
        $params = array(
            'user' => 'me',
            'original_keys' => '',
        );

        expect($this->logger)->error()->once();
        $this->plugin->dump_ssh_keys_gerrit($params);
    }

    public function itTransformsEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() {
        $original_keys = array();
        $new_keys = array();

        $params = array(
            'user'          => $this->user,
            'original_keys' => '',
        );

        stub($this->user)->getAuthorizedKeysArray()->returns($new_keys);

        expect($this->logger)->error()->never();
        expect($this->user_account_manager)->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $this->user
            )->once();

        $this->plugin->dump_ssh_keys_gerrit($params);
    }

    public function itTransformsNonEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() {
        $new_keys      = array();
        $original_keys = array(
            'abcdefg',
            'wxyz',
        );

        $params = array(
            'user'          => $this->user,
            'original_keys' => 'abcdefg'.PFUser::SSH_KEY_SEPARATOR.'wxyz',
        );

        stub($this->user)->getAuthorizedKeysArray()->returns($new_keys);

        expect($this->logger)->error()->never();
        expect($this->user_account_manager)->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $this->user
            )->once();

        $this->plugin->dump_ssh_keys_gerrit($params);
    }

    public function itLogsAnErrorIfSSHKeySynchFails() {
        $params = array(
            'user'          => $this->user,
            'original_keys' => '',
        );

        $this->user_account_manager->throwOn('synchroniseSSHKeys', new Git_UserSynchronisationException());
        
        expect($this->logger)->error()->once();

        $this->plugin->dump_ssh_keys_gerrit($params);
    }
}


class GitPlugin_GetRemoteServersForUserTest extends TuleapTestCase {

    /**
     *
     * @var GitPlugin
     */
    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getGerritServerFactory'
        );
        $this->plugin = partial_mock('GitPlugin', $mocked_methods, array($id));

        $this->user_account_manager = mock('Git_UserAccountManager');
        $this->plugin->setUserAccountManager($this->user_account_manager);

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->plugin)->getGerritServerFactory()->returns($this->gerrit_server_factory);

        $this->logger = mock('BackendLogger');
        $this->plugin->setLogger($this->logger);

        $this->user = mock('PFUser');

        $_POST['ssh_key_push'] = true;
    }

    public function testItDoesNotPushKeysIfNoUserIsPassed() {
        $params = array(
            'html' => '',
        );

        expect($this->user_account_manager)->pushSSHKeys()->never();
        $this->plugin->getRemoteServersForUser($params);
    }

    public function tesItDoesNotPushKeysIfUserIsInvalid() {
        $params = array(
            'user' => 'me',
            'html' => '',
        );

        expect($this->user_account_manager)->pushSSHKeys()->never();
        $this->plugin->getRemoteServersForUser($params);
    }

    public function itLogsAnErrorIfSSHKeyPushFails() {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );
        
        $this->user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        expect($this->logger)->error()->once();

        $this->plugin->getRemoteServersForUser($params);
    }

    public function itAddsResponseFeedbackIfSSHKeyPushFails() {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );

        $this->user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        $response = mock('Response');
        $GLOBALS['Response'] = $response;
        expect($response)->addFeedback()->once();

        $this->plugin->getRemoteServersForUser($params);
    }
}

class GitPlugin_Post_System_Events extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getManifestManager',
            'getGitoliteDriver',
            'getLogger',
        );
        $this->plugin           = partial_mock('GitPlugin', $mocked_methods, array($id));
        $this->manifest_manager = mock('Git_Mirror_ManifestManager');
        $this->gitolite_driver  = mock('Git_GitoliteDriver');

        stub($this->plugin)->getManifestManager()->returns($this->manifest_manager);
        stub($this->plugin)->getGitoliteDriver()->returns($this->gitolite_driver);
        stub($this->plugin)->getLogger()->returns(mock('TruncateLevelLogger'));
    }

    public function itProcessGrokmirrorManifestUpdateInPostSystemEventsActions() {
        expect($this->gitolite_driver)
            ->commit()
            ->once();

        expect($this->gitolite_driver)
            ->push()
            ->once();

        expect($this->manifest_manager)
            ->triggerUpdate()
            ->once();

        $params = array(
            'executed_events_ids' => array(),
            'queue_name' => 'git'
        );

        $this->plugin->post_system_events_actions($params);
    }

    public function itDoesNotProcessPostSystemEventsActionsIfNotGitRelated() {
        expect($this->gitolite_driver)
        ->commit()
        ->never();

        expect($this->gitolite_driver)
        ->push()
        ->never();

        expect($this->manifest_manager)
        ->triggerUpdate()
        ->never();

        $params = array(
            'executed_events_ids' => array(),
            'queue_name' => 'owner'
        );

        $this->plugin->post_system_events_actions($params);
    }
}
