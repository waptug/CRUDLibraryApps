<?php
// Simple RESTful API for managing books using SQLite

header('Content-Type: application/json');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/crud.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS books (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, author TEXT)");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$segments = $path === '' ? [] : explode('/', $path);

$resource = array_shift($segments);

if ($resource !== 'books') {
    http_response_code(404);
    echo json_encode(['error' => 'Resource not found']);
    exit;
}

switch ($method) {
    case 'GET':
        if (empty($segments)) {
            listBooks($db);
        } else {
            $id = intval($segments[0]);
            getBook($db, $id);
        }
        break;
    case 'POST':
        createBook($db);
        break;
    case 'PUT':
        if (!empty($segments)) {
            $id = intval($segments[0]);
            updateBook($db, $id);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
        }
        break;
    case 'DELETE':
        if (!empty($segments)) {
            $id = intval($segments[0]);
            deleteBook($db, $id);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function listBooks($db)
{
    $stmt = $db->query('SELECT * FROM books');
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($books);
}

function getBook($db, $id)
{
    $stmt = $db->prepare('SELECT * FROM books WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($book) {
        echo json_encode($book);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
    }
}

function createBook($db)
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['title']) || !isset($data['author'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        return;
    }
    $stmt = $db->prepare('INSERT INTO books (title, author) VALUES (:title, :author)');
    $stmt->execute([
        ':title' => $data['title'],
        ':author' => $data['author']
    ]);
    $id = $db->lastInsertId();
    http_response_code(201);
    echo json_encode(['id' => $id, 'title' => $data['title'], 'author' => $data['author']]);
}

function updateBook($db, $id)
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['title']) || !isset($data['author'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        return;
    }
    $stmt = $db->prepare('UPDATE books SET title = :title, author = :author WHERE id = :id');
    $stmt->execute([
        ':title' => $data['title'],
        ':author' => $data['author'],
        ':id' => $id
    ]);
    if ($stmt->rowCount()) {
        echo json_encode(['id' => $id, 'title' => $data['title'], 'author' => $data['author']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
    }
}

function deleteBook($db, $id)
{
    $stmt = $db->prepare('DELETE FROM books WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount()) {
        echo json_encode(['status' => 'deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
    }
}

