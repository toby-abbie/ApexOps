<?php
require_once 'config.php';
requireLogin();

$name = $_SESSION['user_name'];

$total_incidents = $pdo->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
$open_incidents  = $pdo->query("SELECT COUNT(*) FROM incidents WHERE status='open'")->fetchColumn();
$critical        = $pdo->query("SELECT COUNT(*) FROM incidents WHERE severity='critical' AND status='open'")->fetchColumn();
$total_estimates = $pdo->query("SELECT COUNT(*) FROM estimates")->fetchColumn();
$recent          = $pdo->query("SELECT * FROM incidents ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — ApexOps Portal</title>
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
    .page-header { margin-bottom: 32px; }
    .page-header h1 { font-size: 22px; font-weight: 600; margin-bottom: 4px; }
    .page-header p { font-size: 14px; color: var(--muted); }
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
    .stat-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
    .stat-card-label { font-size: 12px; color: var(--muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .stat-card-value { font-size: 28px; font-weight: 600; }
    .stat-card.critical .stat-card-value { color: #dc2626; }
    .stat-card.blue .stat-card-value { color: var(--blue); }
    .dash-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .panel { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
    .panel-title { font-size: 15px; font-weight: 600; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }
    .panel-title a { font-size: 13px; font-weight: 400; color: var(--blue); text-decoration: none; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 12px; font-weight: 500; color: var(--muted); text-align: left; padding: 0 12px 10px 0; border-bottom: 1px solid var(--border); }
    td { font-size: 13px; padding: 12px 12px 12px 0; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-open { background: #fef3c7; color: #92400e; }
    .badge-resolved { background: #d1fae5; color: #065f46; }
    .badge-critical { background: #fee2e2; color: #991b1b; }
    .badge-high { background: #ffedd5; color: #9a3412; }
    .badge-medium { background: #fef9c3; color: #713f12; }
    .badge-low { background: #f0fdf4; color: #166534; }
    .quick-links { display: flex; flex-direction: column; gap: 10px; }
    .quick-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px; text-decoration: none; color: var(--text); transition: all 0.15s; }
    .quick-link:hover { border-color: var(--blue); background: var(--blue-light); }
    .quick-link-icon { font-size: 22px; width: 36px; text-align: center; }
    .quick-link-text strong { display: block; font-size: 14px; font-weight: 500; margin-bottom: 2px; }
    .quick-link-text span { font-size: 12px; color: var(--muted); }
    .empty { text-align: center; padding: 32px; color: var(--muted); font-size: 14px; }
    @media (max-width: 900px) { .sidebar { display: none; } .main { margin-left: 0; padding: 20px; } .stats-row { grid-template-columns: repeat(2,1fr); } .dash-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Apex<span>Ops</span></div>
    <div class="sidebar-label">Portal</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active"><span class="icon">📊</span> Dashboard</a>
      <a href="incidents.php" class="nav-item"><span class="icon">🚨</span> Incidents</a>
      <a href="estimator.php" class="nav-item"><span class="icon">💰</span> Cost Estimator</a>
    </nav>
    <div class="sidebar-footer">
      <div style="margin-bottom:6px">Signed in as <strong><?= htmlspecialchars($name) ?></strong></div>
      <a href="logout.php">Sign out</a>
    </div>
  </aside>
  <main class="main">
    <div class="page-header">
      <h1>Good day, <?= htmlspecialchars(explode(' ', $name)[0]) ?> 👋</h1>
      <p>Here's what's happening across your ApexOps environment.</p>
    </div>
    <div class="stats-row">
      <div class="stat-card"><div class="stat-card-label">Total incidents</div><div class="stat-card-value"><?= $total_incidents ?></div></div>
      <div class="stat-card"><div class="stat-card-label">Open incidents</div><div class="stat-card-value"><?= $open_incidents ?></div></div>
      <div class="stat-card critical"><div class="stat-card-label">Critical & open</div><div class="stat-card-value"><?= $critical ?></div></div>
      <div class="stat-card blue"><div class="stat-card-label">Cost estimates</div><div class="stat-card-value"><?= $total_estimates ?></div></div>
    </div>
    <div class="dash-grid">
      <div class="panel">
        <div class="panel-title">Recent incidents <a href="incidents.php">View all →</a></div>
        <?php if (count($recent) > 0): ?>
        <table>
          <thead><tr><th>Service</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['service']) ?></td>
              <td><span class="badge badge-<?= $row['severity'] ?>"><?= ucfirst($row['severity']) ?></span></td>
              <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
              <td><?= date('M j', strtotime($row['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty">No incidents yet. <a href="incidents.php">Log one →</a></div>
        <?php endif; ?>
      </div>
      <div class="panel">
        <div class="panel-title">Quick actions</div>
        <div class="quick-links">
          <a href="incidents.php" class="quick-link"><div class="quick-link-icon">🚨</div><div class="quick-link-text"><strong>Incident Tracker</strong><span>Log and manage IT incidents</span></div></a>
          <a href="estimator.php" class="quick-link"><div class="quick-link-icon">💰</div><div class="quick-link-text"><strong>Cost Estimator</strong><span>Estimate AWS monthly costs</span></div></a>
          <a href="index.html" class="quick-link"><div class="quick-link-icon">🌐</div><div class="quick-link-text"><strong>Public Website</strong><span>Back to ApexOps.io</span></div></a>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
