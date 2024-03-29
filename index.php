<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('config.php');

$sql = "SELECT * FROM restaurant";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$tmp_restaurant_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tmp_restaurant_data as $item)
    $restaurant_names[$item['id']] = $item['name'];

$day_filter = null;
if (isset($_GET['day_filter']) && $_GET['day_filter'] != "all") {
    $day_filter = $_GET['day_filter'];
    $date = date("Y-m-d", strtotime("{$day_filter} this week"));
    $response = file_get_contents("https://site60.webte.fei.stuba.sk/webte2-zadanie2/api.php?date={$date}");
}
else
    $response = file_get_contents('https://site60.webte.fei.stuba.sk/webte2-zadanie2/api.php');
$response = json_decode($response, true);

$grouped_data = array();
foreach ($response as $item) {
    if ($item['day'] !== null)
            $grouped_data[$item['restaurant_id']][$item['day']][] = $item;
    else
        $grouped_data[$item['restaurant_id']][null][] = $item;
}

$this_monday = date("d/m/Y", strtotime("Monday this week"));
$this_sunday = date("d/m/Y", strtotime("Sunday this week"));
?>

<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jedálny lístok</title>
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
                    <a class="col-12 col-md-4 py-3 nav-button-active d-flex justify-content-center" href="#">Jedálny lístok</a>
                    <a class="col-12 col-md-4 py-3 d-flex justify-content-center" href="api_verify.php">Overenie API</a>
                    <a class="col-12 col-md-4 py-3 d-flex justify-content-center" href="api_description.php">Popis API</a>
                </div>
            </div>
        </div>

        <div class="page-content p-3">
            <h2 class="pb-3">Jedálny lístok <?php echo "({$this_monday} - {$this_sunday})"?></h2>
            <form action="" method="get">
                <div class="row mb-3">
                    <div class="col-6">
                        <select class="form-select" name="day_filter">
                            <option value="all">Celý týždeň</option>
                            <option <?php if ($day_filter == "Monday") {echo "selected";} ?> value="Monday">Pondelok</option>
                            <option <?php if ($day_filter == "Tuesday") {echo "selected";} ?> value="Tuesday">Utorok</option>
                            <option <?php if ($day_filter == "Wednesday") {echo "selected";} ?> value="Wednesday">Streda</option>
                            <option <?php if ($day_filter == "Thursday") {echo "selected";} ?> value="Thursday">Štvrtok</option>
                            <option <?php if ($day_filter == "Friday") {echo "selected";} ?> value="Friday">Piatok</option>
                        </select>
                    </div>
                    <div class="col-6 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrovať</button>
                    </div>
                </div>
            </form>

            <?php if (empty($response)): ?>
            <p>
                V databáze nie sú žiadne dáta, prosím prejdite na podstránku Overenie API. 
                Tam stlačte tlačidlá Stiahni a Rozparsuj a prejdite opäť na podstránku Jedálny lístok.
            </p>;
            <?php else: ?>
            <div class="row">
                <?php foreach ($grouped_data as $restaurant_id => $restaurant_data): 
                    $null_days_data = array();
                ?>
                <div class="col-12 col-sm-4">
                    <h3 class="text-center mb-2"><?php echo $restaurant_names[$restaurant_id]; ?></h3>
                    <?php foreach ($restaurant_data as $day => $day_data): ?>
                        <?php if ($day != null): ?>
                        <div class="mb-3 pb-3">
                            <h4><?php echo $day; ?></h4>
                            <div>
                            <?php foreach ($day_data as $item): ?>
                                <div class="mb-2 pb-2 border-bottom">
                                    <h5><?php echo $item['description']; ?></h5>
                                    <div class="row">
                                        <div class="col-9"><?php echo $item['name']; ?></div>
                                        <div class="col-3"><?php echo $item['price']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: 
                            $null_days_data[] = $day_data;
                        ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!empty($null_days_data)): ?>
                    <div>
                        <h4>Mimo dennej ponuky</h4>
                        <div>
                        <?php foreach ($null_days_data as $data): ?>
                            <?php foreach ($data as $item): ?>
                            <div class="mb-2 pb-2 border-bottom">
                                <h5><?php echo $item['description']; ?></h5>
                                <div class="row">
                                    <div class="col-9"><?php echo $item['name']; ?></div>
                                    <div class="col-3"><?php echo $item['price']; ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>