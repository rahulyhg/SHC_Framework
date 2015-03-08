<?php

namespace SHC\Switchable;

//Imports
use RWF\Core\RWF;
use RWF\User\Visitor;
use SHC\Command\CommandSheduler;
use SHC\Timer\SwitchPoint;
use SHC\Room\Room;
use RWF\User\User;
use RWF\User\UserGroup;

/**
 * Basisklasse eines Schaltbaren Elements
 * 
 * @author     Oliver Kleditzsch
 * @copyright  Copyright (c) 2014, Oliver Kleditzsch
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @since      2.0.0-0
 * @version    2.0.0-0
 */
abstract class AbstractSwitchable implements Switchable {

    /**
     * Schaltpunkte
     * 
     * @var Array 
     */
    protected $switchPoints = array();

    /**
     * Status
     * 
     * @var Integer 
     */
    protected $state = self::STATE_OFF;
    
    /**
     * gibt an ob der Status veraendert wurde
     * 
     * @var Boolean 
     */
    protected $stateModified = false;

    /**
     * ID des Elements
     * 
     * @var Integer 
     */
    protected $id = 0;
    
    /**
     * Icon des Elements
     * 
     * @var String 
     */
    protected $icon = '';

    /**
     * Name des Elements
     * 
     * @var String 
     */
    protected $name = '';

    /**
     * Raeume
     *
     * @var Array
     */
    protected $rooms = array();
    
    /**
     * Sortierungs ID
     * 
     * @var Array
     */
    protected $order = array();

    /**
     * aktiviert/deaktiviert
     * 
     * @var Boolean 
     */
    protected $enabled = true;
    
    /**
     * sichtbarkeit des Schaltelementes
     * 
     * @var Integer 
     */
    protected $visibility = 1;

    /**
     * Berechtigte Benutzergruppen
     * 
     * @var Array 
     */
    protected $allowedUserGroups = array();

    /**
     * fuegt einen Schaltpunkt hinzu
     * 
     * @param  \SHC\Timer\SwitchPoint $switchPoint
     * @return \SHC\Switchable\Switchable
     */
    public function addSwitchPoint(SwitchPoint $switchPoint) {

        $this->switchPoints[] = $switchPoint;
        return $this;
    }

    /**
     * loescht einen Schaltpunkt
     * 
     * @param  \SHC\Timer\SwitchPoint $switchPoint
     * @return \SHC\Switchable\Switchable
     */
    public function removeSwitchPoint(SwitchPoint $switchPoint) {

        $this->switchPoints = array_diff($this->switchPoints, array($switchPoint));
        return $this;
    }

    /**
     * loescht alle Schaltpunkte
     * 
     * @return \SHC\Switchable\Switchable
     */
    public function removeAllSwitchPoints() {

        $this->switchPoints = array();
        return $this;
    }

    /**
     * gibt eine Liste ,it allen Schaltpunkten zurueck
     *
     * @return Array
     */
    public function listSwitchPoints() {

        return $this->switchPoints;
    }

    /**
     * schaltet das Objekt um (in den jeweils gegenteiligen zustand)
     * 
     * @return Boolean
     */
    public function toggle() {

        if ($this->getState() === self::STATE_ON) {

            return $this->switchOff();
        }
        return $this->switchOn();
    }

    /**
     * setzt den Status des Objekts
     * 
     * @param  Integer $state    Status
     * @param  Boolean $modified als veaendert Markieren
     * @return \SHC\Switchable\Switchable
     */
    public function setState($state, $modified = true) {
        
        $this->state = $state;
        if($modified == true) {
            
            $this->stateModified = true;
        }
        return $this;
    }
    
    /**
     * gibt den aktuellen geschaltenen Zustand zurueck
     * 
     * @return Integer
     */
    public function getState() {

        return $this->state;
    }
    
    /**
     * gibt an ob der Status veraendert wurde
     * 
     * @return Boolean
     */
    public function isStateModified() {
        
        return $this->stateModified;
    }

