<?php
// ===============================
// api.php - API REST principale
// ===============================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['request']) ? $_GET['request'] : '';

switch($request) {
    // VISITEURS
    case 'visiteurs':
        if($method == 'GET') {
            getVisiteurs($db);
        } elseif($method == 'POST') {
            addVisiteur($db);
        }
        break;
    
    case 'visiteur':
        if($method == 'GET') {
            getVisiteur($db);
        } elseif($method == 'PUT') {
            updateVisiteur($db);
        } elseif($method == 'DELETE') {
            deleteVisiteur($db);
        }
        break;
    
    // UTILISATEURS
    case 'users':
        if($method == 'GET') {
            getUsers($db);
        } elseif($method == 'POST') {
            addUser($db);
        }
        break;
    
    case 'user':
        if($method == 'GET') {
            getUser($db);
        } elseif($method == 'PUT') {
            updateUser($db);
        } elseif($method == 'DELETE') {
            deleteUser($db);
        }
        break;
    
    // OBSERVATIONS
    case 'observations':
        if($method == 'GET') {
            getObservations($db);
        } elseif($method == 'POST') {
            addObservation($db);
        }
        break;
    
    case 'observation':
        if($method == 'DELETE') {
            deleteObservation($db);
        }
        break;
    
    // STATISTIQUES
    case 'stats':
        getStats($db);
        break;
    
    // LOGIN
    case 'login':
        login($db);
        break;
    
    default:
        echo json_encode(["message" => "Endpoint non trouvé"]);
}

// ===============================
// FONCTIONS VISITEURS
// ===============================
function getVisiteurs($db) {
    $query = "SELECT v.*, u.username 
              FROM Visiteur v 
              LEFT JOIN Utilisateur u ON v.id_user = u.id_user 
              ORDER BY v.date_visite DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $visiteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($visiteurs);
}

function getVisiteur($db) {
    $id = isset($_GET['id']) ? $_GET['id'] : die();
    
    $query = "SELECT * FROM Visiteur WHERE id_visiteur = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $visiteur = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($visiteur);
}

function addVisiteur($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->nom) && !empty($data->prenom) && !empty($data->cin)) {
        $query = "INSERT INTO Visiteur 
                  (nom, prenom, CIN, type_visiteur, but_visite, date_visite, id_user) 
                  VALUES 
                  (:nom, :prenom, :cin, :type, :but, NOW(), :id_user)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':nom', $data->nom);
        $stmt->bindParam(':prenom', $data->prenom);
        $stmt->bindParam(':cin', $data->cin);
        $stmt->bindParam(':type', $data->type_visiteur);
        $stmt->bindParam(':but', $data->but_visite);
        $stmt->bindParam(':id_user', $data->id_user);
        
        if($stmt->execute()) {
            echo json_encode([
                "message" => "Visiteur ajouté avec succès",
                "id" => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(["message" => "Erreur lors de l'ajout"]);
        }
    } else {
        echo json_encode(["message" => "Données incomplètes"]);
    }
}

function updateVisiteur($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    $query = "UPDATE Visiteur 
              SET nom = :nom, 
                  prenom = :prenom, 
                  CIN = :cin, 
                  type_visiteur = :type, 
                  but_visite = :but 
              WHERE id_visiteur = :id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':nom', $data->nom);
    $stmt->bindParam(':prenom', $data->prenom);
    $stmt->bindParam(':cin', $data->cin);
    $stmt->bindParam(':type', $data->type_visiteur);
    $stmt->bindParam(':but', $data->but_visite);
    $stmt->bindParam(':id', $data->id);
    
    if($stmt->execute()) {
        echo json_encode(["message" => "Visiteur mis à jour"]);
    } else {
        echo json_encode(["message" => "Erreur lors de la mise à jour"]);
    }
}

function deleteVisiteur($db) {
    $id = isset($_GET['id']) ? $_GET['id'] : die();
    
    $query = "DELETE FROM Visiteur WHERE id_visiteur = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        echo json_encode(["message" => "Visiteur supprimé"]);
    } else {
        echo json_encode(["message" => "Erreur lors de la suppression"]);
    }
}

// ===============================
// FONCTIONS UTILISATEURS
// ===============================
function getUsers($db) {
    $query = "SELECT id_user, username, role, heure_debut, heure_fin FROM Utilisateur";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
}

function getUser($db) {
    $id = isset($_GET['id']) ? $_GET['id'] : die();
    
    $query = "SELECT id_user, username, role, heure_debut, heure_fin 
              FROM Utilisateur WHERE id_user = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($user);
}

