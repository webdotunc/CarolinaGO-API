<?php

$term = $_GET['term'];
$area = $_GET['area'];
$search = $_GET['search'];

if(empty($term) && empty($search) && empty($area)){
	echo file_get_contents('terms.json');
}
elseif(!empty($term) && empty($search) && empty($area)){
	$term_read = json_decode(file_get_contents('terms.json'), true);
	$area_read = json_decode(file_get_contents('areas.json'), true);
	$course_read = json_decode(file_get_contents($term . '.json'), true);
	$arr_output = array();
	foreach($course_read['courses'] as $item)
	{
		foreach($area_read['areas'] as $ditem)
		{
			if ($ditem['area'] == $item['subject'] && !in_array($ditem,$arr_output))
			{
				$arr_output[] = $ditem;
			}
		}
	}
	echo json_encode(array('areas' => $arr_output), true);
}

elseif(!empty($term) && empty($search) && !empty($area))
{
        $term_read = json_decode(file_get_contents('terms.json'), true);
        $area_read = json_decode(file_get_contents('areas.json'), true);
        $course_read = json_decode(file_get_contents($term . '.json'), true);
	$catalog_output = array();
	foreach($course_read['courses'] as $item)
	{
		if($item['subject'] == $area)
		{
			$catalog_output[] = $item;
		}
	}
	echo json_encode(array('courses' => $catalog_output));
}
elseif(!empty($term) && !empty($search) && empty($area))
{
	$term_read = json_decode(file_get_contents($term.'.json'), true);
	$catalog_output = array();
	foreach($term_read['courses'] as $item)
	{
		if(strripos(implode($item,' '), $search) !== FALSE)
		{
			$catalog_output[] = $item;
		}
	}
	echo json_encode(array('courses' => $catalog_output), true);
}
elseif(!empty($term) && !empty($search) && !empty($area))
{
        $term_read = json_decode(file_get_contents('terms.json'), true);
        $area_read = json_decode(file_get_contents('areas.json'), true);
        $course_read = json_decode(file_get_contents($term . '.json'), true);
        $catalog_output = array();
        foreach($course_read['courses'] as $item)
        {
                if($item['subject'] == $area && strripos(implode($item,' '), $search) !== FALSE)
                {
                        $catalog_output[] = $item;
                }
        }
	echo json_encode(array('courses' => $catalog_output));
}

?>
