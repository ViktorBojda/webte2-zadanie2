<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('config.php');

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
    $dom = new DOMDocument("1.0", "UTF-8");
    $internalErrors = libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_use_internal_errors($internalErrors);
    return $dom;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'download') {
        $dom = getDOM(curlDownload('http://www.freefood.sk/menu/#fiit-food'));
        $xpath = new DOMXPath($dom);
        $xpath_menu = $xpath->query('//div[@id="fiit-food"]//ul[@class="daily-offer"]');
        $raw_menu = $dom->saveHTML($xpath_menu->item(0));
        $sql = "INSERT INTO raw_data (restaurant_name, data) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["fiitfood", $raw_menu]);
        
        $dom = getDOM(curlDownload('https://www.novavenza.sk/tyzdenne-menu'));
        $xpath = new DOMXPath($dom);
        $xpath_menu = $xpath->query('//div[@id="pills-tabContent"]');
        $raw_menu = $dom->saveHTML($xpath_menu->item(0));
        $sql = "INSERT INTO raw_data (restaurant_name, data) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["venza", $raw_menu]);
        
        $dom = getDOM(curlDownload('http://eatandmeet.sk/tyzdenne-menu'));
        $xpath = new DOMXPath($dom);
        $xpath_menu = $xpath->query('//div[@class="tab-content weak-menu" and div[1][@id="day-1"]]');
        $raw_menu = $dom->saveHTML($xpath_menu->item(0));
        $sql = "INSERT INTO raw_data (restaurant_name, data) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["eatandmeet", $raw_menu]);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'parse') {
        $sql = "SELECT * FROM raw_data ORDER BY id DESC LIMIT 3";
        $raw_data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw_data as $raw_menu) {
            if ($raw_menu["restaurant_name"] == "fiitfood") {
                
            }
        }
    }
}




// $results = $xpath->query('//div[@id="fiit-food"]//ul[@class="day-offer"]/li//text()');

// foreach($nodes as $li) {
//     echo $li->nodeValue . "<br>";
// }

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