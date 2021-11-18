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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class modbus extends eqLogic {
    /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
      public static function cron5() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
      public static function cron10() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
      public static function cron15() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
      public static function cron30() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {
      }
     */
     public static function deamon_info() {
     		$return = array();
     		$return['log'] = 'modbus';
     		$return['state'] = 'nok';
     		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamond.pid';
     		if (file_exists($pid_file)) {
     		    $pid = trim(file_get_contents($pid_file));
            if (is_numeric($pid) && posix_getsid($pid)){
     				     $return['state'] = 'ok';
     			  } else {
     				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
     			}
     		}
     		$return['launchable'] = 'ok';
     		return $return;
     	}

      public static function dependancy_info() {
                 $return = array();
                 $return['log'] = log::getPathToLog(__CLASS__ . '_update');
                 $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
                 if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
                     $return['state'] = 'in_progress';
                 } else {
                     if (exec(system::getCmdSudo() . system::get('cmd_check') . '-Ec "python3\-pip|python3\-dev|python3\-pyudev|python3\-serial|python3\-requests|python3\-pymodbus"') < 5) {
                         $return['state'] = 'nok';
                     } elseif (exec(system::getCmdSudo() . 'pip3 list | grep -Ewc "pymodbus"') < 1) {
                         $return['state'] = 'nok';
                     } else {
                         $return['state'] = 'ok';
                     }
                 }
                 return $return;
      }


      public static function dependancy_install() {
          log::remove(__CLASS__ . '_update');
          return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency', 'log' => log::getPathToLog(__CLASS__ . '_update'));
       }


       public static function deamon_start() {
            self::deamon_stop();
            $deamon_info = self::deamon_info();
            if ($deamon_info['launchable'] != 'ok') {
                throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
            }

            $path = realpath(dirname(__FILE__) . '/../../resources/demond');
            $cmd = '/usr/bin/python3 ' . $path . '/demond.py';
            $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
            $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '50404');
            $cmd .= ' --sockethost 127.0.0.1';
            $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/modbus/core/php/jeeModbus.php';
            $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
            $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamond.pid';
            log::add(__CLASS__, 'info', 'Lancement démon '.$cmd);
            $result = exec($cmd . ' >> ' . log::getPathToLog('modbus') . ' 2>&1 &');
            $i = 0;
            while ($i < 10) {
                $deamon_info = self::deamon_info();
                if ($deamon_info['state'] == 'ok') {
                    break;
                }
                sleep(1);
                $i++;
            }
            if ($i >= 30) {
                log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
                return false;
            }
            message::removeAll(__CLASS__, 'unableStartDeamon');
            return true;
       }


  public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamond.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('demond.py');
        sleep(1);
    }



    public static function socketConnection($value){
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_set_timeout($socket,180);
        socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'modbus', 5030));
        socket_write($socket, $value, strlen($value));
        socket_close($socket);
    }


    public function allowDevice() {
		$value = array('apikey' => jeedom::getApiKey('modbus'), 'cmd' => 'add');
		if ($this->getConfiguration('typeOf') == 'rtu') {
			   $value = json_encode($value);
			   if (config::byKey('socketport', 'modbusrtu') != '') {
            self::socketConnection($value);
			   }
		}elseif($this->getConfiguration('typeOf') == 'tcp'){



    }else{
        log::add(__CLASS__, 'info', 'Vous n\'avez pas choisi le mode de communication Modbus');

    }
	}


    /*     * *********************Méthodes d'instance************************* */

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {

    }

 // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {

    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {

    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {

    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {

    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {

    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {

    }

 // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {

    }

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class modbusCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande
     public function execute($_options = array()) {

     }

    /*     * **********************Getteur Setteur*************************** */
}
