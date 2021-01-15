/**
 * Classe que define a lista de "CallSummary"
 *
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2021 MagnusBilling. All rights reserved.
 * ###################################
 *
 * This software is released under the terms of the GNU Lesser General Public License v3
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 * Please submit bug reports, patches, etc to https://github.com/magnussolution/magnusbilling7/issues
 * =======================================
 * Magnusbilling.org <info@magnussolution.com>
 * 17/08/2012
 */
Ext.define('MBilling.view.callSummaryMonthDid.List', {
    extend: 'Ext.ux.grid.Panel',
    alias: 'widget.callsummarymonthdidlist',
    store: 'CallSummaryMonthDid',
    initComponent: function() {
        var me = this;
        me.fieldSearch = App.user.isAdmin ? 'idDid.did' : '';
        me.buttonImportCsv = false
        me.buttonUpdateLot = false;
        me.allowCreate = false;
        me.allowUpdate = false;
        me.allowDelete = false;
        me.columns = [{
            header: t('Month'),
            renderer: Ext.util.Format.dateRenderer('Y-m'),
            dataIndex: 'month',
            filter: {
                type: 'date',
                field: 'month',
                dateFormat: 'Ym'
            },
            flex: 3
        }, {
            header: t('Duration') + '/Min',
            dataIndex: 'sessiontime',
            flex: 2
        }, {
            header: t('ALOC all calls'),
            dataIndex: 'aloc_all_calls',
            renderer: Helper.Util.formatsecondsToTime,
            flex: 3
        }, {
            header: t('Answered'),
            dataIndex: 'nbcall',
            flex: 3
        }, {
            header: t('DID'),
            dataIndex: 'id_did',
            filter: {
                type: 'string',
                field: 'idDid.did'
            },
            flex: 4,
            hidden: App.user.isClient,
            hideable: !App.user.isClient
        }];
        me.callParent(arguments);
    }
});