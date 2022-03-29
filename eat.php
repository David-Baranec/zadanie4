<?php
$fileCreated = false;

// Read the JSON file
if(!file_exists('./storage/file.json')){
    touch('./storage/file.json');
    $fp = fopen('./storage/file.json', 'w');
    $data = ["timestamp" => (new DateTime())->getTimestamp(), "data" => []];
    fwrite($fp, json_encode($data));
    fclose($fp);

    $fileCreated = true;
}

$json = file_get_contents('./storage/file.json');

$interval = 0;
$timeDifference = 0;

// Decode the JSON file
$json_data = json_decode($json,true);

$jedla = $json_data['data'];

$interval = date_diff( DateTime::createFromFormat( 'U', $json_data['timestamp'] ), new DateTime());
$timeDifference = (new DateTime())->getTimestamp() - $json_data['timestamp'];

if($timeDifference > 800 || $fileCreated) {
    $ch = curl_init();

// set url
    curl_setopt($ch, CURLOPT_URL, "http://eatandmeet.sk/tyzdenne-menu");

//return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// $output contains the output string
    $output = curl_exec($ch);

// close curl resource to free up system resources
    curl_close($ch);

    $dom = new DOMDocument();

    @$dom->loadHTML($output);
    $dom->preserveWhiteSpace = false;

    $parseNodes = ["day-1", "day-2", "day-3", "day-4", "day-5", "day-6", "day-7"];

    $jedla = [
        ["date" => date('d.m.Y', strtotime('monday this week')), "day" => "Pondelok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('tuesday this week')), "day" => "Utorok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('wednesday this week')), "day" => "Streda", "menu" => []],
        ["date" => date('d.m.Y', strtotime('thursday this week')), "day" => "Štvrtok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('friday this week')), "day" => "Piatok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('saturday this week')), "day" => "Sobota", "menu" => []],
        ["date" => date('d.m.Y', strtotime('sunday this week')), "day" => "Nedeľa", "menu" => []],
    ];

    foreach ($parseNodes as $index => $nodeId) {

        $node = $dom->getElementById($nodeId);

        foreach ($node->childNodes as $menuItem) {
            if ($menuItem && $menuItem->childNodes->item(1) && $menuItem->childNodes->item(1)->childNodes->item(3)) {
                $nazov = trim($menuItem->childNodes->item(1)->childNodes->item(3)->childNodes->item(1)->childNodes->item(1)->nodeValue);
                $cena = trim($menuItem->childNodes->item(1)->childNodes->item(3)->childNodes->item(1)->childNodes->item(3)->nodeValue);
                $popis = trim($menuItem->childNodes->item(1)->childNodes->item(3)->childNodes->item(3)->nodeValue);
                array_push($jedla[$index]["menu"], "$nazov ($popis): $cena");
            }
        }
    }

    $data = ["timestamp" => (new DateTime())->getTimestamp(), "data" => $jedla];

    $fp = fopen('./storage/file.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
}