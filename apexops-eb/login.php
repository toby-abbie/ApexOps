<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            header("Location: dashboard.php");
            exit;
        }
        $error = "Invalid email or password.";
    } else {
        $error = "Please enter your email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ApexOps Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --blue: #185FA5; --blue-dark: #0C447C; --blue-light: #E6F1FB; --text: #1a1a1a; --muted: #6b7280; --border: #e5e7eb; --bg: #f9fafb; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }
    .top-bar { padding: 18px 48px; border-bottom: 1px solid var(--border); background: #fff; display: flex; justify-content: space-between; align-items: center; }
    .logo { font-size: 17px; font-weight: 600; text-decoration: none; color: var(--text); }
    .logo span { color: var(--blue); }
    .back { font-size: 13px; color: var(--muted); text-decoration: none; }
    .back:hover { color: var(--text); }
    .main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 24px; }
    .card { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 40px; width: 100%; max-width: 420px; }
    .card-eyebrow { font-size: 12px; font-weight: 500; color: var(--blue); letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 8px; }
    .card-title { font-size: 22px; font-weight: 600; margin-bottom: 6px; }
    .card-sub { font-size: 14px; color: var(--muted); margin-bottom: 24px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
    input { width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; background: #fff; }
    input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(24,95,165,0.1); }
    .btn { width: 100%; background: var(--blue); color: #fff; padding: 13px; border-radius: 8px; font-size: 15px; font-weight: 500; border: none; cursor: pointer; font-family: inherit; margin-top: 4px; transition: background 0.2s; }
    .btn:hover { background: var(--blue-dark); }
    .error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 11px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
    .demo-hint { margin-top: 20px; padding: 14px; background: var(--blue-light); border-radius: 8px; font-size: 13px; color: var(--blue-dark); }
    .demo-hint strong { display: block; margin-bottom: 4px; }
    footer { text-align: center; padding: 20px; font-size: 13px; color: var(--muted); border-top: 1px solid var(--border); }
  </style>
</head>
<body>
<div class="top-bar">
  <a href="index.html" class="logo">Apex<span>Ops</span></a>
  <a href="index.html" class="back">← Back to website</a>
</div>
<div class="main">
  <div class="card">
    <p class="card-eyebrow">Client Portal</p>
    <h1 class="card-title">Welcome back</h1>
    <p class="card-sub">Sign in to access your ApexOps dashboard</p>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
      <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" placeholder="you@company.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn">Sign in →</button>
    </form>
    <div class="demo-hint">
      <strong>Demo credentials</strong>
      Email: admin@apexops.io &nbsp;|&nbsp; Password: Admin@123
    </div>
  </div>
</div>
<footer>© 2026 ApexOps Consulting — Secure Client Portal</footer>
</body>
</html>
