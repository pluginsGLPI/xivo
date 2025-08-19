/**
 * -------------------------------------------------------------------------
 * xivo plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of xivo.
 *
 * xivo is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * xivo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with xivo. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2017-2024 by xivo plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/xivo
 * -------------------------------------------------------------------------
 */

$(function() {
   // do like a jquery toggle but based on a parameter
   $.fn.toggleFromValue = function(val) {
      var that = this;
      if (val === 1
          || val === "1"
          || val === true) {
         that.show();
         $(that).find('[_required]').prop('required', true);
      } else {
         that.hide();
         $(that).find('[required]').prop('required', false).attr('_required', 'true');
      }
   }

   // remove required from hidden fields
   $(document).on('click','.xivo_config form input[type=submit]',function() {
      xivoCheckConfig();
   });
});

var xivoCheckConfig = function() {
   $(".xivo_config .xivo_config_block").each(function() {
      var that = $(this);
      if (that.css("display") == "none") {
         $(that).find('[required]').prop('required', false);
      }
   });
};
