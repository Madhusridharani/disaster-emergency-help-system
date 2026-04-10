<?php
// admin.php - Admin Panel
session_start();
require_once 'db.php';

// Must be logged in as admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit();
}

$success = '';
$error   = '';

// ---- Handle status update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $report_id  = (int)($_POST['report_id']  ?? 0);
    $new_status = sanitize($conn, $_POST['new_status'] ?? '');
    $valid_statuses = ['Pending', 'In Progress', 'Resolved'];

    if ($report_id > 0 && in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE emergency_reports SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $report_id);
        if ($stmt->execute()) {
            setFlash('success', "Report #$report_id status updated to \"$new_status\".");
        } else {
            setFlash('error', 'Failed to update status. Please try again.');
        }
        $stmt->close();
    } else {
        setFlash('error', 'Invalid request.');
    }
    // Redirect to keep current filter
    $qs = http_build_query(array_filter([
        'filter_type'   => $_POST['filter_type_bk']   ?? '',
        'filter_status' => $_POST['filter_status_bk']  ?? '',
    ]));
    header("Location: admin.php" . ($qs ? "?$qs" : ''));
    exit();
}

// ---- Filters ----
$filter_type   = sanitize($conn, $_GET['filter_type']   ?? 'all');
$filter_status = sanitize($conn, $_GET['filter_status'] ?? 'all');

$where_parts = [];
$valid_types   = ['fire','flood','accident','medical','other'];
$valid_statuses= ['Pending','In Progress','Resolved'];

if (in_array($filter_type, $valid_types)) {
    $ft = $conn->real_escape_string($filter_type);
    $where_parts[] = "r.disaster_type = '$ft'";
}
if (in_array($filter_status, $valid_statuses)) {
    $fs = $conn->real_escape_string($filter_status);
    $where_parts[] = "r.status = '$fs'";
}

$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// ---- Fetch reports with user info ----
$reports = $conn->query("
    SELECT r.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
    FROM emergency_reports r
    LEFT JOIN users u ON r.user_id = u.id
    $where_sql
    ORDER BY r.created_at DESC
");

// ---- Overall stats ----
$stat_rows = $conn->query("SELECT status, COUNT(*) c FROM emergency_reports GROUP BY status");
$stats = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0, '_total' => 0];
while ($s = $stat_rows->fetch_assoc()) {
    $stats[$s['status']] = (int)$s['c'];
    $stats['_total'] += (int)$s['c'];
}
$total_users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];

