
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

 $('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
    });
});

 $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (init(_cmd.logicalId) == 'refresh') {
       return;
    }
    if (init(_cmd.type) != 'info') {
      var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
      tr += '<td>';
      tr += '<span class="cmdAttr" data-l1key="id" ></span>';
      tr += '</td>';
      tr += '<td>';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="action" style="display : none;">';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="subtype" value="binary" style="display : none;">';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" >';
      tr += '</td>';
      tr += '<td>';
      tr += '<span class="mac">';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="mac" placeholder="{{01:23:45:67:89:ab}}" >';
      tr += '</span>';
      tr += '</td>';

      tr += '<td>';
      tr += '<span class="keepalive">';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="keepalive" placeholder="{{900}}" >';
      tr += '</span>';
      tr += '</td>';

      tr += '<td>';
      tr += '<span><input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="isVisible" data-size="mini" data-label-text="{{Afficher}}" checked/></span> ';
      tr += '</td>';
      tr += '<td>';
      tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
      tr += '</tr>';
      $('#table_cmd tbody').append(tr);
      $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    } else {
      var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
      tr += '<td>';
      tr += '<span class="cmdAttr" data-l1key="id" ></span>';
      tr += '</td>';
      tr += '<td>';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="info" style="display : none;">';
      tr += '<span class="cmdAttr form-control input-sm" data-l1key="name" ></span>';
      tr += '<span class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
      tr += '</td>';

      tr += '<td>';
      tr += '<span><input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="isHistorized"  data-size="mini" data-label-text="{{Historiser}}" /></span> ';
      tr += '</td>';
      tr += '<td>';
      if (is_numeric(_cmd.id)) {
          tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
          tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
      }
      tr += '</td>';
      tr += '</tr>';
      $('#table_info tbody').append(tr);
      $('#table_info tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
}
