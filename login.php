<?php
    session_start();
    if(isset($_SESSION['userID'])){
        header("Location: dashboard.php");
        exit;
    }
    require_once "db.php";

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $errors = [];

        $email = trim($_POST['email'] ?? '');

        if($email == ''){
            $errors['email'] = "Email is required!";
        }

        $password = $_POST['pass'];

        if($password == ''){
            $errors['password'] = "Password is required!";
        }

        if(!$errors){
            try{
                $sql = "SELECT userID, username, password_hash FROM users WHERE email = ? LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$email]);
                $user = $stmt->fetch();
            } catch(PDOException $e){
                echo "Error: " . htmlspecialchars($e->getMessage());
            }
            if(!$user || !password_verify($password, $user['password_hash'])){
                $errors['login'] = "Email or Password is invalid!";
            }

            if(!$errors){
                $_SESSION['userID'] = $user['userID'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            }

        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login to artWORK</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="d-flex flex-column min-vh-100 bg-light">
    <div class="container py-5 flex-grow-1">
        <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">

            <h2 class="text-center fw-bold mb-4">Login to ArtArchive</h2>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" for="email">Email:</label>
                    <input class="form-control" type="email" id="email" name="email"
                        placeholder="name@example.com" value="<?php echo htmlspecialchars($email ?? '');?>" required>
                    <?php if(isset($errors['email'])):?>
                        <div class="text-danger small">
                            <?php echo htmlspecialchars($errors['email']);?>
                        </div>
                    <?php endif;?>

                </div>

                <div class="mb-3">
                    <label class="form-label" for="pass">Password:</label>
                    <input class="form-control" type="password" id="pass" name="pass" required>
                    <?php if(isset($errors['password'])):?>
                        <div class="text-danger small">
                            <?php echo htmlspecialchars($errors['password']);?>
                        </div>
                    <?php endif;?>
                </div>

                <?php if(isset($errors['login'])):?>
                    <div class="text-danger small">
                        <?php echo htmlspecialchars($errors['login']);?>
                    </div>
                <?php endif;?>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary w-100">
                    Login
                    </button>
                </div>
            </form>

            <p class="text-center mt-3 mb-0">Don’t have an account?<a href="register.php">Register</a>
            </p>

        </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/footer.php'; ?>
    </body>
</html>