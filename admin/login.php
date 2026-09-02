<?php
session_start();
require_once __DIR__ . '/../db-connect.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Set session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_id'] = $admin['id'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    } else {
        $error = 'Silakan isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Alfaruq Donate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2B7FE4;
            --primary-dark: #1E62B5;
            --background: #F9FAFB;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --error: #EF4444;
            --card-bg: #FFFFFF;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Rubik', sans-serif;
            background-color: var(--background);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            border: 1px solid #E5E7EB;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-area h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .logo-area p {
            font-size: 14px;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43, 127, 228, 0.15);
        }

        .error-message {
            background-color: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: var(--error);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: inherit;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
        }

        .footer-note {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: var(--text-light);
        }

        .footer-note a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-area">
            <h1>Alfaruq Donate</h1>
            <p>Masuk ke Panel Dashboard Admin</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>
        </form>

        <div class="footer-note">
            <a href="../index.html">← Kembali ke beranda utama</a>
        </div>
    </div>
</body>
</html>
