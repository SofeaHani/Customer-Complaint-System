<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Server-side email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.com$/', $email)) {
        echo "<script>
                alert('Please enter a valid email address ending with .com');
              </script>";
    } else {
        $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'user')";

        if ($conn->query($query)) {
            echo "<script>
                    alert('Account successfully registered!');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            echo "<script>
                    alert('Error: " . addslashes($conn->error) . "');
                  </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: rgb(255, 165, 207);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .card {
            background-color: rgb(255, 211, 228);
            width: 400px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
        }

        .form-control {
            margin-bottom: 15px;
            border-radius: 10px;
        }

        .btn-primary {
            width: 100%;
            border-radius: 10px;
            background-color: rgb(250, 58, 106);
            border: none;
        }

        .btn-primary:hover {
            background-color: rgb(141, 0, 49);
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-header h2 {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-header p {
            color: #666;
        }
    </style>
    <script>
        // Client-side validation for .com email
        function validateForm() {
            const emailField = document.forms["signupForm"]["email"].value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailField) || !emailField.endsWith(".com")) {
                alert("Please enter a valid email address ending with .com");
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="card">
        <div class="form-header">
            <h2>Sign Up</h2>
        </div>

        <form method="POST" name="signupForm" onsubmit="return validateForm()">
            <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <p class="mt-3 text-center">Already have an account? <a href="login.php">Log In</a></p>
    </div>
</body>

</html>