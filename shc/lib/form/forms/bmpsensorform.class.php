<?php

namespace SHC\Form\Forms;

//Imports
use RWF\Core\RWF;
use RWF\Form\DefaultHtmlForm;
use RWF\Form\FormElements\FloatInputField;
use RWF\Form\FormElements\OnOffOption;
use RWF\Form\FormElements\TextField;
use SHC\Form\FormElements\RoomChooser;
use SHC\Room\Room;
use SHC\Sensor\Sensors\BMP;

/**
 * BMP Sensor Formular
 *
 * @author     Oliver Kleditzsch
 * @copyright  Copyright (c) 2014, Oliver Kleditzsch
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @since      2.0.0-0
 * @version    2.0.0-0
 */
class BMPSensorForm extends DefaultHtmlForm {

    /**
     * @param BMP $sensor
     */
    public function __construct(BMP $sensor = null) {

        //Konstruktor von TabbedHtmlForm aufrufen
        parent::__construct();

        RWF::getLanguage()->disableAutoHtmlEndocde();

        //Name des Sensors
        $name = new TextField('name', ($sensor instanceof BMP ? $sensor->getName() : ''), array('minlength' => 3, 'maxlength' => 25));
        $name->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.name'));
        $name->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.name.description'));
        $name->requiredField(true);
        $this->addFormElement($name);

        //Raum
        $room = new RoomChooser('room', ($sensor instanceof BMP  && $sensor->getRoom() instanceof Room ? $sensor->getRoom()->getId() : null));
        $room->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.room'));
        $room->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.room.description'));
        $room->requiredField(true);
        $this->addFormElement($room);

        //Sichtbarkeit
        $visibility = new OnOffOption('visibility', ($sensor instanceof BMP ? $sensor->isVisible() : true));
        $visibility->setOnOffLabel();
        $visibility->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.visibility'));
        $visibility->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.visibility.description'));
        $visibility->requiredField(true);
        $this->addFormElement($visibility);

        //Temperatur Sichtbar
        $temperatureVisibility = new OnOffOption('temperatureVisibility', ($sensor instanceof BMP ? $sensor->isTemperatureVisible() : true));
        $temperatureVisibility->setOnOffLabel();
        $temperatureVisibility->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.temperatureVisibility'));
        $temperatureVisibility->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.temperatureVisibility.description'));
        $temperatureVisibility->requiredField(true);
        $this->addFormElement($temperatureVisibility);

        //Temperatur Offset
        $temperatureOffset = new FloatInputField('tempOffset', ($sensor instanceof BMP ? $sensor->getTemperatureOffset() : 0.0), array('min' => -10.0, 'max' => 10.0, 'step' => 0.1));
        $temperatureOffset->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.temperatureOffset'));
        $temperatureOffset->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.offset.description'));
        $temperatureOffset->requiredField(true);
        $this->addFormElement($temperatureOffset);

        //Luftdruck sichtbar
        $pressureVisibility = new OnOffOption('pressureVisibility', ($sensor instanceof BMP ? $sensor->isPressureVisible() : true));
        $pressureVisibility->setOnOffLabel();
        $pressureVisibility->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.pressureVisibility'));
        $pressureVisibility->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.pressureVisibility.description'));
        $pressureVisibility->requiredField(true);
        $this->addFormElement($pressureVisibility);

        //Luftdruck Offset
        $pressureOffset = new FloatInputField('pressOffset', ($sensor instanceof BMP ? $sensor->getPressureOffset() : 0.0), array('min' => -100.0, 'max' => 100.0, 'step' => 0.1));
        $pressureOffset->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.pressureOffset'));
        $pressureOffset->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.offset.description'));
        $pressureOffset->requiredField(true);
        $this->addFormElement($pressureOffset);

        //Standorthoehe sichtbar
        $altitudeVisibility = new OnOffOption('altitudeVisibility', ($sensor instanceof BMP ? $sensor->isAltitudeVisible() : true));
        $altitudeVisibility->setOnOffLabel();
        $altitudeVisibility->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.altitudeVisibility'));
        $altitudeVisibility->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.altitudeVisibility.description'));
        $altitudeVisibility->requiredField(true);
        $this->addFormElement($altitudeVisibility);

        //Hoehen Offset
        $altitudeOffset = new FloatInputField('altiOffset', ($sensor instanceof BMP ? $sensor->getAltitudeOffset() : 0.0), array('min' => -100.0, 'max' => 100.0, 'step' => 0.1));
        $altitudeOffset->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.altitudeOffset'));
        $altitudeOffset->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.offset.description'));
        $altitudeOffset->requiredField(true);
        $this->addFormElement($altitudeOffset);

        //Daten Aufzeichnung
        $dataRecording = new OnOffOption('dataRecording', ($sensor instanceof BMP ? $sensor->isDataRecordingEnabled() : false));
        $dataRecording->setOnOffLabel();
        $dataRecording->setTitle(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.dataRecording'));
        $dataRecording->setDescription(RWF::getLanguage()->get('acp.switchableManagement.form.sensorForm.dataRecording.description'));
        $dataRecording->requiredField(true);
        $this->addFormElement($dataRecording);

        RWF::getLanguage()->enableAutoHtmlEndocde();
    }
}