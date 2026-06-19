<?php
require_once 'config.php';
requireLogin();

$success = '';
$estimate_result = null;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM estimates WHERE id=:id")->execute([':id' => $id]);
    $success = "Estimate deleted.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label     = trim($_POST['label'] ?? 'My Estimate');
    $ec2_type  = $_POST['ec2_type'] ?? 't2.micro';
    $ec2_hours = max(0, (int)($_POST['ec2_hours'] ?? 730));
    $rds_tier  = $_POST['rds_tier'] ?? 'none';
    $s3_gb     = max(0, (float)($_POST['s3_gb'] ?? 0));
    $data_gb   = max(0, (float)($_POST['data_gb'] ?? 0));

    $ep = ['t2.micro'=>0.0136,'t2.small'=>0.0272,'t2.medium'=>0.0544,'t3.micro'=>0.0124,'t3.small'=>0.0248,'t3.medium'=>0.0496,'t3.large'=>0.0992,'m5.large'=>0.1120,'m5.xlarge'=>0.2240];
    $rp = ['none'=>0,'db.t3.micro'=>28.08,'db.t3.small'=>56.16,'db.t3.medium'=>112.32,'db.m5.large'=>224.64];

    $ec2_cost  = round(($ep[$ec2_type] ?? 0.0136) * $ec2_hours, 2);
    $rds_cost  = $rp[$rds_tier] ?? 0;
    $s3_cost   = round($s3_gb * 0.023, 2);
    $data_cost = round($data_gb * 0.09, 2);
    $total     = round($ec2_cost + $rds_cost + $s3_cost + $data_cost, 2);

    $stmt = $pdo->prepare("INSERT INTO estimates (label, ec2_type, rds_tier, s3_gb, monthly_cost, created_at) VALUES (:l, :e, :r, :s, :t, NOW())");
    $stmt->execute([':l'=>$label,':e'=>$ec2_type,':r'=>$rds_tier,':s'=>$s3_gb,':t'=>$total]);

    $estimate_result = compact('label','ec2_type','ec2_cost','rds_tier','rds_cost','s3_gb','s3_cost','data_gb','data_cost','total');
    $success = "Estimate saved.";
}