function dtypeIcon($type) {
    return match(strtolower($type)) {
        'fire'     => ['🔥', 'dtype-fire'],
        'flood'    => ['🌊', 'dtype-flood'],
        'accident' => ['🚗', 'dtype-accident'],
        'medical'  => ['🏥', 'dtype-medical'],
        default    => ['⚠️',  'dtype-other'],
    };
}
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
  <title>Admin Panel — Disaster Emergency Help System</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="page-wrapper">

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      🚨 DEHS
      <span class="admin-badge">ADMIN</span>
    </div>
    <ul class="navbar-nav">
      <li><a href="admin.php" class="active">🛡 Admin Panel</a></li>
      <li class="user-info">👤 Administrator</li>
      <li><a href="logout.php" class="btn-danger">⏻ Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<div class="main-content">
  <div class="container">

    <!-- Flash -->
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= $flash['type'] === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="page-header">
      <div>
        <h2>🛡 Admin Control Panel</h2>
        <p>View and manage all emergency reports across the system</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
          <h3><?= $total_users ?></h3>
          <p>Registered Users</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <h3><?= $stats['_total'] ?></h3>
          <p>Total Reports</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
          <h3 class="text-red"><?= $stats['Pending'] ?></h3>
          <p>Pending</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔄</div>
        <div class="stat-info">
          <h3 style="color:var(--blue)"><?= $stats['In Progress'] ?></h3>
          <p>In Progress</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <h3 style="color:var(--green)"><?= $stats['Resolved'] ?></h3>
          <p>Resolved</p>
        </div>
      </div>
    </div>

    <!-- Reports Table -->
    <div class="table-card">
      <div class="table-card-header">
        <h3>All Emergency Reports</h3>

        <!-- Filters -->
        <form method="GET" class="admin-toolbar">
          <select name="filter_type" class="filter-select" onchange="this.form.submit()">
            <option value="all" <?= $filter_type==='all'?'selected':'' ?>>All Types</option>
            <option value="fire"     <?= $filter_type==='fire'?'selected':'' ?>>🔥 Fire</option>
            <option value="flood"    <?= $filter_type==='flood'?'selected':'' ?>>🌊 Flood</option>
            <option value="accident" <?= $filter_type==='accident'?'selected':'' ?>>🚗 Accident</option>
            <option value="medical"  <?= $filter_type==='medical'?'selected':'' ?>>🏥 Medical</option>
            <option value="other"    <?= $filter_type==='other'?'selected':'' ?>>⚠️ Other</option>
          </select>
          <select name="filter_status" class="filter-select" onchange="this.form.submit()">
            <option value="all"         <?= $filter_status==='all'?'selected':'' ?>>All Statuses</option>
            <option value="Pending"     <?= $filter_status==='Pending'?'selected':'' ?>>⏳ Pending</option>
            <option value="In Progress" <?= $filter_status==='In Progress'?'selected':'' ?>>🔄 In Progress</option>
            <option value="Resolved"    <?= $filter_status==='Resolved'?'selected':'' ?>>✅ Resolved</option>
          </select>
          <?php if ($filter_type !== 'all' || $filter_status !== 'all'): ?>
            <a href="admin.php" class="btn btn-secondary btn-sm">✕ Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if ($reports->num_rows === 0): ?>
        <div class="empty-state">
          <span class="icon">📭</span>
          <h3>No reports found</h3>
          <p>No emergency reports match the current filters.</p>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#ID</th>
                <th>Type</th>
                <th>Reported By</th>
                <th>Location</th>
                <th>Description</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Update Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $reports->fetch_assoc()): ?>
                <?php [$icon, $cls] = dtypeIcon($row['disaster_type']); ?>
                <tr>
                  <td style="color:var(--text-muted);font-size:0.8rem">#<?= $row['id'] ?></td>
                  <td>
                    <span class="dtype <?= $cls ?>">
                      <?= $icon ?> <?= htmlspecialchars(ucfirst($row['disaster_type'])) ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight:600;color:var(--text-primary);font-size:0.88rem">
                      <?= htmlspecialchars($row['user_name'] ?? 'Unknown') ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted)">
                      <?= htmlspecialchars($row['user_phone'] ?? '') ?>
                    </div>
                  </td>
                  <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                      title="<?= htmlspecialchars($row['location']) ?>">
                    📍 <?= htmlspecialchars($row['location']) ?>
                  </td>
                  <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                      title="<?= htmlspecialchars($row['description']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($row['description'], 0, 55, '…')) ?>
                  </td>
                  <td style="white-space:nowrap;font-size:0.82rem">
                    <?= date('d M Y', strtotime($row['reported_at'])) ?>
                    <br><span style="color:var(--text-muted)"><?= date('h:i A', strtotime($row['reported_at'])) ?></span>
                  </td>
                  <td><?= statusBadge($row['status']) ?></td>
                  <td>
                    <!-- Inline status update form -->
                    <form method="POST" action="admin.php" class="status-form">
                      <input type="hidden" name="report_id" value="<?= $row['id'] ?>">
                      <input type="hidden" name="filter_type_bk"   value="<?= htmlspecialchars($filter_type) ?>">
                      <input type="hidden" name="filter_status_bk" value="<?= htmlspecialchars($filter_status) ?>">
                      <select name="new_status">
                        <option value="Pending"     <?= $row['status']==='Pending'?'selected':''?>>Pending</option>
                        <option value="In Progress" <?= $row['status']==='In Progress'?'selected':''?>>In Progress</option>
                        <option value="Resolved"    <?= $row['status']==='Resolved'?'selected':''?>>Resolved</option>
                      </select>
                      <button type="submit" name="update_status" class="btn btn-info btn-sm">
                        Update
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div><!-- /table-card -->

  </div><!-- /container -->
</div><!-- /main-content -->

<footer class="footer">
  Disaster Emergency Help System — Admin Panel &copy; <?= date('Y') ?> &nbsp;|&nbsp;
  Emergency Helpline: <strong>112</strong>
</footer>

</body>
</html>
