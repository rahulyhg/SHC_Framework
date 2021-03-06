<?php

namespace SHC\Command\All;

//Imports
use RWF\Core\RWF;
use RWF\Date\DateTime;
use RWF\Request\Commands\SyncCommand;
use RWF\Request\Request;
use RWF\Util\DataTypeUtil;
use SHC\Room\Room;
use SHC\Room\RoomEditor;
use SHC\Sensor\SensorPointEditor;
use SHC\Sensor\Sensors\AvmMeasuringSocket;
use SHC\Sensor\Sensors\BMP;
use SHC\Sensor\Sensors\CometDectRadiatorThermostat;
use SHC\Sensor\Sensors\DHT;
use SHC\Sensor\Sensors\DS18x20;
use SHC\Sensor\Sensors\EdimaxMeasuringSocket;
use SHC\Sensor\Sensors\GasMeter;
use SHC\Sensor\Sensors\HcSr04;
use SHC\Sensor\Sensors\Hygrometer;
use SHC\Sensor\Sensors\LDR;
use SHC\Sensor\Sensors\RainSensor;
use SHC\Sensor\Sensors\SCT013;
use SHC\Sensor\Sensors\WaterMeter;
use SHC\Sensor\vSensors\Energy;
use SHC\Sensor\vSensors\FluidAmount;
use SHC\Sensor\vSensors\Humidity;
use SHC\Sensor\vSensors\LightIntensity;
use SHC\Sensor\vSensors\Moisture;
use SHC\Sensor\vSensors\Power;
use SHC\Sensor\vSensors\Temperature;
use SHC\Switchable\Readable;
use SHC\Switchable\Switchable;
use SHC\Switchable\SwitchableEditor;
use SHC\Switchable\Switchables\FritzBox;
use SHC\Switchable\Switchables\Script;
use SHC\Switchable\Switchables\WakeOnLan;

/**
 * Daten eines Raumes Synchronisieren
 *
 * @author     Oliver Kleditzsch
 * @copyright  Copyright (c) 2014, Oliver Kleditzsch
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @since      2.0.0-0
 * @version    2.0.0-0
 */
class RoomSync extends SyncCommand {

    /**
     * maximale Ausfuehrungszeit
     *
     * @var Integer
     */
    protected $maxExecutionTime = 120;

