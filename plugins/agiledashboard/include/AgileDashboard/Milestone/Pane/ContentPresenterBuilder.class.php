<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_Milestone_Pane_ContentPresenterBuilder {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_Milestone_Pane_ContentRowPresenterCollection */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Pane_ContentRowPresenterCollection */
    private $done_collection;

    /** @var String */
    private $parent_item_name = '';

    /** @var String */
    private $backlog_item_name = '';

    /** @var String */
    private $can_add_backlog_item = false;

    /** @var String */
    private $new_backlog_item_url = '';

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $form_element_factory,
        PlanningFactory $planning_factory
    ) {
        $this->dao                  = $dao;
        $this->artifact_factory     = $artifact_factory;
        $this->planning_factory     = $planning_factory;
        $this->form_element_factory = $form_element_factory;

        $this->todo_collection      = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
        $this->done_collection      = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $redirect_paremeter     = new Planning_MilestoneRedirectParameter();
        $backlog_strategy       = $this->getBacklogStrategy($milestone);
        $this->redirect_to_self = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_ContentPaneInfo::IDENTIFIER);

        $this->initBacklogSettings($user, $milestone);
        $this->initCollections($user, $milestone, $backlog_strategy);

        return new AgileDashboard_Milestone_Pane_ContentPresenter(
            $this->todo_collection,
            $this->done_collection,
            $this->parent_item_name,
            $backlog_strategy->getItemName(),
            $this->can_add_backlog_item,
            $this->new_backlog_item_url
        );
    }

    private function initBacklogSettings(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $backlog_tracker = $milestone->getPlanning()->getBacklogTracker();
        if ($backlog_tracker->userCanSubmitArtifact($user)) {
            $this->can_add_backlog_item = true;
        }

        $this->new_backlog_item_url = $milestone->getArtifact()->getSubmitNewArtifactLinkedToMeUri($backlog_tracker).'&'.$this->redirect_to_self;
    }

    private function initCollections(PFUser $user, Planning_ArtifactMilestone $milestone, AgileDashboard_Milestone_Pane_ContentBacklogStrategy $backlog_strategy) {
        $artifacts        = array();
        $backlog_item_ids = array();
        foreach ($backlog_strategy->getArtifacts($user) as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[] = $artifact->getId();
        }
        $parents   = $this->getParentArtifacts($user, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids);
        foreach ($artifacts as $artifact) {
            $this->buildCollections($user, $artifact, $parents, $semantics);
        }
    }

    /**
     * @return AgileDashboard_Milestone_Pane_ContentBacklogStrategy
     */
    private function getBacklogStrategy(Planning_ArtifactMilestone $milestone) {
        $milestone_backlog_artifacts = $this->getBacklogArtifacts($milestone);
        $backlog_tracker_children    = $milestone->getPlanning()->getPlanningTracker()->getChildren();
        $backlog_tracker             = $milestone->getPlanning()->getBacklogTracker();

        if ($backlog_tracker_children) {
            $first_child_tracker         = current($backlog_tracker_children);
            $first_child_backlog_tracker = $this->planning_factory->getPlanningByPlanningTracker($first_child_tracker)->getBacklogTracker();

            if ($first_child_backlog_tracker != $backlog_tracker) {
                return new AgileDashboard_Milestone_Pane_ContentDescendantBacklogStrategy(
                    $milestone_backlog_artifacts,
                    $first_child_backlog_tracker->getName(),
                    $this->dao
                );
            }
        }

        return new AgileDashboard_Milestone_Pane_ContentSelfBacklogStrategy(
            $milestone_backlog_artifacts,
            $backlog_tracker->getName()
        );
    }

    protected function getBacklogArtifacts(Planning_ArtifactMilestone $milestone) {
        return $this->dao->getBacklogArtifacts($milestone->getArtifactId())->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
    }

    private function getParentArtifacts(PFUser $user, array $backlog_item_ids) {
        $parents         = $this->artifact_factory->getParents($backlog_item_ids);
        $parent_tracker  = $this->getParentTracker($parents);
        if ($parent_tracker) {
            $this->parent_item_name = $parent_tracker->getName();
            if ($this->userCanReadBacklogTitleField($user, $parent_tracker)) {
                $this->artifact_factory->setTitles($parents);
            } else {
                foreach ($parents as $artifact) {
                    $artifact->setTitle("");
                }
            }
        }

        return $parents;
    }

    private function getParentTracker(array $artifacts) {
        if (count($artifacts) > 0) {
            $artifact = current($artifacts);
            reset($artifacts);
            return $artifact->getTracker();
        }

        return null;
    }


    private function getArtifactsSemantics(PFUser $user, Planning_ArtifactMilestone $milestone, array $backlog_item_ids) {
        $semantics = array();
        foreach ($this->dao->getArtifactsSemantics($backlog_item_ids, $this->getSemanticsTheUserCanSee($user, $milestone)) as $row) {
            $semantics[$row['id']] = array(
                Tracker_Semantic_Title::NAME  => $row[Tracker_Semantic_Title::NAME],
                Tracker_Semantic_Status::NAME => $row[Tracker_Semantic_Status::NAME],
            );
        }

        return $semantics;
    }

    private function getSemanticsTheUserCanSee(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $backlog_tracker = $milestone->getPlanning()->getBacklogTracker();
        $semantics = array();
        if ($this->userCanReadBacklogTitleField($user ,$backlog_tracker)) {
            $semantics[] = Tracker_Semantic_Title::NAME;
        }
        if ($this->userCanReadBacklogStatusField($user, $backlog_tracker)) {
            $semantics[] = Tracker_Semantic_Status::NAME;
        }

        return $semantics;
    }

    protected function userCanReadBacklogTitleField(PFUser $user, Tracker $tracker) {
        return Tracker_Semantic_Title::load($tracker)->getField()->userCanRead($user);
    }

    protected function userCanReadBacklogStatusField(PFUser $user, Tracker $tracker) {
        return Tracker_Semantic_Status::load($tracker)->getField()->userCanRead($user);
    }

    protected function setRemainingEffort(PFUser $user, AgileDashboard_BacklogItem $backlog_item, Tracker_Artifact $artifact) {
        $field = $this->form_element_factory->getUsedFieldByNameForUser(
            $artifact->getTrackerId(),
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $user
        );
        if ($field) {
            $backlog_item->setRemainingEffort($field->fetchCardValue($artifact));
        }
    }

    private function buildCollections(PFUser $user, Tracker_Artifact $artifact, array $parents, array $semantics) {
        $artifact_id = $artifact->getId();
        $artifact->setTitle($semantics[$artifact_id][Tracker_Semantic_Title::NAME]);

        $backlog_item = new AgileDashboard_BacklogItem($artifact, $this->redirect_to_self);
        if (isset($parents[$artifact_id])) {
            $backlog_item->setParent($parents[$artifact_id]);
        }
        if ($semantics[$artifact_id][Tracker_Semantic_Status::NAME] == AgileDashboard_BacklogItemDao::STATUS_OPEN) {
            $this->setRemainingEffort($user, $backlog_item, $artifact);
            $this->todo_collection->push($backlog_item);
        } else {
            $this->done_collection->push($backlog_item);
        }
    }
}
?>