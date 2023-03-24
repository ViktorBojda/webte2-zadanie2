<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<script>console.log('HERE');</script>";

require_once('config.php');
require_once('day_enum.php');

function curlDownload($url) {
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function getDOM($html) {
    $dom = new DOMDocument();
    $internalErrors = libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_use_internal_errors($internalErrors);
    return $dom;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['action']) && $_POST['action'] == 'download') {
        $failed = false;
        $pdo->beginTransaction();
        
        $dom = getDOM(curlDownload('http://www.freefood.sk/menu/#fiit-food'));
        $sql = "INSERT INTO restaurant_html (restaurant_name, html) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute(["fiitfood", $dom->saveHTML()]))
            $failed = true;
        
        $dom = getDOM(curlDownload('https://www.novavenza.sk/tyzdenne-menu'));
        // $xpath = new DOMXPath($dom);
        // $xpath_menu = $xpath->query('//div[@id="pills-tabContent"]');
        // $raw_menu = $dom->saveHTML($xpath_menu->item(0));
        $sql = "INSERT INTO restaurant_html (restaurant_name, html) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute(["venza", $dom->saveHTML()]))
            $failed = true;
        
        $dom = getDOM(curlDownload('http://eatandmeet.sk/tyzdenne-menu'));
        // $xpath = new DOMXPath($dom);
        // $xpath_menu = $xpath->query('//div[@class="tab-content weak-menu" and div[1][@id="day-1"]]');
        // $raw_menu = $dom->saveHTML($xpath_menu->item(0));
        $sql = "INSERT INTO restaurant_html (restaurant_name, html) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute(["eatandmeet", $dom->saveHTML()]))
            $failed = true;

        if ($failed) {
            $pdo->rollBack();
            echo "Nastala chyba. Zopakujte operáciu.";
        }
        else
            $pdo->commit();
    }

    if (isset($_POST['action']) && $_POST['action'] == 'parse') {
        $sql = "SELECT * FROM restaurant_html ORDER BY created_at DESC LIMIT 3";
        $restaurant_html_array = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($restaurant_html_array as $restaurant_html) {
            $sql = "INSERT INTO restaurant(name) VALUES(?)
                    ON DUPLICATE KEY UPDATE id=id";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$restaurant_html["restaurant_name"]])) {
                $sql = "SELECT id FROM restaurant WHERE name = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$restaurant_html["restaurant_name"]]);
                $restaurant_id = $stmt->fetchColumn();

                $dom = getDOM($restaurant_html["html"]);
                $xpath = new DOMXPath($dom);

                $failed = false;
                $pdo->beginTransaction();

                if ($restaurant_html["restaurant_name"] == "fiitfood") {
                    $daily_menu_list =  $xpath->query('//div[@id="fiit-food"]//ul[@class="daily-offer"]/li/ul[@class="day-offer"]');

                    $day_order = 1;
                    foreach ($daily_menu_list as $daily_menu) {
                        $xpath_query = './/text()';
                        $text_list = $xpath->evaluate($xpath_query, $daily_menu);
                        $menu_array = array();
                        $item_array = array();

                        for ($i=1; $i < $text_list->length + 1; $i++) {
                            $text = trim($text_list->item($i - 1)->textContent);

                            switch ($i % 3) {
                                case 1:
                                    $item_array["description"] = $text;
                                    break;
                                case 2:
                                    $item_array["name"] = $text;
                                    break;
                                case 0:
                                    $item_array["price"] = $text;
                                    $menu_array[] = $item_array;
                                    $item_array = array();
                                    break;
                                default:
                                    $failed = true;
                                    echo "Neznámy počet parametrov";
                                    break;
                            }
                        }

                        $sql = "INSERT INTO menu_item(name, restaurant_id, description, price, day, date) 
                                VALUES(:name, :restaurant_id, :description, :price, :day, :date)
                                ON DUPLICATE KEY UPDATE description = :description, price = :price, day = :day, date = :date";
                        $stmt = $pdo->prepare($sql);
                        foreach ($menu_array as $item)
                            if (!$stmt->execute([
                                ":name" => $item["name"],
                                ":restaurant_id" => $restaurant_id,
                                ":description" => $item["description"],
                                ":price" => $item["price"],
                                ":day" => Day::from($day_order)->name,
                                ":date" => date("Y-m-d", strtotime(Day::from($day_order)->name . " this week"))
                            ]))
                                $failed = true;
                        ++$day_order;
                    }
                }
                else if ($restaurant_html["restaurant_name"] == "venza") {
                    $daily_menu_list =  $xpath->query('//div[@id="pills-tabContent"]//div[@class="menubar"]/div');

                    $day_order = 1;
                    foreach ($daily_menu_list as $daily_menu) {
                        $xpath_query = './div/div';
                        $menu_item_list = $xpath->evaluate($xpath_query, $daily_menu);
                        $menu_array = array();
                        $item_array = array();
                        $is_soup_done = false;

                        foreach ($menu_item_list as $menu_item) {
                            $xpath_query = './/text()[normalize-space()]';
                            $text_list = $xpath->evaluate($xpath_query, $menu_item);

                            if ($is_soup_done) {
                                for ($i=0; $i < $text_list->length; $i++) {
                                    $text = trim($text_list->item($i)->textContent);
                                    switch ($i % 4) {
                                        case 0:
                                            $item_array["description"] = $text;
                                            break;
                                        case 1:
                                            $item_array["name"] = $text;
                                            break;
                                        case 2:
                                        case 3:
                                            if ($text[0] == "(")
                                                $item_array["name"] .= " " . $text;
                                            else if (is_numeric($text[0])) {
                                                $item_array["price"] = $text;
                                                $menu_array[] = $item_array;
                                                $item_array = array();
                                            }
                                            break;
                                        default:
                                            $failed = true;
                                            echo "Neznámy počet parametrov";
                                            break;
                                    }
                                }
                            }
                            else {
                                for ($i=1; $i < $text_list->length; $i++) {
                                    $text = trim($text_list->item($i)->textContent);
                                    if ($text[0] == "(")
                                        $item_array["name"] .= " " . $text;
                                    else if (is_numeric($text[0])) {
                                        $item_array["price"] = $text;
                                        $item_array["description"] = "Polievka";
                                        $menu_array[] = $item_array;
                                        $item_array = array();
                                    }
                                    else
                                        $item_array["name"] = $text;
                                }
                                $is_soup_done = true;
                            }
                        }

                        $sql = "INSERT INTO menu_item(name, restaurant_id, description, price, day, date) 
                                VALUES(:name, :restaurant_id, :description, :price, :day, :date)
                                ON DUPLICATE KEY UPDATE description = :description, price = :price, day = :day, date = :date";
                        $stmt = $pdo->prepare($sql);
                        foreach ($menu_array as $item)
                            if (!$stmt->execute([
                                ":name" => $item["name"],
                                ":restaurant_id" => $restaurant_id,
                                ":description" => $item["description"],
                                ":price" => $item["price"],
                                ":day" => Day::from($day_order)->name,
                                ":date" => date("Y-m-d", strtotime(Day::from($day_order)->name . " this week"))
                            ]))
                                $failed = true;
                        ++$day_order;
                    }
                }

                if ($failed) {
                    echo "Nastala chyba. Zopakujte operáciu.";
                    $pdo->rollBack();
                }
                else
                    $pdo->commit();
            }
            else
                echo "Nastala chyba. Zopakujte operáciu.";
        }
    }
}

