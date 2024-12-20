<?php
/* Copyright (C) 2021 Arthur Dupond <contact@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    advancedproductsearch/lib/advancedproductsearch.lib.php
 * \ingroup advancedproductsearch
 * \brief   Library files with common functions for AdvancedProductSearch
 */

/**
 * @return array
 */
function advancedproductsearchAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("advancedproductsearch@advancedproductsearch");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/advancedproductsearch/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

//	$head[$h][0] = dol_buildpath("/advancedproductsearch/admin/about.php", 1);
//	$head[$h][1] = $langs->trans("About");
//	$head[$h][2] = 'about';
//	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@advancedproductsearch:/advancedproductsearch/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@advancedproductsearch:/advancedproductsearch/mypage.php?id=__ID__'
	//); // to remove a tab
	$object = new stdClass;
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'advancedproductsearch');

	return $head;
}


