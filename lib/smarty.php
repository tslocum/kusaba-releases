<?php
/*
 * This file is part of Trevorchan.
 *
 * Trevorchan is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Trevorchan is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along with
 * Trevorchan; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * +------------------------------------------------------------------------------+
 * Smarty engine initialization and configuration
 * +------------------------------------------------------------------------------+
 */
require(TC_ROOTDIR . 'lib/smarty/Smarty.class.php');
$smarty = new Smarty();

$smarty->template_dir = TC_TEMPLATEDIR;
if (TC_CACHEDTEMPLATEDIR != '') {
	$smarty->compile_dir = TC_CACHEDTEMPLATEDIR;
	$smarty->cache_dir = TC_CACHEDTEMPLATEDIR;
}
$smarty->config_dir = TC_ROOTDIR . 'smarty/configs';

$smarty->assign('tc_name', TC_NAME);
$smarty->assign('tc_webpath', TC_WEBPATH);
$smarty->assign('tc_boardspath', TC_BOARDSPATH);
$smarty->assign('tc_cgipath', TC_CGIPATH);

?>