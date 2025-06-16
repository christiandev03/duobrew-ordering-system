<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin/admin_dashboard.php");
        } else {
            header("Location: user/user_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid Username or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duo Brew Ordering System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background-image: url('images/background_image.jpg');
            background-repeat: no-repeat center center fixed;
            background-size: 900px;
        }
        
        .container {
            width: 100%;
            max-width: 650px;
            padding: 0;
        }
        
        .login-box {
            background-color:rgb(156, 98, 53);
            border-radius: 30px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.55);
        }
        
        .logo {
            width: 300px;

        }
        
        h1 {
            color: white;
            margin-bottom: 30px;
            font-size: 30px;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #000;
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #B57C4F;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        
        .login-btn:hover {
            background-color: #583628;
        }
        
        p {
            color: #fff ;
            margin-top: 20px;
        }
        
        .register-link {
            font-weight: bold;
        }
        
        .error {
            color: #ff3333;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        form {
            max-width: 400px;
            margin: 0 auto;
        }

        a{
            color: #FFC99A;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <img src="images/duo_brew.png" alt="Logo" class="logo">
            
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form method="POST">
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <span class="icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
                <p>Don't have an account? <a href="register.php" class="register-link">Register Here</a></p>
            </form>
        </div>
    </div>
</body>
</html>