    /**
     * fuehrt alle anstehenden Schaltbefehle aus und gibt true zurueck wenn eine Aktion ausgefuehrt wurde
     * 
     * @return Boolean
     */
    public function execute() {

        //Schaltpunkte durchlaufen und pruefen ob Ausfuhrungsbereit
        foreach ($this->switchPoints as $switchPoint) {

            /* @var $switchPoint \SHC\Timer\SwitchPoint */
            if ($switchPoint->isSatisfies()) {

                $command = $switchPoint->getCommand();
                switch ($command) {
                    case SwitchPoint::SWITCH_ON:

                        //Einachalten
                        $this->switchOn();
                        try {
                            CommandSheduler::getInstance()->sendCommands();
                        } catch(\Exception $e) {

                            RWF::getResponse()->writeLnColored('Fehler beim Senden : '. $e->getMessage() .' - '. $e->getCode(), 'red');
                        }
                        break;
                    case SwitchPoint::SWITCH_OFF:

                        //Ausschalten
                        $this->switchOff();
                        try {
                            CommandSheduler::getInstance()->sendCommands();
                        } catch(\Exception $e) {
                            RWF::getResponse()->writeLnColored('Fehler beim Senden : '. $e->getMessage() .' - '. $e->getCode(), 'red');
                        }
                        break;
                    case SwitchPoint::SWITCH_TOGGLE:
                    default :

                        //Umschalten
                        $this->toggle();
                        try {
                            CommandSheduler::getInstance()->sendCommands();
                        } catch(\Exception $e) {
                            RWF::getResponse()->writeLnColored('Fehler beim Senden : '. $e->getMessage() .' - '. $e->getCode(), 'red');
                        }
                        break;
                }
            }
        }
        return false;
    }

    /**
     * setzt die ID des Elements
     * 
     * @param Integer $id
     * @return \SHC\Switchable\Switchable
     */
    public function setId($id) {
        
        $this->id = $id;
        return $this;
    }
    
    /**
     * gibt die ID des Elements zurueck
     * 
     * @return Integer
     */
    public function getId() {
        
        return $this->id;
    }
    
    /**
     * setzt das Icon welches Angezeigt werden soll
     * 
     * @param  String $path Dateiname
     * @return \SHC\Switchable\Switchable
     */
    public function setIcon($path) {

        $this->icon = $path;
        return $this;
    }

    /**
     * gibt den Dateinamen des Icons zurueck
     * 
     * @return String
     */
    public function getIcon() {

        return $this->icon;
    }

    /**
     * setzt den Namen des Elements
     * 
     * @param  String $name Name
     * @return \SHC\Switchable\Switchable
     */
    public function setName($name) {

        $this->name = $name;
        return $this;
    }

    /**
     * gibt den Namen des Elements zurueck
     * 
     * @return String
     */
    public function getName() {

        return $this->name;
    }

    /**
     * fuegt einen Raum hinzu
     *
     * @param  Integer $roomId Raum ID
     * @return \SHC\Switchable\Element
     */
    public function addRoom($roomId) {

        $this->rooms[] = $roomId;
        return $this;
    }

    /**
     * setzt eine Liste mit Raeumen
     *
     * @param  Array $roomId Raum IDs
     * @return \SHC\Switchable\Element
     */
    public function setRooms(array $rooms) {

        $this->rooms = $rooms;
        return $this;
    }

    /**
     * entfernt einen Raum
     *
     * @param  Integer $roomId Raum ID
     * @return \SHC\Switchable\Element
     */
    public function removeRoom($roomId) {

        $this->rooms = array_diff($this->rooms, array($roomId));
        return $this;
    }

    /**
     * prueft on das Element dem Raum mit der uebergebenen ID zugeordnet ist
     *
     * @param  Integer $roomId Raum ID
     * @return Boolean
     */
    public function isInRoom($roomId) {

        return in_array($roomId, $this->rooms);
    }

