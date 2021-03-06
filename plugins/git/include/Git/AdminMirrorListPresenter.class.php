<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

class Git_AdminMirrorListPresenter extends Git_AdminMirrorPresenter {

    const TEMPLATE = 'admin-plugin';

    public $see_all = true;

    public $list_of_mirrors;

    public function __construct($title, CSRFSynchronizerToken $csrf, array $list_of_mirrors) {
        parent::__construct($title, $csrf);

        $this->list_of_mirrors = $list_of_mirrors;
        $this->btn_submit      = $GLOBALS['Language']->getText('global', 'btn_submit');
    }

    public function add_mirror() {
        return $GLOBALS['Language']->getText('plugin_git','add_mirror');
    }

    public function mirror_section_title() {
        return $GLOBALS['Language']->getText('plugin_git','mirror_section_title');
    }

    public function url_label() {
        return $GLOBALS['Language']->getText('plugin_git','url_label');
    }

    public function owner_label() {
        return $GLOBALS['Language']->getText('plugin_git','owner_label');
    }

    public function ssh_key_label() {
        return $GLOBALS['Language']->getText('plugin_git','ssh_key_label');
    }

    public function pwd_label() {
        return $GLOBALS['Language']->getText('plugin_git','pwd_label');
    }

    public function mirrored_repo_label() {
        return $GLOBALS['Language']->getText('plugin_git','mirrored_repo_label');
    }

    public function list_of_mirrors_not_empty() {
        return count($this->list_of_mirrors) > 0;
    }
}
