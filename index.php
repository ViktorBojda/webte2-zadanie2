<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = file_get_contents('https://site60.webte.fei.stuba.sk/webte2-zadanie2/api/restaurants/meals/');
$response = json_decode($response, true);

$grouped_data = array();
foreach ($response as $item) {
    if ($item['day'] !== null) {
        $grouped_data[$item['restaurant_id']][$item['day']][] = $item;
    } else {
        $grouped_data[$item['restaurant_id']][null][] = $item;
    }
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
                    <a class="col-12 col-md-6 py-3 nav-button-active d-flex justify-content-center" href="#">Jedálny lístok</a>
                    <a class="col-12 col-md-6 py-3 d-flex justify-content-center" href="api_verify.php">Overenie API</a>
                </div>
            </div>
        </div>

        <div class="page-content p-3">
            <h2 class="pb-3">Jedálny lístok <?php echo "({$this_monday} - {$this_sunday})"?></h2>
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
                <div class="col-4">
                    <h3 class="text-center"><?php echo $restaurant_id; ?></h3>
                    <?php foreach ($restaurant_data as $day => $day_data): ?>
                        <?php if ($day != null): ?>
                        <div>
                            <h4><?php echo $day; ?></h4>
                            <div>
                            <?php foreach ($day_data as $item): ?>
                                <h5><?php echo $item['description']; ?></h5>
                                <p><?php echo $item['name']; ?></p>
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
                        <h4>Mimo od dennej ponuky</h4>
                        <div>
                        <?php foreach ($null_days_data as $data): ?>
                            <?php foreach ($data as $item): ?>
                            <h5><?php echo $item['description']; ?></h5>
                            <p><?php echo $item['name']; ?></p>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
                    
                <!-- foreach ($grouped_data as $restaurant_id => $restaurant_data) {
                echo '<h2>Restaurant ID: ' . $restaurant_id . '</h2>';
                echo '<table>';
                echo '<tr><th>Day</th><th>Name</th><th>Description</th><th>Price</th></tr>';
                foreach ($restaurant_data as $day => $day_data) {
                    if ($day !== null) {
                        echo '<tr><td colspan="4"><strong>' . $day . '</strong></td></tr>';
                    }
                    $displayed_days = array();
                    foreach ($day_data as $item) {
                        if ($day === null || !in_array($day, $displayed_days)) {
                            echo '<tr>';
                            echo '<td>' . ($day === null ? 'Null' : $day) . '</td>';
                            echo '<td>' . $item['name'] . '</td>'; -->

            <!-- <div class='row'>
                <div class='col-4'>
                    <h3 class="text-center">FIITFOOD</h3>
                    <div>
                        <h4>Monday</h4>
                        <div>
                            <h5>Polievka</h5>
                            <p>Gulasova 5e</p>
                        </div>
                    </div>
                </div>
                <div class='col-4'>
                    <h3 class="text-center">Venza</h3>
                </div>
                <div class='col-4'>
                    <h3 class="text-center">Eat & Meet</h3>
                </div>
            </div> -->
            <?php endif; ?>
        </div>
        
    </div>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>