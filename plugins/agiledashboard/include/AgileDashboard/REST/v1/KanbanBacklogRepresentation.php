<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanItemDao;
use Tracker_ArtifactFactory;
use PFUser;
use Tuleap\REST\JsonCast;
use Tracker_Artifact;
use UserManager;
use EventManager;

class KanbanBacklogRepresentation {

    /** @var array */
    public $collection;

    /** @var int */
    public $total_size;

    public function build(PFUser $user, AgileDashboard_Kanban $kanban, $limit, $offset) {
        $dao     = new AgileDashboard_KanbanItemDao();
        $factory = Tracker_ArtifactFactory::instance();
        $data    = $dao->searchPaginatedBacklogItemsByTrackerId($kanban->getTrackerId(), $limit, $offset);

        $this->total_size = (int) $dao->foundRows();
        $this->collection = array();
        foreach ($data as $row) {
            $artifact = $factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $this->collection[] = array(
                    'id'          => JsonCast::toInt($artifact->getId()),
                    'label'       => $artifact->getTitle(),
                    'card_fields' => $this->getArtifactCardFields($artifact)
                );
            }
        }
    }

    private function getArtifactCardFields(Tracker_Artifact $artifact) {
        $current_user         = UserManager::instance()->getCurrentUser();
        $card_fields_semantic = $this->getCardFieldsSemantic($artifact);
        $card_fields          = array();

        foreach($card_fields_semantic->getFields() as $field) {
            if ($field->userCanRead($current_user)) {
                $card_fields[] = $field->getFullRESTValue($current_user, $artifact->getLastChangeset());
            }
        }

        return $card_fields;
    }

    private function getCardFieldsSemantic(Tracker_Artifact $artifact) {
        $card_fields_semantic = null;

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_GET_CARD_FIELDS,
            array(
                'tracker'              => $artifact->getTracker(),
                'card_fields_semantic' => &$card_fields_semantic
            )
        );

        return $card_fields_semantic;
    }
}
