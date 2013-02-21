<?php

class Mutex
{
    private $lockFile;
    private $name
    private $expireTime = 1800;

    function __construct($a)
    {
        $this->lockFile = "tmp/".$a.".lock";
        $this->name = $a;
    }

    function lock()
    {
        //Check if the file exists
        if (file_exists($this->lockFile)) {

            //If the timestamp is < 30min, exit.
            if (filemtime($this->lockFile) > (time() - $this->expireTime)) {

                //Log the time until expiration.
                $remainingTime = (filemtime($this->lockFile) - (time() - $this->expireTime)) / 60;
                Log::writeLog($name,"Process locked, expires in ".number_format($remainingTime,0)." minutes.");
                return false;

            } else {
                //If the timestamp is > 30min, attempt to delete and remake.
                if (unlink($this->lockFile)) {

                    Log::writeLog($name,"WARNING: Lock expired");

                    if(!touch($this->lockFile)) {

                        Log::writeLog($name,"WARNING: Unable to create lock. Exiting.");
                        return false;

                    }

                } else {
                    //If unable to remove lock.
                    Log::writeLog($name,"WARNING: Unable to remove lock. Exiting.");
                    return false;

                }

            }

        } else {
            //If file does not exist, create it.
            if (!touch($this->lockFile)) {

                Log::writeLog($name,"WARNING: Unable to create lock. Exiting");
                return false;

            } else {
                return true;
            }
        }
    }

    function unlock()
    {
        //
        //Cleaning up the mutex lock
        //
        if (!(unlink($this->lockFile))) {
            Log::writeLog($name,"WARNING: Unable to delete lock at cleanup");
            return false;
        } else {
            return true;
        }
    }
}