$history = $pdo->query("SELECT * FROM estimates ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cost Estimator — ApexOps Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --blue: #185FA5; --blue-dark: #0C447C; --blue-light: #E6F1FB; --text: #1a1a1a; --muted: #6b7280; --border: #e5e7eb; --bg: #f9fafb; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); }
    .layout { display: flex; min-height: 100vh; }
    .sidebar { width: 220px; background: #fff; border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; }
    .sidebar-logo { padding: 22px 20px 18px; border-bottom: 1px solid var(--border); font-size: 17px; font-weight: 600; }
    .sidebar-logo span { color: var(--blue); }
    .sidebar-label { font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: 0.8px; text-transform: uppercase; padding: 20px 20px 8px; }
    .sidebar-nav { flex: 1; padding: 8px 12px; }
    .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; font-size: 14px; color: var(--muted); text-decoration: none; transition: all 0.15s; margin-bottom: 2px; }
    .nav-item:hover { background: var(--bg); color: var(--text); }
    .nav-item.active { background: var(--blue-light); color: var(--blue); font-weight: 500; }
    .nav-item .icon { font-size: 16px; width: 20px; text-align: center; }
    .sidebar-footer { padding: 16px 20px; border-top: 1px solid var(--border); font-size: 13px; color: var(--muted); }
    .sidebar-footer a { color: #dc2626; text-decoration: none; }
    .main { margin-left: 220px; flex: 1; padding: 32px 40px; }
    .page-header { margin-bottom: 28px; }
    .page-header h1 { font-size: 22px; font-weight: 600; margin-bottom: 4px; }
    .page-header p { font-size: 14px; color: var(--muted); }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .panel { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 28px; }
    .panel-title { font-size: 16px; font-weight: 600; margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
    .form-hint { font-size: 12px; color: var(--muted); margin-bottom: 6px; }
    input[type="text"], input[type="number"], select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; background: #fff; }
    input:focus, select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(24,95,165,0.1); }
    .section-divider { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.6px; margin: 20px 0 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
    .btn { width: 100%; background: var(--blue); color: #fff; padding: 12px; border-radius: 8px; font-size: 15px; font-weight: 500; border: none; cursor: pointer; font-family: inherit; margin-top: 8px; }
    .btn:hover { background: var(--blue-dark); }
    .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .result-panel { background: var(--blue); border-radius: 12px; padding: 28px; color: #fff; margin-bottom: 24px; }
    .result-label { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
    .result-total { font-size: 42px; font-weight: 600; margin-bottom: 4px; }
    .result-sub { font-size: 13px; opacity: 0.8; margin-bottom: 20px; }
    .result-breakdown { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .result-item { background: rgba(255,255,255,0.12); border-radius: 8px; padding: 12px; }
    .result-item-label { font-size: 11px; opacity: 0.8; margin-bottom: 4px; }
    .result-item-value { font-size: 16px; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 12px; font-weight: 600; color: var(--muted); text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); background: var(--bg); text-transform: uppercase; }
    td { font-size: 13px; padding: 12px; border-bottom: 1px solid var(--border); }
    tr:last-child td { border-bottom: none; }
    .del-btn { font-size: 12px; color: #dc2626; text-decoration: none; }
    .empty { text-align: center; padding: 32px; color: var(--muted); font-size: 14px; }
    @media (max-width: 900px) { .sidebar { display: none; } .main { margin-left: 0; padding: 20px; } .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Apex<span>Ops</span></div>
    <div class="sidebar-label">Portal</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
      <a href="incidents.php" class="nav-item"><span class="icon">🚨</span> Incidents</a>
      <a href="estimator.php" class="nav-item active"><span class="icon">💰</span> Cost Estimator</a>
    </nav>
    <div class="sidebar-footer">
      <div style="margin-bottom:6px">Signed in as <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
      <a href="logout.php">Sign out</a>
    </div>
  </aside>
  <main class="main">
    <div class="page-header"><h1>Cloud Cost Estimator</h1><p>Estimate your monthly AWS costs for af-south-1 (Cape Town region).</p></div>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <div class="grid">
      <div>
        <?php if ($estimate_result): ?>
        <div class="result-panel">
          <div class="result-label">Estimated monthly cost</div>
          <div class="result-total">$<?= number_format($estimate_result['total'], 2) ?></div>
          <div class="result-sub"><?= htmlspecialchars($estimate_result['label']) ?> — af-south-1</div>
          <div class="result-breakdown">
            <div class="result-item"><div class="result-item-label">EC2 (<?= $estimate_result['ec2_type'] ?>)</div><div class="result-item-value">$<?= number_format($estimate_result['ec2_cost'], 2) ?></div></div>
            <div class="result-item"><div class="result-item-label">RDS</div><div class="result-item-value">$<?= number_format($estimate_result['rds_cost'], 2) ?></div></div>
            <div class="result-item"><div class="result-item-label">S3 (<?= $estimate_result['s3_gb'] ?>GB)</div><div class="result-item-value">$<?= number_format($estimate_result['s3_cost'], 2) ?></div></div>
            <div class="result-item"><div class="result-item-label">Data transfer</div><div class="result-item-value">$<?= number_format($estimate_result['data_cost'], 2) ?></div></div>
          </div>
        </div>
        <?php endif; ?>
        <div class="panel">
          <div class="panel-title">Configure your estimate</div>
          <form method="POST" action="estimator.php">
            <div class="form-group"><label>Estimate name</label><input type="text" name="label" value="My Estimate" placeholder="e.g. Production setup"></div>
            <div class="section-divider">EC2 — Compute</div>
            <div class="form-group"><label>Instance type</label>
              <select name="ec2_type">
                <option value="t2.micro">t2.micro — Free tier eligible</option>
                <option value="t2.small">t2.small</option><option value="t2.medium">t2.medium</option>
                <option value="t3.micro">t3.micro</option><option value="t3.small">t3.small</option>
                <option value="t3.medium">t3.medium</option><option value="t3.large">t3.large</option>
                <option value="m5.large">m5.large</option><option value="m5.xlarge">m5.xlarge</option>
              </select>
            </div>
            <div class="form-group"><label>Hours per month</label><div class="form-hint">730 = 24/7 · 160 = business hours only</div><input type="number" name="ec2_hours" value="730" min="0" max="744"></div>
            <div class="section-divider">RDS — Database</div>
            <div class="form-group"><label>RDS instance tier</label>
              <select name="rds_tier">
                <option value="none">None</option><option value="db.t3.micro">db.t3.micro</option>
                <option value="db.t3.small">db.t3.small</option><option value="db.t3.medium">db.t3.medium</option>
                <option value="db.m5.large">db.m5.large</option>
              </select>
            </div>
            <div class="section-divider">S3 & Data Transfer</div>
            <div class="form-group"><label>S3 storage (GB)</label><input type="number" name="s3_gb" value="0" min="0"></div>
            <div class="form-group"><label>Data transfer out (GB/month)</label><input type="number" name="data_gb" value="0" min="0"></div>
            <button type="submit" class="btn">Calculate & save →</button>
          </form>
        </div>
      </div>
      <div class="panel" style="align-self:start">
        <div class="panel-title">Saved estimates</div>
        <?php if (count($history) > 0): ?>
        <table>
          <thead><tr><th>Name</th><th>EC2</th><th>Total/mo</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($history as $row): ?>
            <tr>
              <td><strong><?= htmlspecialchars($row['label']) ?></strong></td>
              <td style="color:var(--muted)"><?= htmlspecialchars($row['ec2_type']) ?></td>
              <td style="font-weight:600;color:var(--blue)">$<?= number_format($row['monthly_cost'], 2) ?></td>
              <td style="color:var(--muted)"><?= date('M j', strtotime($row['created_at'])) ?></td>
              <td><a href="?delete=<?= $row['id'] ?>" class="del-btn" onclick="return confirm('Delete?')">Delete</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?><div class="empty">No estimates yet.</div><?php endif; ?>
      </div>
    </div>
  </main>
</div>
</body>
</html>
