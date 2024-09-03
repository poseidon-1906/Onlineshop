<?php require_once('header.php'); ?>

<?php
// Preventing direct access
if (!isset($_REQUEST['id'])) {
    header('location: logout.php');
    exit;
}

$mcat_id = $_REQUEST['id'];

// Check if the mid category ID is valid
$statement = $pdo->prepare("SELECT * FROM tbl_mid_category WHERE mcat_id=?");
$statement->execute([$mcat_id]);
if ($statement->rowCount() == 0) {
    header('location: logout.php');
    exit;
}

// Function to delete products and associated records
function deleteProductsAndPhotos($pdo, $product_ids) {
    foreach ($product_ids as $p_id) {
        // Get featured photo and delete
        $statement = $pdo->prepare("SELECT p_featured_photo FROM tbl_product WHERE p_id=?");
        $statement->execute([$p_id]);
        if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if (file_exists('../assets/uploads/' . $row['p_featured_photo'])) {
                unlink('../assets/uploads/' . $row['p_featured_photo']);
            }
        }

        // Get other photos and delete
        $statement = $pdo->prepare("SELECT photo FROM tbl_product_photo WHERE p_id=?");
        $statement->execute([$p_id]);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if (file_exists('../assets/uploads/product_photos/' . $row['photo'])) {
                unlink('../assets/uploads/product_photos/' . $row['photo']);
            }
        }

        // Delete product and associated records
        $tables = ['tbl_product', 'tbl_product_photo', 'tbl_product_size', 'tbl_product_color', 'tbl_rating'];
        foreach ($tables as $table) {
            $statement = $pdo->prepare("DELETE FROM $table WHERE p_id=?");
            $statement->execute([$p_id]);
        }

        // Delete payments and orders
        $statement = $pdo->prepare("SELECT payment_id FROM tbl_order WHERE product_id=?");
        $statement->execute([$p_id]);
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $statement1 = $pdo->prepare("DELETE FROM tbl_payment WHERE payment_id=?");
            $statement1->execute([$row['payment_id']]);
        }

        $statement = $pdo->prepare("DELETE FROM tbl_order WHERE product_id=?");
        $statement->execute([$p_id]);
    }
}

// Get end category IDs
$statement = $pdo->prepare("SELECT ecat_id FROM tbl_end_category WHERE mcat_id=?");
$statement->execute([$mcat_id]);
$ecat_ids = $statement->fetchAll(PDO::FETCH_COLUMN);

// Initialize product IDs array
$p_ids = [];

if (!empty($ecat_ids)) {
    foreach ($ecat_ids as $ecat_id) {
        $statement = $pdo->prepare("SELECT p_id FROM tbl_product WHERE ecat_id=?");
        $statement->execute([$ecat_id]);
        $product_ids = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($product_ids)) {
            $p_ids = array_merge($p_ids, $product_ids);
        }

        // Delete end categories
        $statement = $pdo->prepare("DELETE FROM tbl_end_category WHERE ecat_id=?");
        $statement->execute([$ecat_id]);
    }

    // Delete products and associated records
    if (!empty($p_ids)) {
        deleteProductsAndPhotos($pdo, $p_ids);
    }
}

// Delete mid category
$statement = $pdo->prepare("DELETE FROM tbl_mid_category WHERE mcat_id=?");
$statement->execute([$mcat_id]);

header('location: mid-category.php');
?>