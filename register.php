<?php
session_start();
if(isset($_SESSION['userID'])){
    header("Location: dashboard.php");
    exit;
}
require_once 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $errors = [];
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $errors['username'] = "Username is required!";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = "Username must be between 3 and 50 characters!";
    }

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $errors['email'] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email is not valid!";
    } elseif (strlen($email) > 100) {
        $errors['email'] = "Email is too long (max 100 characters)!";
    }

    $password = $_POST['pass'] ?? '';

    if ($password === '') {
        $errors['password'] = "Password is required!";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters!";
    }





    if(!$errors){
        try{
            $sql = "INSERT INTO users(username, email, password_hash)
            VALUES(?, ?, ?)"; 
            $stmt = $conn->prepare($sql);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$username, $email, $password_hash]);
            header("Location: login.php");
            exit;
        } catch(PDOException $e){
            echo "Error: " . htmlspecialchars($e->getMessage());
        }

    }


}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Register</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
        <div class="col-12 col-md-7 col-lg-5">

            <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">

                <h2 class="text-center fw-bold mb-1">Register</h2>
                <p class="text-center text-muted mb-4">Create your ArtArchive account</p>

                <form method="POST">
                <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email"
                            placeholder="name@example.com" value="<?php echo htmlspecialchars($email ?? '');?>" required maxlength="100">
                        <?php if(isset($errors['email'])): ?>
                                <div class="text-danger small">
                                    <?php echo htmlspecialchars($errors['email']);?>
                                </div>
                        <?php endif;?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" type="text" id="username" name="username"
                            placeholder="bsp. name1234" value="<?php echo htmlspecialchars($username ?? '');?>" required minlength="3" maxlength="50">
                        <?php if(isset($errors['username'])): ?>
                            <div class="text-danger small">
                                <?php echo htmlspecialchars($errors['username']);?>
                            </div>
                        <?php endif;?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="pass">Password</label>
                        <input class="form-control" type="password" id="pass" name="pass" required minlength="8">
                        <?php if(isset($errors['password'])): ?>
                            <div class="text-danger small">
                                <?php echo htmlspecialchars($errors['password']);?>
                            </div>
                        <?php endif;?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                        Register
                    </button>
                </form>

                <p class="text-center mt-3 mb-0">
                Already have an account? <a href="login.php">Login</a>
                </p>

            </div>
            </div>

        </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/footer.php'; ?>
    </body>
</html>
