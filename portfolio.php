<?php
// portfolio.php  (public page)
require_once "db.php";

// GET filter
$tag = trim($_GET['tag'] ?? '');

// Fetch artworks
try {

    if ($tag === '') {
        $sql = "
            SELECT 
                i.artID,
                i.title,
                i.descriptions,
                u.username,
                MIN(img.filepath) AS filepath,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
            FROM artworks i
            JOIN users u ON u.userID = i.userID
            LEFT JOIN artwork_images img ON img.artID = i.artID
            LEFT JOIN artwork_tags at ON at.artID = i.artID
            LEFT JOIN tags t ON t.tagID = at.tagID
            GROUP BY i.artID, i.title, i.descriptions, u.username
            ORDER BY i.created_at DESC
        ";
        $stmt = $conn->query($sql);

    } else {
        $sql = "
            SELECT 
                i.artID,
                i.title,
                i.descriptions,
                u.username,
                MIN(img.filepath) AS filepath,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
            FROM artworks i
            JOIN users u ON u.userID = i.userID
            LEFT JOIN artwork_images img ON img.artID = i.artID
            LEFT JOIN artwork_tags at ON at.artID = i.artID
            LEFT JOIN tags t ON t.tagID = at.tagID
            WHERE i.artID IN (
                SELECT at2.artID
                FROM artwork_tags at2
                JOIN tags t2 ON t2.tagID = at2.tagID
                WHERE t2.tag_name = ?
            )
            GROUP BY i.artID, i.title, i.descriptions, u.username
            ORDER BY i.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$tag]);
    }

    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $artworks = [];
    $dbError = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Public Portfolio | ArtArchive</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

<div class="container py-5 flex-grow-1">
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-9">

      <h2 class="fw-bold text-center mb-4">Public Portfolio</h2>

      <div class="text-center mb-4">
        <p class="text-muted">
          Want to create and upload your own artworks?
          <a href="register.php" class="fw-semibold">Register here</a>.
        </p>
      </div>

      <!-- Filter -->
      <form method="GET" class="mb-4">
        <div class="row g-2 justify-content-center">

          <div class="col-md-5">
            <input
              type="text"
              class="form-control"
              name="tag"
              placeholder="Filter by tag (example: portrait)"
              value="<?php echo htmlspecialchars($tag); ?>"
            >
          </div>

          <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filter</button>
          </div>

          <div class="col-auto">
            <a href="portfolio.php" class="btn btn-outline-secondary">Clear</a>
          </div>

        </div>
      </form>

      <?php if (!empty($dbError)): ?>
        <div class="alert alert-danger">
          <?php echo htmlspecialchars($dbError); ?>
        </div>
      <?php endif; ?>

      <div class="text-center mb-4">



      <!-- Artworks grid -->
      <div class="row g-4">

        <?php if (empty($artworks)): ?>
          <div class="col-12">
            <div class="text-center py-5">
              <h4 class="fw-semibold mb-3">No artworks found</h4>
              <p class="text-muted mb-0">
                <?php if ($tag !== ''): ?>
                  No artworks match the tag "<?php echo htmlspecialchars($tag); ?>".
                <?php else: ?>
                  There are no public artworks yet.
                <?php endif; ?>
              </p>
            </div>
          </div>

        <?php else: ?>
          <?php foreach ($artworks as $art): ?>
            <div class="col-12 col-sm-6 col-lg-4">
              <div class="card h-100">

                <?php if (!empty($art['filepath'])): ?>
                  <img
                    src="uploads/<?php echo htmlspecialchars($art['filepath']); ?>"
                    class="card-img-top"
                    alt="Artwork image"
                  >
                <?php else: ?>
                  <div class="bg-secondary-subtle d-flex align-items-center justify-content-center" style="height: 180px;">
                    <span class="text-muted small">No image</span>
                  </div>
                <?php endif; ?>

                <div class="card-body">
                  <h5 class="card-title mb-1">
                    <?php echo htmlspecialchars($art['title']); ?>
                  </h5>

                  <div class="text-muted small mb-2">
                    by <?php echo htmlspecialchars($art['username']); ?>
                  </div>

                  <p class="card-text text-muted small">
                    <?php echo htmlspecialchars($art['descriptions']); ?>
                  </p>

                  <?php if (!empty($art['tags'])): ?>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <?php foreach (explode(',', $art['tags']) as $t): ?>
                        <span class="badge bg-secondary">
                          <?php echo htmlspecialchars(trim($t)); ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="mt-2">
                      <span class="badge bg-light text-secondary border">no tags</span>
                    </div>
                  <?php endif; ?>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>