    /**
     * gibt eine Liste mit allen Raeumen zurueck
     *
     * @return Array
     */
    public function getRooms() {

        return $this->rooms;
    }

    /**
     * setzt die Sortierung
     *
     * @param  Array $order Sortierung
     * @return \SHC\Switchable\Switchable
     */
    public function setOrder(array $order) {

        $this->order = $order;
        return $this;
    }

    /**
     * setzt die Sortierungs ID
     *
     * @param  Integer $roomId  Raum ID
     * @param  Integer $orderId Sortierungs ID
     * @return \SHC\Switchable\Switchable
     */
    public function setOrderId($roomId, $orderId) {

        $this->order[$roomId] = $orderId;
        return $this;
    }

    /**
     * gibt die Sortierungs ID zurueck
     *
     * @param  Integer $roomId  Raum ID
     * @return Integer
     */
    public function getOrderId($roomId) {

        if(isset($this->order[$roomId])) {

            return $this->order[$roomId];
        }
        return 0;
    }

    /**
     * Aktiviert/Deaktiviert das Element
     * 
     * @param  Boolean $enabled Aktiviert
     * @return \SHC\Switchable\Switchable
     */
    public function enable($enabled) {

        if ($enabled == true) {

            $this->enabled = true;
        } else {

            $this->enabled = false;
        }
        return $this;
    }

    /**
     * gibt an ob das Element Aktiviert ist
     * 
     * @return Boolean 
     */
    public function isEnabled() {

        return $this->enabled;
    }
    
    /**
     * setzt die Sichtbarkeit dss Schaltelements
     * 
     * @param  Integer $visibility Sichtbarkeit
     * @return \SHC\Switchable\Switchable
     */
    public function setVisibility($visibility) {
        
        $this->visibility = $visibility;
        return $this;
    }
    
    /**
     * gibt die Sichtbarkeit des Schaltelementes zurueck
     * 
     * @return Integer
     */
    public function isVisible() {
        
        return $this->visibility;
    }

    /**
     * fuegt eine Benutzergruppen hinzu der es erlaubt ist das Element zu schalten
     * 
     * @param  \RWF\User\UserGroup $userGroup
     * @return \SHC\Switchable\Switchable
     */
    public function addAllowedUserGroup(UserGroup $userGroup) {

        $this->allowedUserGroups[] = $userGroup;
    }

    /**
     * entfernt eine Benutzergruppen der es erlaubt ist das Element zu schalten
     * 
     * @param  \RWF\User\UserGroup $userGroup
     * @return \SHC\Switchable\Switchable
     */
    public function removeAllowedUserGroup(UserGroup $userGroup) {

        $this->allowedUserGroups = array_diff($this->allowedUserGroups, array($userGroup));
        return $this;
    }

    /**
     * entfernt alle Benutzergruppen
     * 
     * @return \SHC\Switchable\Switchable
     */
    public function removeAllAllowedUserGroups() {

        $this->allowedUserGroups = array();
        return $this;
    }

    /**
     * gibt eine Liste mit allen erlaubten Benutzergruppen zurueck
     *
     * @return Array
     */
    public function listAllowedUserGroups() {

        return $this->allowedUserGroups;
    }

    /**
     * prueft ob ein Benutzer berechtigt ist das Element zu schalten
     *
     * @param \RWF\User\Visitor $user
     * @return Boolean
     */
    public function isUserEntitled(Visitor $user) {

        if (isset($this->allowedUserGroups[0]) && $this->allowedUserGroups[0] != '') {

            //Hauptgruppe pruefen
            if (in_array($user->getMainGroup(), $this->allowedUserGroups) || ($user instanceof User && $user->isOriginator())) {

                return true;
            }

            //Alle Benutzergruppen pruefen
            foreach ($user->listGroups() as $userGroup) {

                if ($userGroup instanceof UserGroup && in_array($userGroup, $this->allowedUserGroups)) {

                    return true;
                }
            }

            //keine berechtigte Gruppe gefunden
            return false;
        }
        return true;
    }

}
