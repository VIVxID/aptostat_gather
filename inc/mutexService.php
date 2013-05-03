<?php

class MutexService
{

    private $killswitchPath;
    private $lockPath;

    public function __construct()
    {
        $this->killswitchPath = API_PATH . "app/lock/gatherKill.lock";
        $this->lockPath = "lock/lockCollection.lock";
    }

    public function checkKillswitch()
    {
        if (file_exists($this->killswitchPath)) {
            return false;
        } else {
            return true;
        }
    }

    public function lockCollection()
    {
        if (!is_dir("lock")) {
            mkdir("lock");
        }

        if (!file_exists($this->lockPath)) {
            touch($this->lockPath);
            return true;
        } else {
            unlink($this->lockPath);
            return false;
        }
    }

    public function unlockCollection()
    {
        if (file_exists($this->lockPath)) {
            unlink($this->lockPath);
        }
    }

}