<?php

     	/*
         * Author: Matthew Leming
         * Meant to take various calendars from UNC — for now, the dining and events calendars — and convert them into an iCal format. The calendar functions are both given a URL, w$
         */

    require_once realpath(dirname(__FILE__)) . "/calendarMaker.php";
    $maker = new calendarMaker();
    $maker -> makeEventsCal('http://events.unc.edu/feed',  dirname(__FILE__)  . '/../events/Events-Calendar.ics');
    $maker -> makeDiningCal('http://menus.dining.unc.edu/', dirname(__FILE__)  . '/../dining');
?>





