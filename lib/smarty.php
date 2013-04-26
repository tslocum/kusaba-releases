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
require_once KU_ROOTDIR . 'lib/smarty/Smarty.class.php';
$smarty = new Smarty();

$smarty->template_dir = KU_TEMPLATEDIR;
if (KU_CACHEDTEMPLATEDIR != '') {
	$smarty->compile_dir = KU_CACHEDTEMPLATEDIR;
	$smarty->cache_dir = KU_CACHEDTEMPLATEDIR;
}
$smarty->config_dir = KU_ROOTDIR . 'smarty/configs';

$smarty->assign('ku_name', KU_NAME);
$smarty->assign('ku_webpath', KU_WEBPATH);
$smarty->assign('ku_boardspath', KU_BOARDSPATH);
$smarty->assign('ku_cgipath', KU_CGIPATH);

?>