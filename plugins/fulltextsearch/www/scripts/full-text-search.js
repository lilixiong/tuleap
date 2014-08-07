/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

var tuleap    = tuleap || {};
tuleap.search = tuleap.search || {};

!(function ($) {

    tuleap.search.fulltext = {

        handleFulltextFacets : function (type_of_search) {
            var $facets = $('a.search-type[data-search-type="'+type_of_search+'"]').siblings('ul').find('.facets');

            $facets.on('change', function() {
                var keywords = $('#words').val();
                var facets   = $('a.search-type[data-search-type="'+type_of_search+'"]').siblings('ul').find('.facets');

                (function beforeSend() {
                    $('#search-results').html('').addClass('loading')
                })();

                var url = '/search/?type_of_search='+type_of_search+'&words='+keywords+'&'+facets.serialize();
                if (type_of_search === 'wiki') {
                    url += '&group_id=' + $('.search-bar input[name="group_id"]').val();
                }

                $.getJSON(url)
                    .done(function(json) {
                        $('#search-results').html(json.html);
                        tuleap.search.moveFacetsToSearchPane(type_of_search);
                    }).fail(function(error) {
                        codendi.feedback.clear();
                        codendi.feedback.log('error', codendi.locales.search.error + ' : ' + error.responseText);
                    }).always(function() {
                        $('#search-results').removeClass('loading');
                    });
            });
        }
    };
})(window.jQuery);