<?php
/**
 * Kara David's Wish Note (QAT test web application)
 * Author:  Ernelaine A. Sapugay
 * Section: BSIS 3-C
 * Activity: Final Portfolio
 */

// ---- Database connection (local MariaDB, development only: no password) ----
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"] ?? "");
    $LastName    = trim($_POST["LastName"] ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"] ?? "");

    // =====================================================================
    // INTENTIONAL FLAW (for QA testing):
    // $Location is captured above, but it is deliberately OMITTED from both
    // the INSERT statement below and the bind_param() call. As a result,
    // the Location the user enters is NEVER saved to the database. Only
    // FirstName, LastName and GovPosition are stored. Testers should catch
    // this when comparing the form data with the table contents.
    // =====================================================================
    $sql = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition); // Location omitted (flaw)
        if ($stmt->execute()) {
            $message = "Success! The wish note has been sent.";
            $messageType = "success";
        } else {
            $message = "Error: the wish note could not be saved.";
            $messageType = "error";
        }
        $stmt->close();
    } else {
        // Fallback error if the query fails to prepare
        $message = "Error: failed to prepare the database query.";
        $messageType = "error";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kara David's Wish Note</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center;
         background: #f0f2f5; font-family: Arial, Helvetica, sans-serif; color: #263238; }
  .card { width: 100%; max-width: 440px; background: #fff; border-radius: 10px; padding: 28px;
          box-shadow: 0 4px 16px rgba(0,0,0,.12); }
  h2 { margin: 0 0 20px; text-align: center; color: #1976d2; }
  label { display: block; margin: 14px 0 6px; font-weight: bold; font-size: 14px; }
  input[type=text] { width: 100%; padding: 10px; border: 1px solid #b0bec5; border-radius: 6px; font-size: 15px; }
  input[type=text]:focus { outline: none; border-color: #1976d2; box-shadow: 0 0 0 2px rgba(25,118,210,.2); }
  button { border: 0; border-radius: 6px; padding: 11px 18px; font-size: 15px; cursor: pointer; color: #fff; background: #1976d2; transition: background .2s; }
  button:hover { background: #1258a3; }
  .submit { width: 100%; margin-top: 22px; }
  .msg { padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
  .msg.success { background: #e3f2fd; color: #0d47a1; border: 1px solid #90caf9; }
  .msg.error { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }
  .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); align-items: center; justify-content: center; padding: 20px; }
  .overlay.open { display: flex; }
  .modal { background: #fff; border-radius: 10px; padding: 24px; width: 100%; max-width: 400px; box-shadow: 0 8px 24px rgba(0,0,0,.3); }
  .modal h3 { margin-top: 0; color: #1976d2; }
  .modal p { margin: 8px 0; word-wrap: break-word; }
  .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
  .cancel { background: #78909c; }
  .cancel:hover { background: #546e7a; }
</style>
</head>
<body>
<div class="card">
  <h2>Kara David's Wish Note</h2>

  <?php if ($message): ?>
    <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form id="wishForm" method="POST" action="">
    <label for="FirstName">Target's First Name</label>
    <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

    <label for="LastName">Target's Last Name</label>
    <input type="text" id="LastName" name="LastName" maxlength="30" required>

    <label for="GovPosition">Target's Government Position</label>
    <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

    <label for="Location">Target's Location</label>
    <input type="text" id="Location" name="Location" maxlength="40">

    <button type="submit" class="submit">Send Wish Note</button>
  </form>
</div>

<div class="overlay" id="overlay">
  <div class="modal">
    <h3>Verify Target Data</h3>
    <p><strong>First Name:</strong> <span id="vFirst"></span></p>
    <p><strong>Last Name:</strong> <span id="vLast"></span></p>
    <p><strong>Government Position:</strong> <span id="vPos"></span></p>
    <p><strong>Location:</strong> <span id="vLoc"></span></p>
    <div class="actions">
      <button type="button" class="cancel" id="cancelBtn">Cancel</button>
      <button type="button" id="confirmBtn">Confirm &amp; Send</button>
    </div>
  </div>
</div>

<script>
  const form = document.getElementById('wishForm');
  const overlay = document.getElementById('overlay');

  form.addEventListener('submit', function (e) {
    e.preventDefault(); // intercept submission to show the verification modal
    document.getElementById('vFirst').textContent = form.FirstName.value;
    document.getElementById('vLast').textContent  = form.LastName.value;
    document.getElementById('vPos').textContent   = form.GovPosition.value;
    document.getElementById('vLoc').textContent   = form.Location.value || '(none)';
    overlay.classList.add('open');
  });

  document.getElementById('cancelBtn').addEventListener('click', function () {
    overlay.classList.remove('open');
  });

  document.getElementById('confirmBtn').addEventListener('click', function () {
    form.submit(); // native submit bypasses the intercepted event
  });
</script>
</body>
</html>
