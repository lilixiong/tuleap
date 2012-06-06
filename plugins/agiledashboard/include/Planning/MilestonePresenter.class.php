<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'ArtifactTreeNodeVisitor.class.php';
require_once 'PlanningPresenter.class.php';
require_once 'MilestoneLinkPresenter.class.php';

/**
 * Provides the presentation logic for a planning milestone.
 */
class Planning_MilestonePresenter extends PlanningPresenter {
    
    /**
     * @var array of Planning_Milestone
     */
    private $available_milestones;
    
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    
    /**
     * @var Tracker_CrossSearch_SearchContentView 
     */
    private $backlog_search_view;
    
    /**
     * @var User
     */
    private $current_user;
    
    /**
     * @var string
     */
    public $planning_redirect_parameter;
    
    /**
     * Instanciates a new presenter.
     * 
     * TODO:
     *   - $planning could be retrieved from $milestone
     *   - use $milestone->getPlanning()->getAllMilestones() instead of $available_milestones ?
     * 
     * @param Planning                              $planning                    The planning (e.g. Release planning, Sprint planning).
     * @param Tracker_CrossSearch_SearchContentView $backlog_search_view         The view allowing to search through the backlog artifacts.
     * @param array                                 $available_milestones        The available milestones for a given planning (e.g. Sprint 2, Release 1.0).
     * @param Tracker_Artifact                      $milestone                   The artifact with planning being displayed right now.
     * @param User                                  $current_user                The user to which the artifact plannification UI is presented.
     * @param string                                $planning_redirect_parameter The request parameter representing the artifact being planned, used for redirection (e.g: "planning[2]=123").
     */
    public function __construct(Planning                              $planning,
                                Tracker_CrossSearch_SearchContentView $backlog_search_view,
                                array                                 $available_milestones,
                                Planning_Milestone                    $milestone, 
                                User                                  $current_user,
                                                                      $planning_redirect_parameter) {
        parent::__construct($planning);
        
        $this->milestone                   = $milestone;
        $this->available_milestones        = $available_milestones;
        $this->backlog_search_view         = $backlog_search_view;
        $this->current_user                = $current_user;
        $this->planning_redirect_parameter = $planning_redirect_parameter;
    }
    
    /**
     * @return bool
     */
    public function hasSelectedArtifact() {
        return !is_a($this->milestone, 'Planning_NoMilestone');
    }
    
    public function additionalPanes() {
        $pane = new stdClass;
        $pane->identifier = 'cardwall';
        $pane->title      = 'Card Wall';
        $pane->content    = '<div class="tracker_renderer_board " id="anonymous_element_1"><label id="tracker_renderer_board-nifty"><input type="checkbox" onclick="$(this).up(\'div.tracker_renderer_board\').toggleClassName(\'nifty\'); new Ajax.Request(\'/toggler.php?id=tracker_renderer_board-nifty\');">free-hand drawing view</label><table width="100%" border="1" bordercolor="#ccc" cellspacing="2" cellpadding="10"><colgroup><col id="tracker_renderer_board_column-100"><col id="tracker_renderer_board_column-195"><col id="tracker_renderer_board_column-196"><col id="tracker_renderer_board_column-197"><col id="tracker_renderer_board_column-198"><col id="tracker_renderer_board_column-199"><col id="tracker_renderer_board_column-200"><col id="tracker_renderer_board_column-201"><col id="tracker_renderer_board_column-202"><col id="tracker_renderer_board_column-203"><col id="tracker_renderer_board_column-204"></colgroup><thead><tr><th>None</th><th>New</th><th>Analyzed</th><th>Accepted</th><th>Under Implementation</th><th>Ready for Review</th><th>Ready for Test</th><th>In Test</th><th>Approved</th><th>Deployed</th><th>Declined</th></tr></thead><tbody><tr valign="top"><td style="position: relative; "><ul><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-69" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=69">#69</a></div><div class="tracker_renderer_board_content">Dring Dring</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-68" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=68">#68</a></div><div class="tracker_renderer_board_content">Bicyclette !?!?</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-67" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=67">#67</a></div><div class="tracker_renderer_board_content">go to skool:)</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-74" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=74">#74</a></div><div class="tracker_renderer_board_content">Histoire</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-75" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=75">#75</a></div><div class="tracker_renderer_board_content">Jolie petite histoire</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-78" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=78">#78</a></div><div class="tracker_renderer_board_content">froufrou</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-79" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=79">#79</a></div><div class="tracker_renderer_board_content">qefvqev</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-80" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=80">#80</a></div><div class="tracker_renderer_board_content">efvv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-81" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=81">#81</a></div><div class="tracker_renderer_board_content">efvv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-82" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=82">#82</a></div><div class="tracker_renderer_board_content">efvv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-83" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=83">#83</a></div><div class="tracker_renderer_board_content">dvfv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-84" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=84">#84</a></div><div class="tracker_renderer_board_content">dvfv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-88" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=88">#88</a></div><div class="tracker_renderer_board_content">ababa</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-77" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=77">#77</a></div><div class="tracker_renderer_board_content">E guaine</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-102" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=102">#102</a></div><div class="tracker_renderer_board_content">dddddd</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-76" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=76">#76</a></div><div class="tracker_renderer_board_content">Again</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-45" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=45">#45</a></div><div class="tracker_renderer_board_content">have an overview of the architecture</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-49" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=49">#49</a></div><div class="tracker_renderer_board_content">mmllm,,l</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-26" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=26">#26</a></div><div class="tracker_renderer_board_content">sell tuleap</div></div></li></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul><li class="tracker_renderer_board_postit anonymous_element_1_dummy_2" id="tracker_renderer_board_postit-25" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=25">#25</a></div><div class="tracker_renderer_board_content">shrink ui</div></div></li></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td></tr></tbody></table></div>';
        return array($pane);
    }
    
