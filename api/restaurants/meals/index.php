<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once('../../../config.php');

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['date']))
            read_meals_by_date($pdo, $_GET['date']);
        else
            read_meals($pdo);
        break;
    case 'POST':
        create_meal($pdo, json_decode(file_get_contents('php://input'), true));
        break;
    case 'PUT':
        if (isset($_GET['id']))
            update_meal($pdo, $_GET['id'], json_decode(file_get_contents('php://input'), true));
        break;
    case 'DELETE':
        $id = $_GET['id'];
        // delete_games($db, $id);
        break;
}

function read_meals($pdo) {
    $this_monday = date("Y-m-d", strtotime("Monday this week"));
    $this_sunday = date("Y-m-d", strtotime("Sunday this week"));
    $sql = "SELECT * FROM menu_item WHERE start_date >= ? AND end_date <= ? ORDER BY restaurant_id, start_date, description ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$this_monday, $this_sunday]);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($meals);
}

function read_meals_by_date($pdo, $date) {
    $sql = "SELECT * FROM menu_item WHERE ? BETWEEN start_date AND end_date";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($meals);
}

function create_meal($pdo, $data) {
    if (!array_keys_exist(['name', 'restaurant_id', 'description', 'price', 'day', 'start_date', 'end_date'], $data)) {
        echo json_encode(array('error' => 'Create failed, missing parameters'));
        http_response_code(400);
        return;
    }

    $sql = "SELECT id FROM restaurant WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['restaurant_id']]);
    if (is_empty($stmt->fetchColumn())) {
        echo json_encode(array('error' => 'Create failed, incorrect restaurant_id'));
        http_response_code(400);
        return;
    }

    $sql = "SELECT id FROM menu_item WHERE name = ? AND restaurant_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['name'], $data['restaurant_id']]);
    if (!is_empty($stmt->fetchColumn())) {
        echo json_encode(array('error' => 'Create failed, duplicate name and restaurant_id values'));
        http_response_code(400);
        return;
    }

    list($start_date, $end_date) = check_date_range($data['start_date'], $data['end_date']);
    if ($start_date == null) {
        echo json_encode(array('error' => 'Create failed, incorrect date range'));
        http_response_code(400);
        return;
    }
    else {
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
    }

    $sql = "INSERT INTO menu_item (name, restaurant_id, description, price, day, start_date, end_date) VALUES (:name, :restaurant_id, :description, :price, :day, :start_date, :end_date)";
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute([
        ":name" => $data["name"],
        ":restaurant_id" => $data['restaurant_id'],
        ":description" => $data["description"],
        ":price" => $data["price"],
        ":day" => $data['day'],
        ":start_date" => $data['start_date'],
        ":end_date" => $data['end_date']
    ])) {
        echo json_encode(array('error' => 'Create failed'));
        http_response_code(400);
        return;
    }
    echo json_encode(array('success' => 'Data created successfully'));
}

function update_meal($pdo, $id, $data) {
    $sql = "SELECT * FROM menu_item WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $meal = $stmt->fetch();

    foreach ($meal as $key => $value)
        if (array_key_exists($key, $data))
            $meal[$key] = $data[$key];

    list($start_date, $end_date) = check_date_range($meal['start_date'], $meal['end_date']);
    if ($start_date == null) {
        echo json_encode(array('error' => 'Update failed, incorrect date range'));
        http_response_code(400);
        return;
    }
    else {
        $meal['start_date'] = $start_date;
        $meal['end_date'] = $end_date;
    }

    $sql = "UPDATE menu_item SET name = :name, restaurant_id = :restaurant_id, description = :description, price = :price, day = :day, start_date = :start_date, end_date = :end_date WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute([
        ":name" => $meal["name"],
        ":restaurant_id" => $meal['restaurant_id'],
        ":description" => $meal["description"],
        ":price" => $meal["price"],
        ":day" => $meal['day'],
        ":start_date" => $meal['start_date'],
        ":end_date" => $meal['end_date'],
        ":id" => $id
    ]))
        echo json_encode(array('error' => 'Update failed'));
    echo json_encode(array('success' => 'Data updated successfully'));
}

function delete_games($db, $id) {
    if(!is_empty($id)) {
        echo json_encode(array('error' => 'Delete failed'));
        http_response_code(400);
        return;
    } else {
        $stmt = $db->prepare('DELETE FROM games WHERE id = :id');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        echo json_encode(array('success' => 'Data deleted successfully'));
    }
}

function array_keys_exist($keys, $array){
    foreach($keys as $key)
        if(!array_key_exists($key, $array))
            return false;
    return true;
}

function is_empty($param) {
    if(empty($param))
        return true;
    else
        return false;
}

function check_date_range($start_date, $end_date) {
    $start_date = date("Y-m-d", strtotime($start_date));
    $end_date = date("Y-m-d", strtotime($end_date));

    if ($start_date > $end_date)
        return null;
    else
        return array($start_date, $end_date);
}
?>