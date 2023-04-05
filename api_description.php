<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$json_example = json_encode(json_decode(
    '{"name": "Hlivový perkelt (1,7)",
    "restaurant_id": 1,
    "description": "Vegetariánske",
    "price": "3,80 €",
    "start_date": "2023-01-01",
    "end": "2023-01-01"}'
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popis API</title>
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
                    <a class="col-12 col-md-4 py-3 d-flex justify-content-center" href="index.php">Jedálny lístok</a>
                    <a class="col-12 col-md-4 py-3 d-flex justify-content-center" href="api_verify.php">Overenie API</a>
                    <a class="col-12 col-md-4 py-3 nav-button-active d-flex justify-content-center" href="#">Popis API</a>
                </div>
            </div>
        </div>

        <div class="page-content p-3">
            <h2 class="pb-3">Popis metód API</h2>
            <div class="row">
                <div class="col-12 mb-3"">
                    <span class="badge text-bg-primary fs-5 me-3">GET</span><span class="fw-bold me-3">/api.php</span>Vráti zoznam jedál spolu s dostupnými cenami pre aktuálny týždeň a všetky reštaurácie
                </div>

                <div class="col-12 mb-3">
                    <span class="badge text-bg-primary fs-5 me-3">GET</span><span class="fw-bold me-3">/api.php?date={YYYY-MM-DD}</span>Vráti zoznam jedál spolu s dostupnými cenami pre zadaný dátum
                </div>

                <div class="col-12 mb-3" data-bs-toggle="collapse" data-bs-target="#collapse-post">
                    <span class="badge text-bg-success fs-5 me-3">POST</span><span class="fw-bold me-3">/api.php</span>Pridá nové jedlo do ponuky reštaurácie
                    <div class="collapse mt-2" id="collapse-post">
                        <div class="card card-body">
                            <p>Príklad request body:</p>
                            <code>
                                <pre><?php echo $json_example; ?></pre>
                            </code>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-3" data-bs-toggle="collapse" data-bs-target="#collapse-put">
                    <span class="badge text-bg-warning fs-5 me-3">PUT</span><span class="fw-bold me-3">/api.php?id={id}</span>Modifikuje jedlo, podľa zadaného ID jedla
                    <div class="collapse mt-2" id="collapse-put">
                        <div class="card card-body">
                            <p>Príklad request body:</p>
                            <code>
                                <pre><?php echo $json_example; ?></pre>
                            </code>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <span class="badge text-bg-danger fs-5 me-3">DELETE</span><span class="fw-bold me-3">/api.php?id={id}</span>Vymaže celú ponuku reštaurácie, podľa zadaného ID reštaurácie
                </div>
            </div>
        </div>
        
    </div>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>