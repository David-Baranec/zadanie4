<?php
$fileCreated = false;

// Read the JSON file
if(!file_exists('./storage/freeFood.json')){
    touch('./storage/freeFood.json');
    $fp = fopen('./storage/freeFood.json', 'w');
    $data = ["timestamp" => (new DateTime())->getTimestamp(), "data" => []];
    fwrite($fp, json_encode($data));
    fclose($fp);

    $fileCreated = true;
}

// Read the JSON file
$json = file_get_contents('./storage/freeFood.json');
// Decode the JSON file
$json_data = json_decode($json,true);
$foodsKoliba = $json_data['data'];
$interval = date_diff( DateTime::createFromFormat( 'U', $json_data['timestamp'] ), new DateTime());
$timeDifference = (new DateTime())->getTimestamp() - $json_data['timestamp'];

if($timeDifference > 800 || $fileCreated) {
    $ch = curl_init();
// set url
    curl_setopt($ch, CURLOPT_URL, "http://www.freefood.sk/menu/#free-food");
//return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// $output contains the output string
    $output = curl_exec($ch);
// close curl resource to free up system resources
    curl_close($ch);

    $dom = new DOMDocument();

    @$dom->loadHTML($output);
    $dom->preserveWhiteSpace = false;

    $foodsKoliba = [
        ["date" => date('d.m.Y', strtotime('monday this week')), "day" => "Pondelok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('tuesday this week')), "day" => "Utorok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('wednesday this week')), "day" => "Streda", "menu" => []],
        ["date" => date('d.m.Y', strtotime('thursday this week')), "day" => "Štvrtok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('friday this week')), "day" => "Piatok", "menu" => []],
        ["date" => date('d.m.Y', strtotime('saturday this week')), "day" => "Sobota", "menu" => []],
        ["date" => date('d.m.Y', strtotime('sunday this week')), "day" => "Nedela", "menu" => []]
    ];

    $menu = $dom->getElementById("free-food");
    $menu = $menu->getElementsByTagName('div')->item(0);
    $menu = $menu->getElementsByTagName('ul')->item(0);
    $index = -1;

    foreach ($menu->childNodes as $day) {
        $day = $day->childNodes->item(1);
        foreach ($day->childNodes as $menuItem) {
            if ($menuItem && $menuItem->childNodes->item(1) && $menuItem->childNodes->item(2) && ($index < 5)) {
                $popis = trim($menuItem->childNodes->item(1)->nodeValue);
                $cena = trim($menuItem->childNodes->item(2)->nodeValue);
                array_push($foodsKoliba[$index]["menu"], "$popis: $cena");
            }
        }
        $index++;
    }

    $data = ["timestamp" => (new DateTime())->getTimestamp(), "data" => $foodsKoliba];

    $fp = fopen('./storage/freeFood.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
}

//echo "<pre>";
//var_dump($foodsKoliba);
//echo "</pre>";

