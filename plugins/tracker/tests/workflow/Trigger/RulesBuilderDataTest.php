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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Workflow_Trigger_RulesBuilderDataTest extends TuleapTestCase {

    public function itHasNoData() {
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(array(), array());
        $this->assertEqual(
            $rules_builder_data->toJson(),
            '{"targets":[],"conditions":["'.Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE.'","'.Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF.'"],"triggers":[]}'
        );
    }

    public function itHasATargetFieldOfTheTrackerOnWhichRulesWillApply() {
        $target_field = aSelectBoxField()
            ->withId(30)
            ->withLabel("Status")
            ->withBind(new Tracker_FormElement_Field_List_Bind_Null(aMockField()->build()))
            ->build();
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(array($target_field), array());

        $result = json_decode($rules_builder_data->toJson());
        $this->assertCount($result->targets, 1);
        $this->assertEqual($result->targets[0]->id, 30);
        $this->assertEqual($result->targets[0]->label, "Status");
    }

    public function itHasATargetFieldWithValues() {
        $target_field = aSelectBoxField()->withBind(
            aBindStatic()->withValues(array(
                aBindStaticValue()->withId(345)->withLabel('Todo')->build(),
            ))->build()
        )->build();
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(array($target_field), array());

        $result = json_decode($rules_builder_data->toJson());
        $this->assertCount($result->targets[0]->values, 1);
        $this->assertEqual($result->targets[0]->values[0]->id, 345);
        $this->assertEqual($result->targets[0]->values[0]->caption, 'Todo');
    }

    public function itHasATriggerTracker() {
        $triggering_field = new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            aTracker()->withId(90)->withName('Tasks')->build(),
            array()
        );

        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(array(), array($triggering_field));
        $result = json_decode($rules_builder_data->toJson());
        $this->assertCount($result->triggers, 1);
        $this->assertEqual($result->triggers[0]->id, 90);
        $this->assertEqual($result->triggers[0]->name, 'Tasks');
        $this->assertEqual($result->triggers[0]->fields, array());
    }

     public function itHasATriggerTrackerWithAField() {
         $triggering_field = new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            aTracker()->withId(90)->withName('Tasks')->build(),
            array(
                aSelectBoxField()
                    ->withId(91)
                    ->withLabel("Status")
                    ->withBind(new Tracker_FormElement_Field_List_Bind_Null(aMockField()->build()))
                    ->build()
            )
        );

        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(array(), array($triggering_field));
        $result = json_decode($rules_builder_data->toJson());
        $trigger = $result->triggers[0];
        $this->assertCount($trigger->fields, 1);
        $this->assertEqual($trigger->fields[0]->id, 91);
        $this->assertEqual($trigger->fields[0]->label, "Status");
    }
}

?>
