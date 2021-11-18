<?php

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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
/*
 * Non obligatoire mais peut être utilisé si vous voulez charger en même temps que votre
 * plugin des librairies externes (ne pas oublier d'adapter plugin_info/info.xml).
 *
 *
 */
 if (!jeedom::apiAccess(init('apikey'), 'modbus')) {
   echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
	log::add('bacnet','debug','Accès interdit core/php/jeemodbus.php');
   die();
 }

 if (isset($_GET['test'])) {
	log::add('bacnet','debug','Test core/php/jeeModbus.php:');
 	echo 'OK';
 	die();
 }


/*$result = json_decode(file_get_contents("php://input"), true);*/