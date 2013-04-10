<?php

class Mutex
{
    private $lockFile;
    private $name;

    function __construct($a)
    {
        $this->lockFile = "tmp/".$a.".lock";
        $this->name = $a;
    }

    function lock()
    {

        if (!file_exists("tmp/")) {

            mkdir("tmp");

        }


        if (file_exists($this->lockFile)) {

            return false;

        } else {

            if (!touch($this->lockFile)) {

                return false;

            } else {

                return true;

            }
        }
    }

    function unlock()
    {

        if (!(unlink($this->lockFile))) {

            return false;

        } else {

            return true;

        }
    }
}
