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

Nadlani.define('crm:views/opportunity/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({

        setupMassActionItems: function () {
            Dep.prototype.setupMassActionItems.call(this);

            if (this.getConfig().get('currencyList').length > 1) {
                if (
                    this.getAcl().checkScope(this.scope, 'edit')
                    &&
                    !~this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit').indexOf('amount')
                ) {
                    this.addMassAction('convertCurrency', true);
                }
            }
        },

        massActionConvertCurrency: function () {
            var ids = false;
            var allResultIsChecked = this.allResultIsChecked;
            if (!allResultIsChecked) {
                ids = this.checkedList;
            }

            this.createView('modalConvertCurrency', 'views/modals/mass-convert-currency', {
                entityType: this.scope,
                field: 'amount',
                ids: ids,
                where: this.collection.getWhere(),
                selectData: this.collection.data,
                byWhere: this.allResultIsChecked
            }, function (view) {
                view.render();
                this.listenToOnce(view, 'after:update', function (count) {
                    this.listenToOnce(this.collection, 'sync', function () {
                        if (count) {
                            var msg = 'massUpdateResult';
                            if (count == 1) {
                                msg = 'massUpdateResultSingle'
                            }
                            Nadlani.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                        } else {
                            Nadlani.Ui.warning(this.translate('noRecordsUpdated', 'messages'));
                        }
                        if (allResultIsChecked) {
                            this.selectAllResult();
                        } else {
                            ids.forEach(function (id) {
                                this.checkRecord(id);
                            }, this);
                        }
                    }, this);
                    this.collection.fetch();
                }, this);
            });
        }

    });
});
