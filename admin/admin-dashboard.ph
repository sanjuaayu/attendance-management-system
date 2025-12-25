<?php
// ========= admin-dashboard.php =========
session_start();
require_once 'config.php';

// NO ACCESS CONTROL - Direct access allowed

// --- Stats ---
$today = date('Y-m-d');
$totalUsers       = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0;
$presentToday     = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM attendance WHERE DATE(punch_in_datetime)=CURDATE()")->fetch_assoc()['c'] ?? 0;
$absentToday      = max(0, $totalUsers - $presentToday);
$todaysPunchIn    = $conn->query("SELECT COUNT(*) AS c FROM attendance WHERE DATE(punch_in_datetime)=CURDATE()")->fetch_assoc()['c'] ?? 0;
$todaysPunchOut   = $conn->query("SELECT COUNT(*) AS c FROM attendance WHERE DATE(punch_out_datetime)=CURDATE()")->fetch_assoc()['c'] ?? 0;

// --- Data for dropdowns ---
$branches = [];
$resB = $conn->query("SELECT id, name FROM branches ORDER BY name");
while ($r = $resB->fetch_assoc()) { $branches[] = $r; }

$users = [];
$resU = $conn->query("SELECT u.id, u.username, u.full_name, u.role, u.branch_id, b.name AS branch_name
                      FROM users u
                      LEFT JOIN branches b ON b.id=u.branch_id
                      ORDER BY u.username");
while ($r = $resU->fetch_assoc()) { $users[] = $r; }

// Edit mode?
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $get = $conn->prepare("SELECT id, username, full_name, role, branch_id FROM users WHERE id=?");
    $get->bind_param("i", $id);
    $get->execute();
    $editUser = $get->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#f4f6f9}
  .card{border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.06)}
  .btn-rounded{border-radius:10px}
  .table thead th{background:#0d6efd;color:#fff}
</style>
</head>
<body>
<div class="container py-4">
  <h2 class="text-center mb-4">Admin Dashboard</h2>

  <!-- Overview -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center p-3"><h6>Total Users</h6><h3><?= $totalUsers ?></h3></div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3"><h6>Present Today</h6><h3 class="text-success"><?= $presentToday ?></h3></div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3"><h6>Absent Today</h6><h3 class="text-danger"><?= $absentToday ?></h3></div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3"><h6>Today's Punch In</h6><h3><?= $todaysPunchIn ?></h3></div>
    </div>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center p-3"><h6>Today's Punch Out</h6><h3><?= $todaysPunchOut ?></h3></div>
    </div>
  </div>

  <!-- Reports -->
  <div class="card mb-4">
    <div class="card-body">
      <h4 class="mb-3">Reports</h4>
      <div class="row g-3">
        <!-- All (date range) -->
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <h6>All attendance (date range)</h6>
            <form method="post" action="download_report.php">
              <div class="mb-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from_date" required>
              </div>
              <div class="mb-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to_date" required>
              </div>
              <button class="btn btn-primary btn-rounded w-100" name="generate_report">Download CSV</button>
            </form>
          </div>
        </div>
        <!-- Branch-wise -->
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <h6>Branch wise attendance</h6>
            <form method="post" action="download_report.php">
              <div class="mb-2">
                <label class="form-label">Branch</label>
                <select class="form-select" name="branch_id" required>
                  <?php foreach($branches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from_date" required>
              </div>
              <div class="mb-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to_date" required>
              </div>
              <button class="btn btn-primary btn-rounded w-100" name="download_report">Download CSV</button>
            </form>
          </div>
        </div>
        <!-- Single user -->
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <h6>Single user attendance</h6>
            <form method="post" action="download_report.php">
              <div class="mb-2">
                <label class="form-label">User</label>
                <select class="form-select" name="user_id" required>
                  <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>">
                      <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['branch_name'] ?? 'N/A') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="from_date" required>
              </div>
              <div class="mb-2">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="to_date" required>
              </div>
              <button class="btn btn-primary btn-rounded w-100" name="export_report">Download CSV</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- User Management -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="mb-3">User Management</h4>
        <?php if(!empty($_GET['msg'])): ?>
          <span class="badge bg-success"><?= htmlspecialchars($_GET['msg']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Add / Edit form -->
      <form class="row g-2" method="post" action="user_crud.php">
        <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
        <?php if($editUser): ?><input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>

        <div class="col-md-3">
          <label class="form-label">Full name</label>
          <input class="form-control" name="full_name" required value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" required>
            <?php
              $roles = ['admin'=>'Admin','agent'=>'Agent','parent_admin'=>'Parent Admin'];
              $cur = $editUser['role'] ?? 'agent';
              foreach($roles as $val=>$label){
                $sel = $cur===$val ? 'selected' : '';
                echo "<option value=\"$val\" $sel>$label</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Branch</label>
          <select class="form-select" name="branch_id" required>
            <?php
              $curB = $editUser['branch_id'] ?? '';
              foreach($branches as $b){
                $sel = ($curB==$b['id']) ? 'selected':'';
                echo "<option value=\"{$b['id']}\" $sel>".htmlspecialchars($b['name'])."</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?= $editUser ? 'New ' : '' ?>Password</label>
          <input class="form-control" name="password" <?= $editUser ? '' : 'required' ?> placeholder="<?= $editUser ? '(leave blank to keep)' : '' ?>">
        </div>
        <div class="col-md-12 d-grid d-md-block mt-2">
          <button class="btn btn-success btn-rounded"><?= $editUser ? 'Update User' : 'Add User' ?></button>
          <?php if($editUser): ?>
            <a class="btn btn-secondary btn-rounded" href="admin-dashboard.php">Cancel</a>
          <?php endif; ?>
        </div>
      </form>

      <!-- Users table -->
      <div class="table-responsive mt-4">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Full name</th>
              <th>Role</th>
              <th>Branch</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($users as $i=>$u): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['full_name']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td><?= htmlspecialchars($u['branch_name'] ?? 'N/A') ?></td>
              <td>
                <a class="btn btn-sm btn-primary" href="admin-dashboard.php?edit=<?= (int)$u['id'] ?>">Edit</a>
                <form class="d-inline" method="post" action="user_crud.php" onsubmit="return confirm('Delete this user?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <p class="text-center text-muted">Tip: Reports export as CSV. Open in Excel/Google Sheets.</p>
</div>
</body>
</html>
