<?php
// Basic protection — change this password
define('ADMIN_PASS', 'flatlab2024');

session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
    } else {
        $error = 'Wrong password.';
    }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (empty($_SESSION['admin'])) { ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin Login</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f4f4f4}
form{background:#fff;padding:32px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);min-width:280px}
h2{margin:0 0 20px}input{width:100%;padding:8px;margin-bottom:12px;box-sizing:border-box;border:1px solid #ddd;border-radius:4px}
button{width:100%;padding:10px;background:#111;color:#fff;border:none;border-radius:4px;cursor:pointer}
.err{color:red;font-size:13px;margin-bottom:8px}</style></head><body>
<form method="POST"><h2>Admin</h2>
<?php if (!empty($error)) echo "<p class='err'>$error</p>"; ?>
<input type="password" name="password" placeholder="Password" autofocus>
<button type="submit">Login</button></form></body></html>
<?php exit; }

require_once __DIR__ . '/db.php';
$db = get_db();

$tab = $_GET['tab'] ?? 'newsletter';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'], $_POST['delete_table'])) {
    $allowed = ['newsletter_subscribers', 'contact_messages', 'blog_comments'];
    if (in_array($_POST['delete_table'], $allowed)) {
        $db->prepare("DELETE FROM {$_POST['delete_table']} WHERE id = ?")->execute([(int)$_POST['delete_id']]);
    }
    header("Location: admin.php?tab=$tab"); exit;
}
$tables = [
    'newsletter'  => ['label' => 'Newsletter Subscribers', 'table' => 'newsletter_subscribers'],
    'contacts'    => ['label' => 'Contact Messages',       'table' => 'contact_messages'],
    'comments'    => ['label' => 'Blog Comments',          'table' => 'blog_comments'],
];
$current = $tables[$tab] ?? $tables['newsletter'];
$rows = $db->query("SELECT * FROM {$current['table']} ORDER BY id DESC")->fetchAll();
$cols = $rows ? array_keys($rows[0]) : [];
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>FlatLab Admin</title>
<style>
*{box-sizing:border-box}body{font-family:sans-serif;margin:0;background:#f4f4f4;color:#222}
header{background:#111;color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
header h1{margin:0;font-size:18px}header a{color:#aaa;font-size:13px;text-decoration:none}
nav{background:#fff;border-bottom:1px solid #e5e5e5;padding:0 24px;display:flex;gap:4px}
nav a{display:inline-block;padding:12px 16px;font-size:14px;text-decoration:none;color:#555;border-bottom:3px solid transparent}
nav a.active{color:#111;border-bottom-color:#111;font-weight:600}
main{padding:24px}
.count{font-size:13px;color:#888;margin-bottom:12px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08)}
th{background:#111;color:#fff;text-align:left;padding:10px 14px;font-size:13px}
td{padding:10px 14px;font-size:13px;border-bottom:1px solid #f0f0f0;vertical-align:top;max-width:300px;word-break:break-word}
tr:last-child td{border-bottom:none}tr:hover td{background:#fafafa}
.btn-del{background:#dc2626;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-size:12px}
.btn-del:hover{background:#b91c1c}
.empty{text-align:center;padding:40px;color:#999;background:#fff;border-radius:8px}
</style></head>
<body>
<header>
  <h1>FlatLab Admin</h1>
  <a href="?logout=1">Logout</a>
</header>
<nav>
<?php foreach ($tables as $key => $t): ?>
  <a href="?tab=<?= $key ?>" class="<?= $tab === $key ? 'active' : '' ?>"><?= $t['label'] ?></a>
<?php endforeach; ?>
</nav>
<main>
  <p class="count"><?= count($rows) ?> record<?= count($rows) !== 1 ? 's' : '' ?></p>
  <?php if (empty($rows)): ?>
    <div class="empty">No records yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><?php foreach ($cols as $c) echo "<th>$c</th>"; ?><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <?php foreach ($row as $val) echo '<td>' . htmlspecialchars((string)$val) . '</td>'; ?>
        <td>
          <form method="POST" onsubmit="return confirm('Delete this record?')">
            <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
            <input type="hidden" name="delete_table" value="<?= htmlspecialchars($current['table']) ?>">
            <button class="btn-del" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</body></html>
