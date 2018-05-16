<?php

namespace TopConcepts\JobInstance\Core;

/**
 * Class JobInstancePid
 * @package TopConcepts\JobInstance\Core
 */
class JobInstancePid
{
    /**
     * Die ID des Prozesses.
     *
     * @var integer
     */
    protected $pid;

    /**
     * Setzt die ID des Prozesses.
     *
     * @param   integer $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    /**
     * Beendet den zur ID gehörenden Prozess.
     *
     * @return  boolean
     */
    public function kill()
    {
        if ($this->isRunning() === false) {
            return false;
        }

        exec('kill ' . $this->pid);

        if ($this->isRunning() === false) {
            return true;
        }

        return false;
    }

    /**
     * Kontrolliert ob es einen Prozess mit der Prozess ID gibt.
     *
     * @return  boolean
     */
    public function isRunning()
    {
        $return = array();
        exec('ps -p ' . $this->pid, $return);

        return count($return) === 2;
    }

}
