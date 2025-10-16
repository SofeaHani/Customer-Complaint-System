<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Complaint System</title>
    <link href="styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background: linear-gradient(to right, #ff9a9e, #fecfef);
            color: rgba(34, 30, 34, 0.75);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: white;
        }

        .navbar-left {
            display: flex;
            align-items: center;
        }

        .navbar img {
            height: 50px;
            margin-right: 10px;
        }

        .text-logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: rgb(4, 4, 4);
            font-family: Inter, sans-serif;
        }

        .navbar a {
            color: rgb(250, 58, 106);
            text-decoration: none;
            margin-left: 1rem;
            font-weight: 500;
            font-size: 1.2rem;
        }

        .navbar a:hover {
            text-decoration: none;
        }

        .container {
            text-align: center;
            padding: 5rem 2rem;
        }

        h1 {
            font-size: 4.0rem;
            margin-bottom: 1rem;
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 30px;
        }

        .btn {
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            background: rgb(250, 58, 106);
            color: white;
            transition: background 0.3s;
            font-size: 1.2rem;
        }

        .btn:hover {
            background: #e91e63;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar-left {
                margin-bottom: 1rem;
            }

            .navbar a {
                font-size: 1rem;
                margin-left: 0;
                margin-right: 1rem;
            }

            h1 {
                font-size: 3rem;
            }

            .btn {
                font-size: 1rem;
                padding: 0.75rem 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 3rem 1rem;
            }

            h1 {
                font-size: 2.5rem;
            }

            .btn {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <div class="navbar-left">
            <img src="strateq.jpeg" alt="Logo">
            <span class="text-logo">STRATEQ</span>
        </div>
        <div>
            <a href="login.php">User Login</a>
            <a href="signup.php">User Registration</a>
            <a href="adminlogin.php">Admin</a>
        </div>
    </div>
    <div class="container">
        <h1>Customer Complaint System</h1>
        <h2>Search here for your Electric Service / Product Complaint Status</h2>
        <div class="buttons">
            <a href="signup.php" class="btn">Sign Up</a>
            <a href="login.php" class="btn">Login</a>
        </div>
    </div>
</body>

</html>