<?php
// dashboard.php - User Dashboard
session_start();
require_once 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Admin goes to admin panel
if (!empty($_SESSION['is_admin'])) {
    header("Location: admin.php");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);

// ---- Fetch user's own reports ----
$filter = sanitize($conn, $_GET['filter'] ?? 'all');
$where  = "WHERE user_id = $user_id";
if (in_array($filter, ['Pending','In Progress','Resolved'])) {
    $safe_filter = $conn->real_escape_string($filter);
    $where .= " AND status = '$safe_filter'";
}

$reports_query = "
    SELECT id, disaster_type, location, description, status, reported_at
    FROM emergency_reports
    $where
    ORDER BY reported_at DESC
";
$reports = $conn->query($reports_query);

// ---- Stats ----
$stats = [];
$counts = $conn->query(
    "SELECT status, COUNT(*) as cnt FROM emergency_reports
     WHERE user_id = $user_id GROUP BY status"
);
$total = 0;
while ($row = $counts->fetch_assoc()) {
    $stats[$row['status']] = $row['cnt'];
    $total += $row['cnt'];
}

// Disaster type icons
function dtypeIcon($type) {
    return match(strtolower($type)) {
        'fire'     => ['🔥', 'dtype-fire'],
        'flood'    => ['🌊', 'dtype-flood'],
        'accident' => ['🚗', 'dtype-accident'],
        'medical'  => ['🏥', 'dtype-medical'],
        default    => ['⚠️',  'dtype-other'],
    };
}

// Status badge helper
function statusBadge($status) {
    return match($status) {
        'Pending'     => '<span class="badge-status badge-pending">⏳ Pending</span>',
        'In Progress' => '<span class="badge-status badge-inprogress">🔄 In Progress</span>',
        'Resolved'    => '<span class="badge-status badge-resolved">✅ Resolved</span>',
        default       => '<span class="badge-status badge-pending">' . htmlspecialchars($status) . '</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Disaster Emergency Help System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-wrapper">

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      🚨 DEHS
      <span class="badge">LIVE</span>
    </div>
    <ul class="navbar-nav">
      <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
      <li><a href="report.php">🆘 Report Emergency</a></li>
      <li class="user-info">👤 <?= $user_name ?></li>
      <li><a href="logout.php" class="btn-danger">⏻ Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<div class="main-content">
  <div class="container">

    <!-- Flash message -->
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= $flash['type'] === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h2>My Dashboard</h2>
        <p>Track and manage your emergency reports</p>
      </div>
      <a href="report.php" class="btn btn-primary" style="width:auto">
        + Report Emergency
      </a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <h3><?= $total ?></h3>
          <p>Total Reports</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
          <h3><?= $stats['Pending'] ?? 0 ?></h3>
          <p>Pending</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔄</div>
        <div class="stat-info">
          <h3><?= $stats['In Progress'] ?? 0 ?></h3>
          <p>In Progress</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <h3><?= $stats['Resolved'] ?? 0 ?></h3>
          <p>Resolved</p>
        </div>
      </div>
    </div>

    <!-- Reports Table -->
    <div class="table-card">
      <div class="table-card-header">
        <h3>My Emergency Reports</h3>
        <!-- Filter -->
        <div class="admin-toolbar">
          <form method="GET" style="display:flex;gap:0.5rem;align-items:center;">
            <select name="filter" class="filter-select" onchange="this.form.submit()">
              <option value="all"         <?= $filter==='all'?'selected':'' ?>>All Status</option>
              <option value="Pending"     <?= $filter==='Pending'?'selected':'' ?>>Pending</option>
              <option value="In Progress" <?= $filter==='In Progress'?'selected':'' ?>>In Progress</option>
              <option value="Resolved"    <?= $filter==='Resolved'?'selected':'' ?>>Resolved</option>
            </select>
          </form>
        </div>
      </div>

      <?php if ($reports->num_rows === 0): ?>
        <div class="empty-state">
          <span class="icon">📭</span>
          <h3>No reports found</h3>
          <p>
            <?= $filter !== 'all' ? 'No reports with status "' . htmlspecialchars($filter) . '".' : 'You haven\'t submitted any emergency reports yet.' ?>
            <br>
            <a href="report.php" style="margin-top:0.5rem;display:inline-block">Report an emergency →</a>
          </p>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Type</th>
                <th>Location</th>
                <th>Description</th>
                <th>Date & Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; while ($row = $reports->fetch_assoc()): ?>
                <?php [$icon, $cls] = dtypeIcon($row['disaster_type']); ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td>
                    <span class="dtype <?= $cls ?>">
                      <?= $icon ?> <?= htmlspecialchars(ucfirst($row['disaster_type'])) ?>
                    </span>
                  </td>
                  <td>📍 <?= htmlspecialchars($row['location']) ?></td>
                  <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                      title="<?= htmlspecialchars($row['description']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($row['description'], 0, 60, '…')) ?>
                  </td>
                  <td style="white-space:nowrap">
                    <?= date('d M Y, h:i A', strtotime($row['reported_at'])) ?>
                  </td>
                  <td><?= statusBadge($row['status']) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /container -->
</div><!-- /main-content -->

<footer class="footer">
  Disaster Emergency Help System &copy; <?= date('Y') ?> &nbsp;|&nbsp;
  Emergency Helpline: <strong>112</strong>
</footer>

</body>
</html>
