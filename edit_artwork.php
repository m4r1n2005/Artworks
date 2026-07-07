<?php
session_start();

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

if (!isset($_GET['artID']) || !is_numeric($_GET['artID'])) {
    header("Location: dashboard.php");
    exit;
}

$userID = $_SESSION['userID'];
$artID  = (int) $_GET['artID'];

// load artwork
$sql = "SELECT title, descriptions FROM artworks WHERE artID = ? AND userID = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$artID, $userID]);
$artwork = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$artwork) {
    header("Location: dashboard.php");
    exit;
}

$title = $artwork['title'] ?? '';
$descriptions = $artwork['descriptions'] ?? '';

// load tags as a whole string
$sqlTAGS = "SELECT GROUP_CONCAT(t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
            FROM tags t
            JOIN artwork_tags at USING(tagID)
            WHERE at.artID = ?";
$stmtTAGS = $conn->prepare($sqlTAGS);
$stmtTAGS->execute([$artID]);
$tagString = $stmtTAGS->fetch(PDO::FETCH_ASSOC);
$rawTags = $tagString['tags'] ?? '';

// load images
$sqlImgs = "SELECT imgID, filepath FROM artwork_images WHERE artID = ? ORDER BY imgID ASC";
$stmtImgs = $conn->prepare($sqlImgs);
$stmtImgs->execute([$artID]);
$images = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        $errors['title'] = "Please insert a title!";
    } elseif (strlen($title) > 30) {
        $errors['title'] = "Title is too long!";
    }

    $descriptions = trim($_POST['descriptions'] ?? '');
    if ($descriptions === '') {
        $errors['descriptions'] = "Please insert a description!";
    } elseif (strlen($descriptions) > 1000) {
        $errors['descriptions'] = "Description is too long!";
    }

    $rawTags = trim($_POST['tags'] ?? '');
    $tags = [];

    if ($rawTags !== '') {
        $parts = explode(',', $rawTags);

        foreach ($parts as $tag) {
            $tag = trim($tag);
            if ($tag === '') continue;

            $tag = mb_strtolower($tag);

            if (mb_strlen($tag) > 40) {
                $errors['tags'] = "One tag is too long (max 40 characters)!";
                break;
            }

            $tags[] = $tag;
        }

        $tags = array_values(array_unique($tags));
    }

    if (count($tags) > 10) {
        $errors['tags'] = "Too many tags (max 10)!";
    }

    // images 
    $target_dir = "uploads/";

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $index => $originalName) {
            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                $errors['images'] = "Image upload error!";
                break;
            }
            $tmpName = $_FILES['images']['tmp_name'][$index];
            if (getimagesize($tmpName) === false) {
                $errors['images'] = "One file is not a valid image!";
                break;
            }
        }
    }

    if (!$errors) {
        try {
            $conn->beginTransaction();

            // UPDATE artwork data
            $sql = "UPDATE artworks SET title = ?, descriptions = ? WHERE artID = ? AND userID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$title, $descriptions, $artID, $userID]);

            // replace tag links (NOT deleting tags table!)
            $delTagLinks = $conn->prepare("DELETE FROM artwork_tags WHERE artID = ?");
            $delTagLinks->execute([$artID]);

            foreach ($tags as $tag) {

                $queryTAGS = "SELECT tagID FROM tags WHERE tag_name = ?";
                $select = $conn->prepare($queryTAGS);
                $select->execute([$tag]);
                $tagID = $select->fetchColumn();

                if (!$tagID) {
                    $sqlTAG = "INSERT INTO tags(tag_name) VALUES(?)";
                    $insert = $conn->prepare($sqlTAG);
                    $insert->execute([$tag]);
                    $tagID = $conn->lastInsertId();
                }

                $link = $conn->prepare("INSERT IGNORE INTO artwork_tags(artID, tagID) VALUES(?, ?)");
                $link->execute([$artID, $tagID]);
            }

            // upload new images ONLY if user wants to
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $index => $originalName) {

                    $tmpName = $_FILES['images']['tmp_name'][$index];
                    $filename = uniqid() . "_" . basename($originalName);
                    $target_file = $target_dir . $filename;

                    if (!move_uploaded_file($tmpName, $target_file)) {
                        throw new Exception("File upload failed!");
                    }

                    $sqlIMG = "INSERT INTO artwork_images(artID, filepath) VALUES(?, ?)";
                    $stmtIMG = $conn->prepare($sqlIMG);
                    $stmtIMG->execute([$artID, $filename]);
                }
            }

            $conn->commit();
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
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
  <title>Edit Artwork | ArtArchive</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

  <div class="container py-5 flex-grow-1">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">

        <h2 class="fw-bold mb-4 text-center">Edit Artwork</h2>

        <div class="card">
          <div class="card-body p-4">

            <form method="POST" enctype="multipart/form-data">

              <!-- Title -->
              <div class="mb-3">
                <label class="form-label" for="title">Title</label>
                <input
                  type="text"
                  class="form-control"
                  id="title"
                  name="title"
                  placeholder="e.g. Sunset Portrait"
                  value="<?php echo htmlspecialchars($title ?? ''); ?>"
                  required
                  maxlength="30"
                >
                <?php if (isset($errors['title'])): ?>
                  <div class="text-danger small">
                    <?php echo htmlspecialchars($errors['title']); ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Description -->
              <div class="mb-3">
                <label class="form-label" for="descriptions">Description</label>
                <textarea
                  class="form-control"
                  id="descriptions"
                  name="descriptions"
                  rows="4"
                  placeholder="Write a short description of your artwork..."
                  required
                ><?php echo htmlspecialchars($descriptions ?? ''); ?></textarea>
                <?php if (isset($errors['descriptions'])): ?>
                  <div class="text-danger small">
                    <?php echo htmlspecialchars($errors['descriptions']); ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Tags -->
              <div class="mb-3">
                <label class="form-label" for="tags">Tags</label>
                <input
                  type="text"
                  class="form-control"
                  id="tags"
                  name="tags"
                  placeholder="e.g. portrait, digital, fantasy"
                  value="<?php echo htmlspecialchars($rawTags ?? ''); ?>"
                >
                <?php if (isset($errors['tags'])): ?>
                  <div class="text-danger small">
                    <?php echo htmlspecialchars($errors['tags']); ?>
                  </div>
                <?php endif; ?>
                <div class="form-text">Separate tags with commas.</div>
              </div>

              <!-- Existing Images -->
              <div class="mb-3">
                <label class="form-label">Current images</label>

                <?php if (!empty($images)): ?>
                  <div class="row g-3">
                    <?php foreach ($images as $img): ?>
                      <div class="col-6 col-md-4">
                        <div class="border rounded p-2 bg-white h-100">
                          <img
                            src="uploads/<?php echo htmlspecialchars($img['filepath']); ?>"
                            class="img-fluid rounded"
                            alt="Artwork image"
                          >
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-muted small">No images uploaded yet.</div>
                <?php endif; ?>
              </div>

              <!-- Upload New Images (optional) -->
              <div class="mb-3">
                <label class="form-label" for="images">Add more image(s)</label>
                <input
                  class="form-control"
                  type="file"
                  id="images"
                  name="images[]"
                  accept="image/*"
                  multiple
                >
                <?php if (isset($errors['images'])): ?>
                  <div class="text-danger small">
                    <?php echo htmlspecialchars($errors['images']); ?>
                  </div>
                <?php endif; ?>
                <div class="form-text">Optional: upload additional images.</div>
              </div>

              <!-- Buttons -->
              <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                  Save Changes
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                  Cancel
                </a>
              </div>

            </form>

          </div>
        </div>

      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>