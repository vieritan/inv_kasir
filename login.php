<?php
// login.php - Halaman Login Sistem Inventory
session_start();

// If already logged in, redirect to index.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "ID User dan Password wajib diisi!";
    } else {
        $users = loadData('users.json');
        $foundUser = null;

        if (is_array($users)) {
            foreach ($users as $u) {
                if (strtolower(trim($u['username'] ?? '')) === strtolower($username)) {
                    $foundUser = $u;
                    break;
                }
            }
        }

        $userPass = trim($foundUser['password'] ?? '');

        // Accept @admin123 or admin123 if user is admin
        $isPasswordCorrect = ($foundUser && ($userPass === $password || (strtolower($username) === 'admin' && ($password === '@admin123' || $password === 'admin123'))));

        if ($isPasswordCorrect) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username']  = $foundUser['username'] ?? 'admin';
            $_SESSION['user_name'] = $foundUser['name'] ?? 'Administrator';
            $_SESSION['role']      = $foundUser['role'] ?? 'admin';

            header("Location: index.php");
            exit;
        } else {
            $error = "ID User atau Password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #006600;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: Georgia, "Times New Roman", Times, serif;
        }
        .login-box {
            background: #ffffff;
            color: #333333;
            padding: 35px 30px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 380px;
            border-top: 5px solid #004d00;
        }
        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-header h2 {
            margin: 0 0 6px 0;
            color: #006600;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .login-header p {
            margin: 0;
            color: #666666;
            font-size: 0.85rem;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
            font-size: 0.85rem;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #006600;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 102, 0, 0.3);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #006600;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-login:hover {
            background-color: #004d00;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 18px;
            font-size: 0.85rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <h2>SISTEM INVENTORY</h2>
            <p>Silakan login dengan ID dan Password Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">ID USER / USERNAME:</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="Masukkan ID User..." required autofocus>
            </div>

            <div class="form-group">
                <label for="password">PASSWORD:</label>
                <input type="password" id="password" name="password" placeholder="Masukkan Password..." required>
            </div>

            <button type="submit" class="btn-login">LOGIN MASUK</button>
        </form>
    </div>
</body>
</html>
