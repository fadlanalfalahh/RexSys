<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - Rexsy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- penting untuk mobile -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e0e0e0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
        }

        .login-footer {
            font-size: 14px;
            color: #777;
        }

        @media (max-width: 576px) {
            .login-box {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>

    <div class="login-box">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="text-center mb-4">Login</h4>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="proses_login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>

        <p class="text-center login-footer mt-3">&copy; <?= date('Y'); ?> Rexsy Collection</p>
    </div>

</body>

</html>