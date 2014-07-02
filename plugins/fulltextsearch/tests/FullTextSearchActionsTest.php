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

require_once dirname(__FILE__) .'/../include/autoload.php';
require_once dirname(__FILE__) .'/../../docman/include/Docman_PermissionsItemManager.class.php';
require_once dirname(__FILE__) .'/../../docman/include/Docman_MetadataFactory.class.php';
require_once dirname(__FILE__).'/Constants.php';
require_once dirname(__FILE__).'/builders/Parameters_Builder.php';

class FullTextSearchDocmanActionsTests extends TuleapTestCase {
    protected $client;
    protected $actions;
    protected $params;
    protected $permissions_manager;

    public function setUp() {
        parent::setUp();

        $this->client = partial_mock(
            'ElasticSearch_IndexClientFacade',
            array('index', 'update', 'delete', 'getMapping', 'setMapping')
        );

        $this->permissions_manager = mock('Docman_PermissionsItemManager');

        $metadata01 = stub('Docman_Metadata')->getId()->returns(1);
        $metadata02 = stub('Docman_Metadata')->getId()->returns(2);

        $hardcoded_metadata_title = stub('Docman_Metadata')->getLabel()->returns('title');
        stub($hardcoded_metadata_title)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $hardcoded_metadata_description = stub('Docman_Metadata')->getLabel()->returns('description');
        stub($hardcoded_metadata_description)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $hardcoded_metadata_owner = stub('Docman_Metadata')->getLabel()->returns('owner');
        stub($hardcoded_metadata_owner)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $hardcoded_metadata_create_date = stub('Docman_Metadata')->getLabel()->returns('create_date');
        stub($hardcoded_metadata_create_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata_update_date = stub('Docman_Metadata')->getLabel()->returns('update_date');
        stub($hardcoded_metadata_update_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata_status = stub('Docman_Metadata')->getLabel()->returns('status');
        stub($hardcoded_metadata_status)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $hardcoded_metadata_obsolescence_date = stub('Docman_Metadata')->getLabel()->returns('obsolescence_date');
        stub($hardcoded_metadata_obsolescence_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata = array(
            $hardcoded_metadata_title,
            $hardcoded_metadata_description,
            $hardcoded_metadata_owner,
            $hardcoded_metadata_create_date,
            $hardcoded_metadata_update_date,
            $hardcoded_metadata_update_date,
            $hardcoded_metadata_obsolescence_date
        );

        $this->item = aDocman_File()
            ->withId(101)
            ->withTitle('Coin')
            ->withDescription('Duck typing')
            ->withGroupId(200)
            ->build();

        stub($this->item)->getCreateDate()->returns(1403160945);
        stub($this->item)->getUpdateDate()->returns(1403160949);

        $first_search_type = array(
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            PLUGIN_DOCMAN_METADATA_TYPE_STRING
        );
        $this->metadata_factory = stub('Docman_MetadataFactory')->getRealMetadataList(false, $first_search_type)->returns(
            array($metadata01, $metadata02)
        );

        stub($this->metadata_factory)->getHardCodedMetadataList()->returns($hardcoded_metadata);
        stub($this->metadata_factory)->getMetadataValue($this->item, $metadata01)->returns('val01');
        stub($this->metadata_factory)->getMetadataValue($this->item, $metadata02)->returns('val02');

        $this->approval_table_factories_factory = mock('Docman_ApprovalTableFactoriesFactory');
        stub($this->approval_table_factories_factory)->getSpecificFactoryFromItem($this->item)->returns(mock('Docman_ApprovalTableFileFactory'));

        $this->request_data_factory = new ElasticSearch_1_2_RequestDocmanDataFactory(
            $this->metadata_factory,
            $this->permissions_manager,
            $this->approval_table_factories_factory
        );

        $this->actions = new FullTextSearchDocmanActions(
            $this->client,
            $this->request_data_factory,
            mock('BackendLogger')
        );

        stub($this->permissions_manager)
            ->exportPermissions($this->item)
            ->returns(array(3, 102));

        $this->version = stub('Docman_Version')
            ->getPath()
            ->returns(dirname(__FILE__) .'/_fixtures/file.txt');

        $this->params = aSetOfParameters()
            ->withItem($this->item)
            ->withVersion($this->version)
            ->build();
    }

    public function itCallIndexOnClientWithRightParametersWithObsolescenceDate() {
        stub($this->item)->getObsolescenceDate()->returns(1403160959);

        $second_search_type = array(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        stub($this->metadata_factory)->getRealMetadataList(false, $second_search_type)->returns(
            array()
        );

        stub($this->client)->getMapping()->returns(array());

        $expected = array(
            200,
            101,
            array(
                'id'                      => 101,
                'group_id'                => 200,
                'title'                   => 'Coin',
                'description'             => 'Duck typing',
                'create_date'             => '2014-06-19',
                'update_date'             => '2014-06-19',
                'permissions'             => array(3, 102),
                'file'                    => 'aW5kZXggbWUK',
                'approval_table_comments' => array(),
                'obsolescence_date'       => '2014-06-19',
                'property_1'              => 'val01',
                'property_2'              => 'val02',
               ),
        );
        $this->client->expectOnce('index', $expected);

        $this->actions->indexNewDocument($this->item, $this->version);
    }

    public function itCallIndexOnClientWithRightParametersWithoutObsolescenceDate() {
        stub($this->item)->getObsolescenceDate()->returns(0);

        $second_search_type = array(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        stub($this->metadata_factory)->getRealMetadataList(false, $second_search_type)->returns(
            array()
        );

        stub($this->client)->getMapping()->returns(array());

        $expected = array(
            200,
            101,
            array(
                'id'                      => 101,
                'group_id'                => 200,
                'title'                   => 'Coin',
                'description'             => 'Duck typing',
                'create_date'             => '2014-06-19',
                'update_date'             => '2014-06-19',
                'permissions'             => array(3, 102),
                'file'                    => 'aW5kZXggbWUK',
                'approval_table_comments' => array(),
                'property_1'              => 'val01',
                'property_2'              => 'val02',
               ),
        );
        $this->client->expectOnce('index', $expected);

        $this->actions->indexNewDocument($this->item, $this->version);
    }

    public function itCallUpdateOnClientWithTitleIfNew() {
        $metadata03 = stub('Docman_Metadata')->getId()->returns(6);

        $second_search_type = array(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        stub($this->metadata_factory)->getRealMetadataList(false, $second_search_type)->returns(
            array($metadata03)
        );

        stub($this->metadata_factory)->getMetadataValue($this->item, $metadata03)->returns(1403160959);

        stub($this->client)->getMapping()->returns(array());

        $update_data = array(
            'title'       => $this->item->getTitle(),
            'description' => $this->item->getDescription(),
            'property_1'  => 'val01',
            'property_2'  => 'val02',
            'property_6'  => '2014-06-19',
        );

        $expected = array(200, 101, $update_data);
        $this->client->expectOnce('update', $expected);

        $this->actions->updateDocument($this->item);
    }

    public function itCanDeleteADocumentFromItsId() {
        $this->client->expectOnce('delete', array(200, 101));

        $this->actions->delete($this->item);
    }

    public function itReturnsTrueIfMappingIsNotEmptyForProject() {
        stub($this->client)->getMapping(200)->returns(array(
            'mappings' => array(
                '200' => array(
                    'properties' => array()
                )
            )
        ));

        $this->assertTrue($this->actions->checkProjectMappingExists(200));
    }

    public function itInitializesProjectMapping() {
        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'title' => array(
                        'type' => 'string'
                    ),
                    'description' => array(
                        'type' => 'string'
                    ),
                    'owner' => array(
                        'type' => 'string'
                    ),
                    'create_date' => array(
                        'type' => 'date'
                    ),
                    'update_date' => array(
                        'type' => 'date'
                    ),
                    'obsolescence_date' => array(
                        'type' => 'date'
                    ),
                    'file' => array(
                        'type'   => 'attachment',
                        'fields' => array(
                            'title' => array(
                                'store' => 'yes'
                            ),
                            'file' => array(
                                'term_vector' => 'with_positions_offsets',
                                'store'       => 'yes'
                            )
                        )
                    ),
                    'permissions' => array(
                        'type'  => 'string',
                        'index' => 'not_analyzed'
                    ),
                    'approval_table_comments' => array(
                        'properties' => array(
                            'user_id' => array(
                                'type' => 'integer',
                            ),
                            'date_added' => array(
                                'type' => 'date',
                            ),
                            'comment' => array(
                                'type' => 'string',
                            ),
                        )
                    )
                )
            )
        );

        expect($this->client)->setMapping(200, $expected_data)->once();

        $this->actions->initializeProjetMapping(200);
    }

    public function itUpdatesMappingIfANewCustomDateMetadataIsFound() {
        $metadata03 = stub('Docman_Metadata')->getId()->returns(6);

        $second_search_type = array(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        stub($this->metadata_factory)->getRealMetadataList(false, $second_search_type)->returns(
            array($metadata03)
        );

        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'property_6' => array(
                        'type' => 'date'
                    )
                )
            )
        );

        stub($this->client)->getMapping()->returns(array(
            'docman' => array(
                'mappings' => array(
                    '200' => array(
                        'properties' => array(
                            'description' => array(
                                'type' => 'string'
                            )
                        )
                    )
                )
            )
        ));

        expect($this->client)->setMapping(200, $expected_data)->once();

        $this->actions->indexNewDocument($this->item, $this->version);
    }
}