unset($stmt);
unset($pdo);
?>

<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overenie API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="container-xl">
        <header>
            <h1 class="page-content text-center py-3 my-3">Bojda Nearby Lunch</h1>
        </header>

        <div class="page-content my-3">
            <nav class="navbar navbar-dark dark-blue-color">
                <div class="container-fluid">
                    <button class="navbar-toggler border-gray" type="button" data-bs-toggle="collapse" data-bs-target="#nav-toggle" 
                    aria-controls="nav-toggle" aria-expanded="false" aria-label="Zobraz menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </nav>
            <div class="collapse" id="nav-toggle">
                <div class="row dark-blue-color mx-0">
                    <a class="col-12 col-md-6 py-3 d-flex justify-content-center" href="index.php">Jedálny lístok</a>
                    <a class="col-12 col-md-6 py-3 nav-button-active d-flex justify-content-center" href="#">Overenie API</a>
                </div>
            </div>
        </div>

        <div class="page-content p-3">
            <h2 class="pb-3">Overenie metód API</h2>

            <form action="" method="post">
                <div class="row">
                    <div class="col-4 d-grid">
                        <button class='btn btn-primary' name="action" value="download" type='submit'>Stiahni</button>
                    </div>

                    <div class="col-4 d-grid">
                        <button class='btn btn-primary' name="action" value="parse" type='submit'>Rozparsuj</button>
                    </div>
                        
                    <div class="col-4 d-grid">
                        <button class='btn btn-danger' name="action" value="delete" type='submit'>Vymaž</button>
                    </div>
                </div>
            </form>

        </div>
        
    </div>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>