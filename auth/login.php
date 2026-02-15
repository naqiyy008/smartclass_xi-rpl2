<!DOCTYPE html>
<html>

<head>
  <title>Login SmartClass</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

  <div id="loading-screen">
    <div class="neon-loader">
      <span></span>
    </div>
  </div>

  <div class="login-box">
    <h2>SmartClass XI RPL 2</h2>
    <p>Login</p>

    <form action="proses_login.php" method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" class="btn-primary">Login</button>
    </form>
  </div>
  <script src="assets/script.js"></script>

</body>

</html>