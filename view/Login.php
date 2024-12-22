<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link rel="stylesheet" href="../assets/login.css">
</head>
<body class="background-image">
    <div class="login-body">
        <div class="login">
            <form id="loginForm" method="POST">
                <h2>Login</h2>
                <div id="errorMsg" class="error"></div>
                <input type="email" id="email" name="email" placeholder="Email" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
                <p>Don't have an account? <a href="SignUp.php" class="toggle-link">Sign up</a></p>
            </form>
        </div>
    </div>
    <script src="../assets/login.js"></script>
</body>
</html>