    /**
     * @return TreeNode
     */
    public function plannedArtifactsTree($child_depth = 1) {
        $root_node = null;
        
        if ($this->canAccessPlannedItem()) {
            $root_node = $this->milestone->getPlannedArtifacts();
            
            //TODO use null object pattern while still possible?
            if ($root_node) {
                Planning_ArtifactTreeNodeVisitor::build('planning-draggable-alreadyplanned')->visit($root_node);
            }
        }
        return $root_node;
    }
    
    private function canAccessPlannedItem() {
        return $this->milestone && $this->milestone->userCanView($this->current_user);
    }
    
    /**
     * @return string html
     */
    public function backlogSearchView() {
        return $this->backlog_search_view->fetch();
    }
    
    
    /**
     * @return string
     */
    public function pleaseChoose() {
        return $GLOBALS['Language']->getText('global', 'please_choose_dashed');
    }
    
    /**
     * @return array of (id, title, selected)
     */
    public function selectableArtifacts() {
        $hp             = Codendi_HTMLPurifier::instance();
        $artifacts_data = array();
        $selected_id    = $this->milestone->getArtifactId();
        
        foreach ($this->available_milestones as $milestone) {
            $artifacts_data[] = array(
                'id'       => $milestone->getArtifactId(),
                'title'    => $hp->purify($milestone->getArtifactTitle()),
                'selected' => ($milestone->getArtifactId() == $selected_id) ? 'selected="selected"' : '',
            );
        }
        return $artifacts_data;
    }
    
    /**
     * @return string
     */
    public function plannedArtifactsHelp() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_destination_help');
    }
    
    /**
     * @return string
     */
    public function planningDroppableClass() {
        if ($this->canDrop()) {
            return 'planning-droppable';
        }
        return false;
    }
    
    /**
     * @return string
     */
    public function getPlanningTrackerArtifactCreationLabel() {
        $new       = $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_artifact_new');
        $item_name = $this->planning->getPlanningTracker()->getItemName();
        
        return "$new $item_name";
    }
    
    /**
     * @return bool
     */
    public function canDrop() {
        if ($this->milestone) {
            $art_link_field = $this->milestone->getArtifact()->getAnArtifactLinkField($this->current_user);
            if ($art_link_field && $art_link_field->userCanUpdate($this->current_user)) {
                return true;
            }
        }
        return false;
    }
    
    public function hasSubMilestones() {
        return $this->milestone->hasSubMilestones();
    }
    
    public function getSubMilestones() {
        return array_map(array($this, 'getMilestoneLinkPresenter'), $this->milestone->getSubMilestones());
    }
    
    private function getMilestoneLinkPresenter(Planning_Milestone $milestone) {
        return new Planning_MilestoneLinkPresenter($milestone);
    }
    
    /**
     * @return string html
     */
    public function errorCantDrop() {
        if ($this->canDrop()) {
            return false;
        }
        return '<div class="feedback_warning">'. $GLOBALS['Language']->getText('plugin_tracker', 'must_have_artifact_link_field') .'</div>';
    }

    /**
     * @return string
     */
    public function createNewItemToPlan() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan');
    }
    
    /**
     * @return string
     */
    public function editLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
}
?>
