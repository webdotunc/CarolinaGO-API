<?php
  
  /*
   * Author: Matthew Leming
   * Description: This script takes information from the given XML feed 
   — one which indicates the days in which restaurants are open — and 
   converts it into an iCal format. For each restaurant, it outputs one 
   iCal, which is named according to the location id associated with the 
   establishment. However, it only outputs calendars for that particular 
   day; it doesn't output information associated with tomorrow's to 
   yesterday's menus/times. Even so, this could be modified to support 
   that. The iCals are created from Eluceo's iCal maker 
   (https://github.com/eluceo/iCal).
   
   */
  
  /*
   * The iCal maker paths.
   */
  
  require_once realpath(dirname(__FILE__) . "/iCal-master/src/Eluceo/iCal/Component.php");
  require_once realpath(dirname(__FILE__) . "/iCal-master/src/Eluceo/iCal/Property.php");
  require_once realpath(dirname(__FILE__) . "/iCal-master/src/Eluceo/iCal/PropertyBag.php");
  require_once realpath(dirname(__FILE__) . "/iCal-master/src/Eluceo/iCal/Component/Calendar.php");
  require_once realpath(dirname(__FILE__) . "/iCal-master/src/Eluceo/iCal/Component/Event.php");
  require_once realpath(dirname(__FILE__) . "/iCal-master/src/Eluceo/iCal/Component/Timezone.php");
  
  date_default_timezone_set('America/New_York');
  
  class calendarMaker{
    
    /* A quick function to encapsulate event making. */
    function getEvent($start_time, $end_time, $event_summary){
      $vEvent = new \Eluceo\iCal\Component\Event();
      $vEvent->setDtStart(new \DateTime($start_time));
      $vEvent->setDtEnd(new \DateTime($end_time));
      $vEvent->setNoTime(false);
      $vEvent->setUseTimezone(true);
      $vEvent->setSummary($event_summary);
      return $vEvent;
    }
    
    /* Encapsulates calendar outputs */
    function putCal($output_path, $raw_calendar){
      if(file_exists($output_path)){
        unlink($output_path);
      }
      file_put_contents($output_path, $raw_calendar);
    }
    
    /* Adds a range of dates that aren't today to a calendar variable, given the weeks of the day that it occurs on.*/
    /* It would be preferable to add a multi-date range, but given the limitations of the older version of the iCal maker, this is the best I could do.
    */
    function addDateRange($vCalendar, $start, $end, $dotw, $open, $close, $pubnote){
      $start = strtotime($start);
      $end = strtotime($end);
      if ($start >= $end)
        return $vCalendar;
      $shortdays = array("Mo","Tu","We","Th","Fr","Sa","Su");
      $longdays = array("Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
      $dotw = split(",",  str_replace($shortdays,$longdays,$dotw));
      while($start < $end){
        if ($start < strtotime("today") || $start > strtotime("tomorrow -1 minute"))
          $vCalendar->addEvent($this->getEvent(date('m/d/Y', $start) . " " . $open, date('m/d/Y', $start) . " " . $close, $pubnote));
        $start = strtotime("next " . $dotw[0], $start);
        array_push($dotw, array_shift($dotw));
      }
      return $vCalendar;
    }
    
    
    /* Uses regular expressions to get meal and menu station information out of a dining meal page, given the raw HTML*/
/*    function getMealDescriptionFromHTML($raw){
      preg_match_all("/<span class=\"(DLMDRecipeName|DLMDMenuStationName)\">[^<]+<\/span>/", $raw, $matches);
      $patterns = array ("/<span class=\"DLMDRecipeName\">([^<]*)<\/span>/","/<span class=\"DLMDMenuStationName\">([^<]*)<\/span>/");
      $replace  = array ("<br>\\1 ","<br><br><b>\\1</b>");
      return implode(preg_replace($patterns, $replace, $matches[0]));
    }
*/

    function getMealDescriptionDictionaryFromHTML($file_url){
      $html = file_get_html($file_url);
      $meals=[];
      if($html === FALSE){
        exit("file_get_contents failed for the provided XML URL:" . $file_url);
      }
      foreach($html->find('div[class=menu tab-pane]') as $Meals) {
        $meal = [];
        foreach($Meals->find('div[class=stations] div[class=station-wrap]') as $Meal) {
          $meal_name = $Meal->find('h3',0)->plaintext;
          $meal[$meal_name] = [];
          foreach($Meal->find('ul li') as $Food_Item) {
            var_dump($Food_Item->plaintext);
            array_push($meal[$meal_name], (string)$Food_Item);
          }
        }
        $meals[(string)$Meals->id] = $meal;
      }
      return $meals;
    }
    
    function sanitizeString($raw){
      $patterns = array("/&amp;/","/&#39;/","/-/","/ /","/\//");
      $replace = array("","","_","_","");
      return preg_replace($patterns, $replace, $raw);
    }
    

    /* The main function for converting XML pages into dining calendars*/
    function makeDiningCal($file_url, $output_path){{
      include('simple_html_dom.php');
      /* This is VERY important for menu retrieval. This is a URL formatted to retrieve menus from the current dining website. Currently, the first string (%s) is a date (07/07/2014, for example), the second is the location (only Top of Lenoir and Rams Head Dining Hall are supported, I believe), and the third is the particular meal name (which can vary). This is mainly for Lenoir and Rams Head. It is important to change this as soon as any URLs are altered. */
      $url_format = $file_url . '%s'.'?date=2015-04-14';
      $dataName = 'data-name';
      $dataOpen = 'data-open';
      $dataClose = 'data-close';
      
      /* This loads the XML string into a raw form. The @ represses any PHP warnings in case the network is down, and if the xml is empty it exits to prevent faulty iCals from being made*/
      $html = file_get_html($file_url);
      //var_dump($this->getMealDescriptionDictionaryFromHTML($file_url));

      if($html === FALSE){
        exit("file_get_contents failed for the provided XML URL:" . $file_url);
      }
      /* This is a weird mechanism for (1) keeping track of which places are open or closed today, and (2) finding out which dining locations have online menus associated with them. Study the XML sheets closer, but essentially the XML sheets are divided into two parts, so it is necessary to "record" some information in these arrays prior to actually building and iCals.*/
      /* This loops through each individual location in the XML and starts spitting out calendars labeled "dining-?.ics", with the ? being their location id*/
      foreach($html->find('div.location-group div[class=row location]') as $Location) {
        //echo $Location;
        $meal_url = sprintf($url_format,$Location->find('div.name-wrap h4 a',0)->href);
        $this->getMealDescriptionDictionaryFromHTML($meal_url);
        $vCalendar = new \Eluceo\iCal\Component\Calendar('www.example.com');
        $Location_Title = $Location->find('div.name-wrap h4 a[href]',0)->plaintext;
        $Location_ID =  $this->sanitizeString($Location->find('div.name-wrap h4 a',0)->href);
        //var_dump( $Location_Title);
        $Hours_Info = $Location->find('div.hours-info',0);
        foreach($Hours_Info->find('div[class=hours hours-row]') as $hours){
          $hours_event_title = (string)$hours->$dataName;
          $open = (string)$hours->$dataOpen;
          $close = (string)$hours->$dataClose;
          if ($close == "12:00am"){
            $close = "11:59pm";
          }
          $vEvent = $this->getEvent(date('Y-m-d H:i',$open), date('Y-m-d H:i',$close), $hours_event_title);
          $vCalendar->addEvent($vEvent);
          }
        $this->putCal($output_path. "/dining-".$Location_ID.".ics", $vCalendar->render());
        }
      }
    }
 /***************/
 
    
    /* This makes events from the UNC Event Calendar. Much simpler than what you see above. */
    
    function makeEventsCal($xml_file_url, $output_path){
      $url_format = "%s?date=%s";
      $days_ahead = 10;
      
      $vCalendar = new \Eluceo\iCal\Component\Calendar('www.example.com');
      $title_list = array();
      for ($i = 0; $i < $days_ahead; $i++){
        $target_date = date('Y-m-d', strtotime("today + " . $i));
        $raw = @file_get_contents(sprintf($url_format, $xml_file_url, $target_date));
        $xml = simplexml_load_string($raw);
        if($xml === FALSE){
          exit("file_get_contents failed for the provided XML URL:" . $xml_file_url);
        }
        $channel = $xml->channel;
        
        
        foreach($channel->item as $item) {
          $event_title = $item->title;
          $event_start = $item->fullstartday . " " . $item->starttime;
          $event_end = $item->fullstartday . " " . $item->endtime;
          $event_description = $item->description;
          $event_location = $item->location;
          $vEvent = $this->getEvent($event_start, $event_end, $event_title);
          $vEvent->setDescription($event_description);
          $vEvent->setLocation($event_location);
          /* Lengthy events aren't added */
          if ($item->ongoing == 0 && strtotime("+5 days", strtotime($item->fullstartday)) > strtotime($item->fullendday) && !in_array((string)$event_title, $title_list)){
            $vCalendar->addEvent($vEvent);
            
            }
          array_push($title_list, $event_title);
          
        }
      }
      
      $this->putCal($output_path, $vCalendar->render());
    }
  }
  ?>

