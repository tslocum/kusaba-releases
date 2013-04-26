<?php
function clearBlotterCache() {
	if (KU_APC) {
		apc_delete('blotter|all');
		apc_delete('blotter|last4');
	}
}

/**
 * Clear cache for the supplied post ID of the supplied board
 * 
 * @param integer $id Post ID
 * @param string $board Board name
 */    
function clearPostCache($id, $board) {
	if (KU_APC) {
		apc_delete('post|' . $board . '|' . $id);
	}
}
?>