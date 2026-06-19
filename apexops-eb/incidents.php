<?php
require_once 'config.php';
requireLogin();

$success = '';
$error   = '';

if (isset($_GET['resolve'])) {
    $id = (int)$_GET['resolve'];
    $pdo->prepare("UPDATE incidents SET status='resolved', resolved_at=NOW() WHERE id=:id")->execute([':id' => $id]);
    $success = "Incident #$id marked as resolved.";
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM incidents WHERE id=:id")->execute([':id' => $id]);
    $success = "Incident #$id deleted.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service     = trim($_POST['service'] ?? '');
    $severity    = $_POST['severity'] ?? 'medium';
    $description = trim($_POST['description'] ?? '');
    if ($service && $description) {
        $stmt = $pdo->prepare("INSERT INTO incidents (service, severity, description, status, created_at) VALUES (:s, :sev, :d, 'open', NOW())");
        $stmt->execute([':s' => $service, ':sev' => $severity, ':d' => $description]);
        $success = "Incident logged successfully.";
    } else {
        $error = "Please fill in all fields.";
    }
}

$fs = $_GET['status'] ?? 'all';
$fv = $_GET['severity'] ?? 'all';
$where = [];
$params = [];
if ($fs !== 'all') { $where[] = "status = :status"; $params[':status'] = $fs; }
if ($fv !== 'all') { $where[] = "severity = :severity"; $params[':severity'] = $fv; }
$wsql = $where ? "WHERE " . implode(" AND ", $where) : "";
$stmt = $pdo->prepare("SELECT * FROM incidents $wsql ORDER BY created_at DESC");
$stmt->execute($params);
$incidents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Incidents — ApexOps Portal</title>
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
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; }
    .page-header h1 { font-size: 22px; font-weight: 600; margin-bottom: 4px; }
    .page-header p { font-size: 14px; color: var(--muted); }
    .btn { display: inline-block; background: var(--blue); color: #fff; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; font-family: inherit; text-decoration: none; transition: background 0.2s; }
    .btn:hover { background: var(--blue-dark); }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; align-items: center; justify-content: center; padding: 24px; }
    .modal-overlay.open { display: flex; }
    .modal { background: #fff; border-radius: 16px; padding: 32px; width: 100%; max-width: 480px; }
    .modal h2 { font-size: 18px; font-weight: 600; margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
    input[type="text"], select, textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; background: #fff; }
    input:focus, select:focus, textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(24,95,165,0.1); }
    textarea { resize: vertical; min-height: 90px; }
    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
    .btn-ghost { background: transparent; color: var(--muted); padding: 10px 18px; border-radius: 8px; font-size: 14px; border: 1px solid var(--border); cursor: pointer; font-family: inherit; }
    .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .filters label { font-size: 13px; font-weight: 500; }
    .filters select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; background: #fff; cursor: pointer; }
    .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .panel { background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 12px; font-weight: 600; color: var(--muted); text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--border); background: var(--bg); text-transform: uppercase; letter-spacing: 0.4px; }
    td { font-size: 13px; padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafafa; }
    .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-open { background: #fef3c7; color: #92400e; }
    .badge-resolved { background: #d1fae5; color: #065f46; }
    .badge-critical { background: #fee2e2; color: #991b1b; }
    .badge-high { background: #ffedd5; color: #9a3412; }
    .badge-medium { background: #fef9c3; color: #713f12; }
    .badge-low { background: #f0fdf4; color: #166534; }
    .actions { display: flex; gap: 8px; }
    .action-btn { font-size: 12px; font-weight: 500; padding: 5px 10px; border-radius: 6px; text-decoration: none; border: 1px solid var(--border); color: var(--muted); background: #fff; cursor: pointer; font-family: inherit; }
    .action-btn.resolve { color: #065f46; border-color: #a7f3d0; background: #d1fae5; }
    .action-btn.delete { color: #991b1b; border-color: #fca5a5; background: #fee2e2; }
    .desc-cell { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--muted); }
    .empty { text-align: center; padding: 48px 24px; color: var(--muted); font-size: 14px; }
    @media (max-width: 900px) { .sidebar { display: none; } .main { margin-left: 0; padding: 20px; } }
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Apex<span>Ops</span></div>
    <div class="sidebar-label">Portal</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
      <a href="incidents.php" class="nav-item active"><span class="icon">🚨</span> Incidents</a>
      <a href="estimator.php" class="nav-item"><span class="icon">💰</span> Cost Estimator</a>
    </nav>
    <div class="sidebar-footer">
      <div style="margin-bottom:6px">Signed in as <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
      <a href="logout.php">Sign out</a>
    </div>
  </aside>
  <main class="main">
    <div class="page-header">
      <div><h1>Incident Tracker</h1><p>Log, monitor, and resolve IT incidents.</p></div>
      <button class="btn" onclick="document.getElementById('modal').classList.add('open')">+ Log incident</button>
    </div>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="GET" class="filters">
      <label>Status:</label>
      <select name="status" onchange="this.form.submit()">
        <option value="all" <?= $fs==='all'?'selected':'' ?>>All</option>
        <option value="open" <?= $fs==='open'?'selected':'' ?>>Open</option>
        <option value="resolved" <?= $fs==='resolved'?'selected':'' ?>>Resolved</option>
      </select>
      <label>Severity:</label>
      <select name="severity" onchange="this.form.submit()">
        <option value="all" <?= $fv==='all'?'selected':'' ?>>All</option>
        <option value="critical" <?= $fv==='critical'?'selected':'' ?>>Critical</option>
        <option value="high" <?= $fv==='high'?'selected':'' ?>>High</option>
        <option value="medium" <?= $fv==='medium'?'selected':'' ?>>Medium</option>
        <option value="low" <?= $fv==='low'?'selected':'' ?>>Low</option>
      </select>
    </form>
    <div class="panel">
      <?php if (count($incidents) > 0): ?>
      <table>
        <thead><tr><th>#</th><th>Service</th><th>Description</th><th>Severity</th><th>Status</th><th>Logged</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($incidents as $row): ?>
          <tr>
            <td style="color:var(--muted)"><?= $row['id'] ?></td>
            <td><strong><?= htmlspecialchars($row['service']) ?></strong></td>
            <td class="desc-cell" title="<?= htmlspecialchars($row['description']) ?>"><?= htmlspecialchars($row['description']) ?></td>
            <td><span class="badge badge-<?= $row['severity'] ?>"><?= ucfirst($row['severity']) ?></span></td>
            <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
            <td style="color:var(--muted)"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
            <td><div class="actions">
              <?php if ($row['status'] === 'open'): ?>
                <a href="?resolve=<?= $row['id'] ?>" class="action-btn resolve" onclick="return confirm('Mark as resolved?')">Resolve</a>
              <?php endif; ?>
              <a href="?delete=<?= $row['id'] ?>" class="action-btn delete" onclick="return confirm('Delete?')">Delete</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty">No incidents found.</div>
      <?php endif; ?>
    </div>
  </main>
</div>
<div class="modal-overlay" id="modal">
  <div class="modal">
    <h2>Log a new incident</h2>
    <form method="POST" action="incidents.php">
      <div class="form-group"><label>Affected service</label><input type="text" name="service" placeholder="e.g. EC2, RDS, API Gateway" required></div>
      <div class="form-group"><label>Severity</label><select name="severity"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
      <div class="form-group"><label>Description</label><textarea name="description" placeholder="Describe what happened..." required></textarea></div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" onclick="document.getElementById('modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn">Log incident</button>
      </div>
    </form>
  </div>
</div>
<script>document.getElementById('modal').addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });</script>
</body>
</html>