function addUser($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->username) && !empty($data->password)) {
        // Hash du mot de passe
        $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO Utilisateur 
                  (username, password, role, heure_debut, heure_fin) 
                  VALUES 
                  (:username, :password, :role, :debut, :fin)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':username', $data->username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $data->role);
        $stmt->bindParam(':debut', $data->heure_debut);
        $stmt->bindParam(':fin', $data->heure_fin);
        
        if($stmt->execute()) {
            echo json_encode([
                "message" => "Utilisateur ajouté avec succès",
                "id" => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(["message" => "Erreur lors de l'ajout"]);
        }
    } else {
        echo json_encode(["message" => "Données incomplètes"]);
    }
}

function updateUser($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    $query = "UPDATE Utilisateur 
              SET username = :username, 
                  role = :role, 
                  heure_debut = :debut, 
                  heure_fin = :fin";
    
    // Si un nouveau mot de passe est fourni
    if(!empty($data->password)) {
        $query .= ", password = :password";
    }
    
    $query .= " WHERE id_user = :id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':username', $data->username);
    $stmt->bindParam(':role', $data->role);
    $stmt->bindParam(':debut', $data->heure_debut);
    $stmt->bindParam(':fin', $data->heure_fin);
    $stmt->bindParam(':id', $data->id);
    
    if(!empty($data->password)) {
        $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $hashed_password);
    }
    
    if($stmt->execute()) {
        echo json_encode(["message" => "Utilisateur mis à jour"]);
    } else {
        echo json_encode(["message" => "Erreur lors de la mise à jour"]);
    }
}

function deleteUser($db) {
    $id = isset($_GET['id']) ? $_GET['id'] : die();
    
    $query = "DELETE FROM Utilisateur WHERE id_user = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        echo json_encode(["message" => "Utilisateur supprimé"]);
    } else {
        echo json_encode(["message" => "Erreur lors de la suppression"]);
    }
}

// ===============================
// FONCTIONS OBSERVATIONS
// ===============================
function getObservations($db) {
    $query = "SELECT o.*, u.username 
              FROM Observation o 
              LEFT JOIN Utilisateur u ON o.id_user = u.id_user 
              ORDER BY o.date_obs DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $observations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($observations);
}

function addObservation($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->id_user) && !empty($data->description)) {
        $query = "INSERT INTO Observation 
                  (id_user, date_obs, description) 
                  VALUES 
                  (:id_user, NOW(), :description)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id_user', $data->id_user);
        $stmt->bindParam(':description', $data->description);
        
        if($stmt->execute()) {
            echo json_encode([
                "message" => "Observation ajoutée avec succès",
                "id" => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(["message" => "Erreur lors de l'ajout"]);
        }
    } else {
        echo json_encode(["message" => "Données incomplètes"]);
    }
}

function deleteObservation($db) {
    $id = isset($_GET['id']) ? $_GET['id'] : die();
    
    $query = "DELETE FROM Observation WHERE id_obs = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        echo json_encode(["message" => "Observation supprimée"]);
    } else {
        echo json_encode(["message" => "Erreur lors de la suppression"]);
    }
}

// ===============================
// STATISTIQUES
// ===============================
function getStats($db) {
    $stats = [];
    
    // Total visiteurs
    $query = "SELECT COUNT(*) as total FROM Visiteur";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_visiteurs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Visiteurs aujourd'hui
    $query = "SELECT COUNT(*) as total FROM Visiteur WHERE DATE(date_visite) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['visiteurs_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total utilisateurs
    $query = "SELECT COUNT(*) as total FROM Utilisateur";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total observations
    $query = "SELECT COUNT(*) as total FROM Observation";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_obs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Visiteurs par type
    $query = "SELECT type_visiteur, COUNT(*) as count 
              FROM Visiteur 
              GROUP BY type_visiteur";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['visiteurs_par_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($stats);
}

// ===============================
// AUTHENTIFICATION
// ===============================
function login($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if(!empty($data->username) && !empty($data->password)) {
        $query = "SELECT * FROM Utilisateur WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $data->username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($data->password, $user['password'])) {
                // Session ou JWT ici
                session_start();
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                echo json_encode([
                    "message" => "Connexion réussie",
                    "user" => [
                        "id" => $user['id_user'],
                        "username" => $user['username'],
                        "role" => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(["message" => "Mot de passe incorrect"]);
            }
        } else {
            echo json_encode(["message" => "Utilisateur non trouvé"]);
        }
    } else {
        echo json_encode(["message" => "Données incomplètes"]);
    }
}
?>