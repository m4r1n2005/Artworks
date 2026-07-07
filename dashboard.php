<?php
session_start();

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$artworks = [];

try {
    $sql = "
        SELECT 
            i.artID,
            i.title,
            i.descriptions,
            MIN(a.filepath) AS filepath,
            GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
        FROM artworks i
        LEFT JOIN artwork_images a USING (artID)
        LEFT JOIN artwork_tags at USING (artID)
        LEFT JOIN tags t ON t.tagID = at.tagID
        WHERE i.userID = ?
        GROUP BY i.artID, i.title, i.descriptions
        ORDER BY i.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['userID']]);
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | ArtArchive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

<div class="container py-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <h2 class="fw-bold mb-4 text-center">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </h2>

            <div class="row g-4">

                <?php if (empty($artworks)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <h4 class="fw-semibold mb-3">No artworks yet</h4>
                            <p class="text-muted mb-4">
                                Start building your portfolio by adding your first artwork.
                            </p>
                            <a href="create_artwork.php" class="btn btn-primary">
                                + Add Your First Artwork
                            </a>
                        </div>
                    </div>

                <?php else: ?>

                    <?php foreach ($artworks as $art): ?>
                        <div class="col-12 col-sm-6">
                            <div class="card h-100">

                                <img src="uploads/<?php echo htmlspecialchars($art['filepath']); ?>"
                                     class="card-img-top"
                                     alt="Artwork image">

                                <div class="card-body d-flex flex-column">

                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($art['title']); ?>
                                    </h5>

                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars($art['descriptions']); ?>
                                    </p>

                                    <?php if (!empty($art['tags'])): ?>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php foreach (explode(',', $art['tags']) as $tag): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars(trim($tag)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2">
                                            <span class="badge bg-light text-secondary border">no tags</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Buttons -->
                                    <div class="mt-auto pt-3 d-flex gap-2">

                                        <a href="edit_artwork.php?artID=<?php echo $art['artID']; ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                            Edit
                                        </a>

                                        <a href="delete_artwork.php?artID=<?php echo $art['artID']; ?>" class="btn btn-outline-danger btn-sm flex-fill" onclick="return confirm('Are you sure you want to delete this artwork?');">
                                            Delete
                                        </a>

                                    </div>

                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12">
                        <div class="text-center mt-2">
                            <a href="create_artwork.php" class="btn btn-primary">
                                + Add Artwork
                            </a>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

            <div class="text-center mt-5">
                <a href="logout.php" class="text-muted">Logout</a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
