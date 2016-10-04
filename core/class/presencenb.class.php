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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class presencenb extends eqLogic {
    /*     * *************************Attributs****************************** */

      public static $_widgetPossibility = array('custom' => true);


    /*     * ***********************Methode static*************************** */

      public static function cron() {
          foreach (eqLogic::byType('presencenb') as $eqLogic) {
              $autorefresh = $eqLogic->getConfiguration('autorefresh');
              if ($eqLogic->getIsEnable() == 1 && $autorefresh != '') {
                  try {
                      $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                      if ($c->isDue()) {
                          try {
                              $eqLogic->refresh();
                          } catch (Exception $exc) {
                              log::add('presencenb', 'error', __('Erreur pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $exc->getMessage());
                          }
                      }
                  } catch (Exception $exc) {
                      log::add('presencenb', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
                  }
              }
          }
      }

      public static function dependancy_info() {
          $return = array();
          $return['log'] = 'presencenb_update';
          $return['progress_file'] = '/tmp/dependancy_presencenb_in_progress';
          if (exec('which python3 | wc -l') != 0) {
              $return['state'] = 'ok';
          } else {
              $return['state'] = 'nok';
          }
          return $return;
        }

      public static function dependancy_install() {
          log::remove('presencenb_update');
          $cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install.sh';
          $cmd .= ' >> ' . log::getPathToLog('presencenb_update') . ' 2>&1 &';
          exec($cmd);
      }

        public static function start() {
                self::cron15();
        }

    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        $myConfig = fopen(__DIR__ .'/../../ressources/CONFIG'.$this->getId(), 'w');
        fclose($myfile);
    }

    public function preSave() {
    }

    public function postSave() {
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
            $refresh = new presencenbCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
        }
        $refresh->setEqLogic_id($this->getId());
        $refresh->setLogicalId('refresh');
        $refresh->setType('action');
        $refresh->setSubType('other');
        $refresh->save();
    }

    public function preRemove() {
        unlink(__DIR__ .'/../../ressources/CONFIG'.$this->getId());
    }

    public function postRemove() {
    }

    public function updateConfig() {		// MAJ du fichier CONFIG après la sauvegarde des commandes
        $myConfig = fopen(__DIR__ .'/../../ressources/CONFIG'.$this->getId(), 'w');
        foreach ($this->getCmd('action') as $cmd) {
            if ( $cmd->getLogicalId() != 'refresh') {
                $line = $cmd->getConfiguration('mac')." ". $cmd->getConfiguration('keepalive')."\n";
                fwrite($myConfig, $line);
            }
        }
        fclose($myfile);
    }
      
    public function refresh() {
        $ip = config::byKey('ipBox', 'presencenb');
        $json = shell_exec(__DIR__ .'/../../ressources/apineufbox.py update '.$ip);	// Execute le script python et récupère le json
        $parsed_json = json_decode($json);
        foreach ($this->getCmd('info') as $cmd) {
            $mac = $cmd->getConfiguration('mac');
            $val = $parsed_json->{$mac}->{$cmd->getConfiguration('info')};
            $cmd->setCollectDate('');
            $cmd->event($val);
        }
        $this->refreshWidget();
    }

      public function toHtml($_version = 'dashboard') {
          $replace = $this->preToHtml($_version);
          if (!is_array($replace)) {
              return $replace;
          }
          $_version = jeedom::versionAlias($_version);
          $id = $this->getId();
          if ($_version != 'mobile') {			// Version Dashboard
              $body = null;
              $refresh = $this->getCmd(null, 'refresh');
              $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
              $configuration = $this->getConfiguration();
              $tdTheadIP = null;
              if ($configuration['displayIp']) {
                  $tdTheadIp = '<th>Adresse IP</th>';
              }
              $tdTheadMac = null;
              if ($configuration['displayMac']) {
                  $tdTheadMac = '<th>Adresse MAC</th>';
              }
              $tdTheadHostname = null;
              if ($configuration['displayHostname']) {
                  $tdTheadHostname = '<th>Hostname</th>';
              }
              $thead = '<th>Nom</th><th>Status</th><th>Online</th><th>Offline</th>'.$tdTheadIp.$tdTheadMac.$tdTheadHostname;
              foreach ($this->getCmd('action') as $cmd) {		// Creation du body
                  if ($cmd->getLogicalId() == 'refresh') {
                      continue;
                  }
                  $cmd_id = $cmd->getID();
                  $name = $cmd->getName();
                  $status = $this->getCmd(null, $cmd->getId().'_status')->execCmd();
                  if ($status == 'online') {
                      $statusIcon = 'fa-check';
                  } else {
                      $statusIcon = 'fa-ban';
                  }
                  $active = $this->getCmd(null, $cmd->getId().'_active')->execCmd();
                  if ($active) {
                      $activeIcon = 'fa-eye';
                      $activeStyleOff = 'style="color: rgb(50,50,50);"';
                      $activeStyleOn = '';
                      if ($configuration['displayTimer']) {
                          $timer = $this->getCmd(null, $cmd->getId().'_timer')->execCmd();
                          $timer = '-'.gmdate("i:s", $timer);
                      }
                  } else {
                      $activeIcon = 'fa-eye-slash';
                      $activeStyleOff = '';
                      $activeStyleOn = 'style="color: rgb(50,50,50);"';
                      $timer = '';
                  }
                  $iface = $this->getCmd(null, $cmd->getId().'_iface')->execCmd();
                  $ifaceIcon = '';
                  if (preg_match('/^wlan/', $iface)) {
                      $ifaceIcon = 'fa-wifi';
                  } elseif (preg_match('/^lan/', $iface)) {
                      $ifaceIcon = 'fa-plug';
                  }
                  $online = $this->getCmd(null, $cmd->getId().'_online')->execCmd();
                  if ($online > 3599999) {
                      $online = 3599999;
                  }
                  if (empty($online)) {
                  } elseif (mb_strlen(floor($online / 3600)) == 1) {
                      $online = '0'.floor($online / 3600).':'.gmdate("i:s", $online);
                  } else {
                      $online = floor($online / 3600).':'.gmdate("i:s", $online);
                  }
                  $offline = $this->getCmd(null, $cmd->getId().'_offline')->execCmd();
                  if ($offline > 3599999) {
                      $offline = 3599999;
                  }
                  if (empty($offline)) {
                  } elseif (mb_strlen(floor($offline / 3600)) == 1) {
                      $offline = '0'.floor($offline / 3600).':'.gmdate("i:s", $offline);
                  } else {
                      $offline = floor($offline / 3600).':'.gmdate("i:s", $offline);
                  }
                  $tdBodyIp = '';
                  if ($configuration['displayIp']) {
                      $ip = $this->getCmd(null, $cmd->getId().'_ip')->execCmd();
                      $tdBodyIp = '<td><span>'.$ip.'</span></td>';
                  }
                  $tdBodyMac = '';
                  if ($configuration['displayMac']) {
                      $mac = $cmd->getConfiguration('mac');
                      $tdBodyMac = '<td><span>'.$mac.'</span></td>';
                  }
                  $tdBodyHostname = '';
                  if ($configuration['displayHostname']) {
                      $hostname = $this->getCmd(null, $cmd->getId().'_name')->execCmd();
                      $tdBodyHostname = '<td><span>'.$hostname.'</span></td>';
                  }
                  $body .= '<tr id="'.$cmd_id.'">
                          <td><span class="fa '.$ifaceIcon.' fa-1" style="margin-right: 5px;"></span><span>'.$name.'</span></td>
                          <td style="text-align: center;"><span class="fa '.$statusIcon.' fa-1" style="margin: 5px;"></span><span class="fa '.$activeIcon.' fa-1" style="margin: 5px;"></span></td>
                          <td><span '.$activeStyleOn.'>'.$online.'</span><span style="font-size: 70%; margin: 2px; vertical-align: bottom;">'.$timer.'</span></td>
                          <td><span '.$activeStyleOff.'>'.$offline.'</span></td>
                          '.$tdBodyIp.'
                          '.$tdBodyMac.'
                          '.$tdBodyHostname.'
                          </tr>';
              }
              $replace['#thead#'] = $thead;
              $replace['#body#'] = $body;
          } else { 		// Version mobile
              $refresh = $this->getCmd(null, 'refresh');
              $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
              $thead = '<th>Nom</th><th>Status</th><th>Online</th><th>Offline</th>';
              foreach ($this->getCmd('action') as $cmd) {		// Creation du body
                  if ($cmd->getLogicalId() == 'refresh') {
                      continue;
                  }
                  $cmd_id = $cmd->getID();
                  $name = $cmd->getName();
                  $status = $this->getCmd(null, $cmd->getId().'_status')->execCmd();
                  $active = $this->getCmd(null, $cmd->getId().'_active')->execCmd();
                  $style = null;
                  if ($status == 'online') {
                      $statusIcon = 'fa-check';
                      $counter = $this->getCmd(null, $cmd->getId().'_online')->execCmd();
                  } elseif ($active) {
                      $statusIcon = 'fa-eye';
                      $counter = $this->getCmd(null, $cmd->getId().'_online')->execCmd();
                  } else {
                      $statusIcon = 'fa-eye-slash';
                      $counter = $this->getCmd(null, $cmd->getId().'_offline')->execCmd();
                      $style = 'color: rgb(50,50,50)';
                  }
                  if ($counter > 3599999) {
                      $counter = 3599999;
                  }
                  if (empty($counter)) {
                  } elseif (mb_strlen(floor($counter / 3600)) == 1) {
                      $counter = '0'.floor($counter / 3600).':'.gmdate("i:s", $counter);
                  } else {
                      $counter = floor($counter / 3600).':'.gmdate("i:s", $counter);
                  }
                  $content .= '<span id="'.$cmd_id.'" class="fa '.$statusIcon.' fa-1" style="center; '.$style.';"></span><span style="'.$style.';"> '.$name.' </span><br/>
                               <span style="padding-left: 20px; '.$style.';">'.$counter.'</span><br/>';
              }
              $replace['#content#'] = $content;
          }
          return template_replace($replace, getTemplate('core', $_version, 'presencenb','presencenb'));
      }

    /*     * **********************Getteur Setteur*************************** */
}

class presencenbCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function createSubCmd() {
        foreach (array('name', 'ip', 'iface', 'status', 'active', 'online', 'offline', 'timer') as $key) {
            $presencenbCmd = $this->getEqLogic()->getCmd(null, $this->getId().'_'.$key);
            if (!is_object($presencenbCmd)) {
                $presencenbCmd = new presencenbCmd();
            }
            $presencenbCmd->setName($this->getName().'_'.$key);
            $presencenbCmd->setLogicalId($this->getId().'_'.$key);
            $presencenbCmd->setEqLogic_id($this->getEqLogic_id());
            $presencenbCmd->setType('info');
            if ($key == 'active') {
                $presencenbCmd->setSubType('binary');
                $presencenbCmd->setIsHistorized(1);
            } else {
                $presencenbCmd->setSubType('other');
            }
            $presencenbCmd->setConfiguration('mac', $this->getConfiguration('mac'));
            $presencenbCmd->setConfiguration('info', $key);
            $presencenbCmd->setConfiguration('name', $this->getName());
            $presencenbCmd->save();
        }
    }


    public function preSave() {
        if ($this->getLogicalId() == 'refresh') {
            return;
        }
        if ($this->getType() == 'action') {
            if (!preg_match("#^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$#", $this->getConfiguration('mac'))) {
                throw new Exception('Attention: "' . $this->getConfiguration('mac') . '" n\'est pas une adresse MAC valide');
            }
            if (!is_numeric($this->getConfiguration('keepalive'))) {
                throw new Exception('Attention: keepalive "' . $this->getConfiguration('keepalive') .'" n\'est pas un nombre');
            }
            $this->setConfiguration('mac', strtolower($this->getConfiguration('mac')));
        }
    }


    public function postSave() {			// MAJ du fichier CONFIG
        if ($this->getLogicalId() == 'refresh') {
            return;
        }
        if ($this->getType() == 'action') {
            $this->getEqLogic()->updateConfig();
            $this->createSubCmd();
        }
        if ($this->getType() == 'info') {
            $oldName = $this->getName();
            $newName = $this->getConfiguration('name').'_'.$this->getConfiguration('info');
            if ($oldName != $newName) {
                $this->setName($newName);
                $this->save();
            }
        }
    }

      public function postRemove() {	//Si cmd action -> suppresssion de ses commandes info
          if ($this->getType() == 'action') {
              foreach ($this->getEqLogic()->getCmd('info') as $cmd) {
                  if ($this->getConfiguration('mac') == $cmd->getConfiguration('mac')) {
                      $cmd->remove();
                  }
              }
          }
          $this->getEqLogic()->updateConfig();
      }

      public function dontRemoveCmd() {		// Ne pas supprimer refresh et les cmd info
          if ($this->getLogicalId() == 'refresh' || $this->getType() == 'info') {
              return true;
          }
          return false;
      }

    public function execute($_options = array()) {
        if ($this->getLogicalId() == 'refresh') {
            $this->getEqLogic()->refresh();
            return;
        }
        //log::add('presencenb','debug','execute');
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>
