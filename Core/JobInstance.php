<?php

namespace TopConcepts\JobInstance\Core;


use TopConcepts\JobInstance\Core\Exception\JobInstanceException;

/**
 * Instanz eines Jobs / Scripts / Cronjobs
 *
 */
class JobInstance
{
    /**
     * Der Name des Programmes.
     *
     * @var string
     */
    protected $sName = null;

    /**
     * Die wie vielte Instanz des Programmes gestartet werden soll.
     *
     * @var integer
     */
    protected $iInstance = null;

    /**
     * Der Dateiname in dem die Daten des laufenden Programmes gespeichert
     * werden.
     *
     * @var string
     */
    protected $sFile = null;

    /**
     * Gibt an das Programm bereits läuft.
     *
     * @var boolean
     */
    protected $blAlreadyRunning = false;

    /**
     * Die ID des aktuellen Prozesses.
     *
     * @var integer
     */
    protected $iPid = null;

    /**
     * Gibt an ob die Job Instanz initialisiert wurde.
     *
     * @var boolean
     */
    protected $isInitialized = false;

    /**
     * Die maximale Zeit, die das Programm laufen darf.
     *
     * @var integer
     */
    protected $maxExecutionTime;

    /**
     * Konstruktor
     *
     * @param   string $sName
     * @param   integer $iInstance
     * @param   integer $maxExecutionTime
     *
     * @return void
     */
    public function __construct($sName, $iInstance = null, $maxExecutionTime = null)
    {
        if ($iInstance === null) {
            $iInstance = 1;
        }
        $this->sName            = $sName;
        $this->iInstance        = $iInstance;
        $this->sFile            = $this->getFolderForJobInstanceFile()
                                  . 'tc_jobinstance-' . $this->sName . '-' . $this->iInstance . '.pid';
        $this->iPid             = getmypid();
        $this->maxExecutionTime = $maxExecutionTime;
    }

    /**
     * Kontrolliert ob bereits ein entsprechendes Programm läuft.
     * Wenn die maximale Zeit für eine vorhandenes Programm abgelaufen ist wird
     * dieses automatisch beendet.
     * Wenn kein Programm läuft wird eine entsprechende Prozess Datei erstellt.
     * @throws JobInstanceException
     */
    public function initialize()
    {
        $this->isInitialized = true;

        if (is_file($this->sFile) === true) {
            $aInfo = json_decode(file_get_contents($this->sFile), true);
            $oPid  = oxNew(JobInstancePid::class, $aInfo['pid']);

            // pid lesen und checken
            if ($oPid->isRunning() === true) {
                /* Kontrolle wie lange der Prozess bereits läuft. */
                if ($aInfo['maxExecutionTime'] !== null && (time() - $aInfo['time']) > $aInfo['maxExecutionTime']) {
                    $oPid->kill();
                    throw new JobInstanceException('max execution of programm reached. running since ' . date('Y-m-d H:i:s', $aInfo['time']), JobInstanceException::ERROR_MAX_EXECUTION_REACHED);
                }

                $this->blAlreadyRunning = true;
                throw new JobInstanceException('Job-Instance already running!', JobInstanceException::ERROR_ALREADY_RUNNING);
            }

            // wenn nicht läuft, datei löschen
            if (unlink($this->sFile) === false) {
                throw new JobInstanceException('process not running and pid-file not deleteable!', JobInstanceException::ERROR_CAN_NOT_START);
            }
        }

        // schreiben der PID + time
        file_put_contents($this->sFile, json_encode(array('pid' => $this->iPid, 'time' => time(), 'maxExecutionTime' => $this->maxExecutionTime)));
    }

    /**
     * Löscht die erstellte Prozess Datei.
     */
    public function __destruct()
    {

        // wenn schon läuft, dann nix machen, ansonsten datei löschen
        if ($this->isInitialized === true && $this->blAlreadyRunning === false) {
            unlink($this->sFile);
        }
    }

    /**
     * Gibt das Zielverzeichnis für die pid-Datei zurück
     *
     * @return string
     */
    public function getFolderForJobInstanceFile()
    {
        return '/tmp/';
    }
}
