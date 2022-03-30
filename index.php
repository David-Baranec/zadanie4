<?php
include "eat.php";
include "freeFood.php";

$fileCreated = false;

// Read the JSON file
if (!file_exists('./storage/fileDelikanti.json')) {
    touch('./storage/fileDelikanti.json');
    $fp = fopen('./storage/fileDelikanti.json', 'w');
    $data = ["timestamp" => (new DateTime())->getTimestamp(), "data" => []];
    fwrite($fp, json_encode($data));
    fclose($fp);

    $fileCreated = true;
}

// Read the JSON file
$json = file_get_contents('./storage/fileDelikanti.json');

$interval = 0;
$timeDifference = 0;

// Decode the JSON file
$json_data = json_decode($json, true);

$foods = $json_data['data'];
$foodsEat = $jedla;
$contactEatPrinted= $contactEat;
$foodsPrinted = $foods;
$foodsEatPrinted = $foodsEat;
$foodsFreeFoodPrinted = $foodsFreeFood;

$interval = date_diff(DateTime::createFromFormat('U', $json_data['timestamp']), new DateTime());
$timeDifference = (new DateTime())->getTimestamp() - $json_data['timestamp'];

if ($timeDifference > 8 || $fileCreated) {
    $ch = curl_init();

    // set url
    curl_setopt($ch, CURLOPT_URL, "https://www.delikanti.sk/prevadzky/3-jedalen-prif-uk/");

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // $output contains the output string
    $output = curl_exec($ch);

    // close curl resource to free up system resources
    curl_close($ch);

    $dom = new DOMDocument();

    @$dom->loadHTML($output);
    $dom->preserveWhiteSpace = false;
    $finder = new DomXPath($dom);
    $classname="col-xs-12 col-md-4 col-md-offset-1 col-lg-4 col-lg-offset-1 single-footer";
    $contactDelikanti = $finder->query("//*[contains(@class, '$classname')]");
    $contactDelikanti = $contactDelikanti->item(0)->nodeValue;
    $tables = $dom->getElementsByTagName('table');

    $rows = $tables->item(0)->getElementsByTagName('tr');
    $index = 0;
    $dayCount = 0;

    $foods = [];
    $foodCount = $rows->item(0)->getElementsByTagName('th')->item(0)->getAttribute('rowspan');

    foreach ($rows as $row) {

        if ($row->getElementsByTagName('th')->item(0)) {
            $foodCount = $row->getElementsByTagName('th')->item(0)->getAttribute('rowspan');

            $day = trim($rows->item($index)->getElementsByTagName('th')->item(0)->getElementsByTagName('strong')->item(0)->nodeValue);

            $th = $rows->item($index)->getElementsByTagName('th')->item(0);

            foreach ($th->childNodes as $node)
                if (!($node instanceof \DomText))
                    $node->parentNode->removeChild($node);

            $date = trim($rows->item($index)->getElementsByTagName('th')->item(0)->nodeValue);


            array_push($foods, ["date" => $date, "day" => $day, "menu" => []]);

            for ($i = $index; $i <  $index + intval($foodCount); $i++) {
                if ($foods[$dayCount])
                    array_push($foods[$dayCount]["menu"], trim($rows->item($i)->getElementsByTagName('td')->item(1)->nodeValue));
            }
            $index += intval($foodCount);
            $dayCount++;
        }
    }

    $data = ["timestamp" => (new DateTime())->getTimestamp(), "data" => $foods];

    $fp = fopen('./storage/fileDelikanti.json', 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
}
?>

<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="favicon.png">
 
    <title>Menu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>

<body class=" bg-dark text-white">
    <br><br>
    <div class="container bg-secondary text-white">
        <h1 class="text-center text-white">FEI faculty surrounding restaurants daily menu</h1>
        <br>
        <p class="text-center text-white">You can sort out menu by clicking the header of table by separate day. To show whole menu again click on 'restaurant' header.</p>
        <br>
        <p class=" text-white">FREEFOOD:     Pavilón Matematiky  Fakulta matematiky, fyziky a informatiky UK  Mlynská dolina  842 48 Bratislava</p>
        <p class=" text-white">DELIKANTI:    Delikanti s.r.o.; Nám.Hraničiarov 35; 851 03 Bratislava</p>
        <p class=" text-white">EAT AND MEET:    Staré Grunty 36; Átriaky, Blok AD-U</p>
       
       <?php 
           // echo "<div>Eat and meet: ".$contactEatPrinted."</div>";
            //echo "<div>Delikanti: ".$contactDelikanti."</div>"
        ?>
        
        <table class="table table-striped table-responsive text-white">
            <thead class="thead-dark">
                <tr>
                    <th style='cursor: pointer' onclick="showAll()">Restaurant</th>
                    <?php
                    $index = 0;

                    foreach ($foodsEat as $item) {
                        if (isset($item['day'])) {
                            echo "<th id='$index' style='cursor: pointer' class= 'text-white' onclick='headerClick($index)'>" . $item['day'] . "<br/>" . $item['date'] . "</th>";
                        }
                        $index++;
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
               
                <tr>
                    <th>Delikanti</th>
                    <?php
                    $index = 0;

                    foreach ($foodsPrinted as $item) {
                        if (isset($item['menu'])) {
                            echo "<td class= 'text-white' style='padding: 10px'><div id='column_delikanti-$index'>" . implode("<hr/>", $item['menu']) . "</div></td>";
                        }

                        $index++;
                    }
                    ?>
                </tr>
                <tr>
                    <hr>
                     <th>Eat and meet</th> 
                    <?php
                    $index = 0;

                    foreach ($foodsEatPrinted as $item) {
                        if (isset($item['menu'])) {
                            echo "<td class= 'text-white' style='padding: 10px'><div id='column_eatAndMMeet-$index'>" . implode("<hr/>", $item['menu']) . "</div></td>";
                        }

                        $index++;
                    }
                    ?>
                </tr>
                <tr>
                    <hr>
                    <th>FreeFood</th>

                    <?php
                    $index = 0;

                    foreach ($foodsFreeFoodPrinted as $item) {
                        if (isset($item['menu'])) {
                            echo "<td class= 'text-white' id='col-$index' style='padding: 10px'><div id='column_koliba-$index'>" . implode("<hr/>", $item['menu']) . "</div></td>";
                        }

                        $index++;
                    }
                    ?>
                </tr>
            </tbody>
        </table>
    </div>
    <script>
        //https://stackoverflow.com/questions/17518035/showing-hiding-table-rows-with-javascript-can-do-with-id-how-to-do-with-clas
        const headerClick = (id) => {
            console.log(id)

            for (let i = 0; i < 7; i++) {
                if (i !== id) {
                    document.querySelector("#column_eatAndMMeet" + i).style.display = 'none';
                    document.querySelector("#column_delikanti" + i).style.display = 'none';
                    document.querySelector("#column_koliba" + i).style.display = 'none';
                } else {
                    document.querySelector("#column_eatAndMMeet" + i).style.display = 'table-cell';
                    document.querySelector("#column_delikanti" + i).style.display = 'table-cell';
                    document.querySelector("#column_koliba" + i).style.display = 'table-cell';
                }
            }
        }

        const showAll = () => {
            for (let i = 0; i < 7; i++) {
                document.querySelector("#column_eatAndMeet" + i).style.display = 'table-cell';
                document.querySelector("#column_delikanti" + i).style.display = 'table-cell';
                document.querySelector("#column_koliba" + i).style.display = 'table-cell';
            }
        }
    </script>

</body>

</html>