    /**
     * Daten verarbeiten
     */
    public function processData() {

        /* @var $response \RWF\Request\SSEResponse  */
        $response = $this->response;

        //Raum Objekt laden
        $roomId = RWF::getRequest()->getParam('id', Request::GET, DataTypeUtil::INTEGER);
        $room = RoomEditor::getInstance()->getRoomById($roomId);

        //pruefen ob der Raum existiert
        if(!$room instanceof Room) {

            $response->addEvent('errorInvalidRoom');
            $response->addData('ungültige Raum Id');
            $response->setNoReconnectHeader();
            return;
        }

        //Verzoegerungszeiten
        $sensorSyncTime = DateTime::now();

        //100mal senden und dannach von selbst abbrechen
        for($i = 0; $i < 100; $i++) {

            //schaltbare und lesbare Elemente
            SwitchableEditor::getInstance()->loadData();
            $switchables = SwitchableEditor::getInstance()->listElementsForRoom($roomId, SwitchableEditor::SORT_NOTHING);
            $switchableValues = array();
            $wolValues = array();
            $readableValues = array();
            foreach ($switchables as $switchable) {

                //Berechtigungen pruefen
                if ($switchable->isUserEntitled(RWF::getVisitor()) && $switchable->isEnabled() && $switchable->isVisible()) {

                    if ($switchable instanceof WakeOnLan) {

                        $wolValues[$switchable->getId()] = $switchable->getState();
                    } elseif ($switchable instanceof Switchable) {

                        if($switchable instanceof Script && $switchable->getOnCommand() != '' && $switchable->getOffCommand() != '') {

                            $switchableValues[$switchable->getId()] = $switchable->getState();
                        } elseif($switchable instanceof FritzBox) {

                            if($switchable->getFunction() <= 3) {

                                $switchableValues[$switchable->getId()] = $switchable->getState();
                            }
                        } elseif(!$switchable instanceof Script) {

                            $switchableValues[$switchable->getId()] = $switchable->getState();
                        }
                    } elseif ($switchable instanceof Readable) {

                        //Status lesen ohne ihn zu speichern
                        $readableValues[$switchable->getId()] = $switchable->getState();
                    }
                }
            }
            if(count($switchableValues) > 0) {

                $response->addRetry(1000);
                $response->addEvent('syncSwitchables');
                $response->addArrayAsJson($switchableValues);
                $response->flush();
            }
            if(count($readableValues) > 0) {

                $response->addRetry(1000);
                $response->addEvent('syncReadables');
                $response->addArrayAsJson($readableValues);
                $response->flush();
            }
            if(count($wolValues) > 0) {

                $response->addRetry(1000);
                $response->addEvent('syncWOL');
                $response->addArrayAsJson($wolValues);
                $response->flush();
            }

            //Sensoren Synchronisieren
            if($sensorSyncTime <= DateTime::now()) {

                SensorPointEditor::getInstance()->loadData();
                $sensors = SensorPointEditor::getInstance()->listSensorsForRoom($roomId, SensorPointEditor::SORT_NOTHING);
                $ds18x20Values = array();
                $dhtValues = array();
                $bmpValues = array();
                $analogValues = array();
                $avmPowerValues = array();
                $fluidAmountValues = array();
                $cometThermostatValues = array();
                $edimaxPowerValues = array();
                $sctPowerValues = array();
                $distanceValues = array();
                $vEnergyValues = array();
                $vAmountValues = array();
                $vHumidityValues = array();
                $vLightIntensityValues = array();
                $vMoistureValues = array();
                $vPowerValues = array();
                $vTemaratureValues = array();
                foreach($sensors as $sensor) {

                    if($sensor->isVisible()) {

                        if ($sensor instanceof DS18x20) {

                            $ds18x20Values[$sensor->getId()] = array(
                                'temp' => $sensor->getDisplayTemperature()
                            );
                        } elseif ($sensor instanceof DHT) {

                            $dhtValues[$sensor->getId()] = array(
                                'temp' => $sensor->getDisplayTemperature(),
                                'hum' => $sensor->getDisplayHumidity()
                            );
                        } elseif ($sensor instanceof BMP) {

                            $bmpValues[$sensor->getId()] = array(
                                'temp' => $sensor->getDisplayTemperature(),
                                'press' => $sensor->getDisplayAirPressure(),
                                'alti' => $sensor->getDisplayAltitude()
                            );
                        } elseif ($sensor instanceof Hygrometer || $sensor instanceof RainSensor) {

                            $analogValues[$sensor->getId()] = array(
                                'value' => $sensor->getDisplayMoisture()
                            );
                        } elseif ($sensor instanceof LDR) {

                            $analogValues[$sensor->getId()] = array(
                                'value' => $sensor->getDisplayLightIntensity()
                            );
                        } elseif ($sensor instanceof AvmMeasuringSocket) {

                            $avmPowerValues[str_replace(' ', '-', $sensor->getId())] = array(
                                'temp' => $sensor->getDisplayTemperature(),
                                'power' => $sensor->getDisplayPower(),
                                'energy' => $sensor->getDisplayEnergy()
                            );
                        } elseif ($sensor instanceof GasMeter || $sensor instanceof WaterMeter) {

                            $fluidAmountValues[$sensor->getId()] = array(
                                'amount' => $sensor->getDisplayFluidAmount()
                            );
                        } elseif ($sensor instanceof CometDectRadiatorThermostat) {

                            $cometThermostatValues[$sensor->getId()] = array(
                                'temp' => $sensor->getDisplayTemperature()
                            );
                        } elseif ($sensor instanceof EdimaxMeasuringSocket) {

                            $edimaxPowerValues[str_replace('.', '_', $sensor->getId())] = array(
                                'power' => $sensor->getDisplayPower(),
                                'energy' => $sensor->getDisplayEnergy()
                            );
                        } elseif ($sensor instanceof SCT013) {

                            $sctPowerValues[$sensor->getId()] = array(
                                'power' => $sensor->getDisplayPower()
                            );
                        } elseif ($sensor instanceof HcSr04) {

                            $distanceValues[$sensor->getId()] = array(
                                'dist' => $sensor->getDisplayDistance()
                            );
                        } elseif ($sensor instanceof Energy) {

                            $vEnergyValues[$sensor->getId()] = array(
                                'sum' => $sensor->getSumDisplayEnergy()
                            );
                        } elseif ($sensor instanceof FluidAmount) {

                            $vAmountValues[$sensor->getId()] = array(
                                'sum' => $sensor->getSumDisplayFluidAmount()
                            );
                        } elseif ($sensor instanceof Humidity) {

                            $vHumidityValues[$sensor->getId()] = array(
                                'min' => $sensor->getMinDisplayHunidity(),
                                'avg' => $sensor->getAvarageDisplayHunidity(),
                                'max' => $sensor->getMaxDisplayHunidity()
                            );
                        } elseif ($sensor instanceof LightIntensity) {

                            $vLightIntensityValues[$sensor->getId()] = array(
                                'min' => $sensor->getMinDisplayLightIntensity(),
                                'avg' => $sensor->getAvarageDisplayLightIntensity(),
                                'max' => $sensor->getMaxLightIntensity()
                            );
                        } elseif ($sensor instanceof Moisture) {

                            $vMoistureValues[$sensor->getId()] = array(
                                'min' => $sensor->getMaxDisplayMoisture(),
                                'avg' => $sensor->getAvarageDisplayMoisture(),
                                'max' => $sensor->getMaxDisplayMoisture()
                            );
                        } elseif ($sensor instanceof Power) {

                            $vPowerValues[$sensor->getId()] = array(
                                'avg' => $sensor->getAvarageDisplayPower(),
                                'sum' => $sensor->getSumDisplayPower()
                            );
                        } elseif ($sensor instanceof Temperature) {

                            $vTemaratureValues[$sensor->getId()] = array(
                                'min' => $sensor->getMinTemperature(),
                                'avg' => $sensor->getAvarageTemperature(),
                                'max' => $sensor->getMaxTemperature()
                            );
                        }
                    }
                }
                if(count($ds18x20Values) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncDS18x20');
                    $response->addArrayAsJson($ds18x20Values);
                    $response->flush();
                }
                if(count($dhtValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncDHT');
                    $response->addArrayAsJson($dhtValues);
                    $response->flush();
                }
                if(count($bmpValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncBMP');
                    $response->addArrayAsJson($bmpValues);
                    $response->flush();
                }
                if(count($analogValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncAnalog');
                    $response->addArrayAsJson($analogValues);
                    $response->flush();
                }
                if(count($avmPowerValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncAvmPowerSocket');
                    $response->addArrayAsJson($avmPowerValues);
                    $response->flush();
                }
                if(count($fluidAmountValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncFluidAmountMeters');
                    $response->addArrayAsJson($fluidAmountValues);
                    $response->flush();
                }
                if(count($cometThermostatValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncCometThermostat');
                    $response->addArrayAsJson($cometThermostatValues);
                    $response->flush();
                }
                if(count($edimaxPowerValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncEdimaxPowerSocket');
                    $response->addArrayAsJson($edimaxPowerValues);
                    $response->flush();
                }
                if(count($sctPowerValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncSctPower');
                    $response->addArrayAsJson($sctPowerValues);
                    $response->flush();
                }
                if(count($distanceValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncDistance');
                    $response->addArrayAsJson($distanceValues);
                    $response->flush();
                }
                if(count($vEnergyValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvEnergy');
                    $response->addArrayAsJson($vEnergyValues);
                    $response->flush();
                }
                if(count($vAmountValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvAmount');
                    $response->addArrayAsJson($vAmountValues);
                    $response->flush();
                }
                if(count($vHumidityValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvHumidity');
                    $response->addArrayAsJson($vHumidityValues);
                    $response->flush();
                }
                if(count($vLightIntensityValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvLightIntensity');
                    $response->addArrayAsJson($vLightIntensityValues);
                    $response->flush();
                }
                if(count($vMoistureValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvMoisture');
                    $response->addArrayAsJson($vMoistureValues);
                    $response->flush();
                }
                if(count($vPowerValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvPower');
                    $response->addArrayAsJson($vPowerValues);
                    $response->flush();
                }
                if(count($vTemaratureValues) > 0) {

                    $response->addRetry(1000);
                    $response->addEvent('syncvTemperature');
                    $response->addArrayAsJson($vTemaratureValues);
                    $response->flush();
                }

                //naechste Ausfuehrungszeit
                $sensorSyncTime->add(new \DateInterval('PT5S'));
            }

            //1 Sekunde Wartezeit zwischen den durchlaeufen
            sleep(1);
        }
    }
}
