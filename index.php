<?php
session_start();
require 'db.php';

$viewCarId = isset($_GET['view']) ? (int)$_GET['view'] : null;

if ($viewCarId) {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE car_id = ?");
    $stmt->bind_param("i", $viewCarId);
    $stmt->execute();
    $car = $stmt->get_result()->fetch_assoc();

    $imgStmt = $conn->prepare("SELECT * FROM car_images WHERE car_id = ?");
    $imgStmt->bind_param("i", $viewCarId);
    $imgStmt->execute();
    $carImages = $imgStmt->get_result();
} else {
    $search = $_GET['search'] ?? '';
    $model = $_GET['model'] ?? '';
    $fuel = $_GET['fuel'] ?? '';
    $trans = $_GET['trans'] ?? '';
    $seater = $_GET['seater'] ?? '';
    $terrain = $_GET['terrain'] ?? '';
    $luxury = $_GET['luxury'] ?? '';

    $query = "SELECT * FROM cars WHERE quantity > 0";
    if ($search) $query .= " AND (brand LIKE '%$search%' OR model LIKE '%$search%')";
    if ($model) $query .= " AND model = '$model'";
    if ($fuel) $query .= " AND fuel_type = '$fuel'";
    if ($trans) $query .= " AND transmission = '$trans'";
    if ($seater) $query .= " AND seater = '$seater'";
    if ($terrain) $query .= " AND terrain = '$terrain'";
    if ($luxury !== '') $query .= " AND luxury = '$luxury'";
    $query .= " ORDER BY created_at DESC";

    $cars = $conn->query($query);
}

$userLoggedIn = isset($_SESSION['name']);
$userName = $_SESSION['name'] ?? '';
$userType = $_SESSION['user_type'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BroCar Rental</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <style>
    * {margin: 0; padding: 0; box-sizing: border-box;}
    body {font-family: 'Segoe UI', sans-serif; background: #fff; color: #333;}

    .navbar {
      background:rgb(3, 27, 23);
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .navbar .logo {color: white; font-weight: bold; font-size: 22px;}
    .nav-links {list-style: none; display: flex; gap: 20px;}
    .nav-links a {
      color: white; text-decoration: none; font-weight: 500;
    }
    .nav-links a:hover {color: #00cc66;}

    .profile-dropdown {
      position: relative;
      display: inline-block;
    }

    .profile-btn {
      background: none;
      border: none;
      color: white;
      font-weight: 600;
      cursor: pointer;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      background-color: white;
      min-width: 140px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      z-index: 99;
      border-radius: 6px;
      overflow: hidden;
    }

    .dropdown-menu a {
      display: block;
      color: #333;
      padding: 10px 14px;
      text-decoration: none;
    }

    .dropdown-menu a:hover {
      background-color: #f0f0f0;
    }

    .profile-dropdown:hover .dropdown-menu {
      display: block;
    }

    .hero {
      background: url('images/navbg.jpg') center/cover no-repeat;
      height: 60vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      padding: 20px;
    }
    .hero h1 {
      font-size: 2.8rem;
    }

    .search-bar {
      max-width: 700px;
      margin: 30px auto;
      display: flex;
      gap: 10px;
    }

    .search-bar input {
      flex: 1;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .search-bar button, .filter-toggle {
      padding: 10px 20px;
      background: #00cc44;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
    }

    .filter-container {
      max-width: 700px;
      margin: 0 auto;
      padding: 10px;
    }

    .filter-form {
      margin-top: 10px;
      display: none;
      background: #f8f8f8;
      padding: 15px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }

    .filter-form select {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    .mostbooked-badge {
     margin-left: 40%;
  display: inline-block;
  
  padding: 10px 25px;
  background: linear-gradient(135deg, #009688, #004d40);
  color: white;
  font-size: 24px;
  font-weight: bold;
  border-radius: 30px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 1px;
  transition: 0.3s ease;
}
.mostbooked-badge:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 12px rgba(0,0,0,0.3);
}

    .cars-container {
      max-width: 1100px;
      margin: 40px auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      padding: 0 20px;
    }

    .car-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: 0.3s;
    }
    .car-card img {
      width: 100%;
      height: 180px;
      object-fit: contain;
      background-color: #f0f0f0;
      border-radius: 8px;
      display: block;
      margin: 0 auto;
    }

    .car-card .car-content {
      padding: 15px;
    }
    .car-card h3 {
      font-size: 1.2rem;
      color: #004d40;
    }
    .car-card .price {
      margin-top: 10px;
      font-weight: 700;
      color: #00cc44;
    }
    .details-btn {
      display: inline-block;
      margin-top: 10px;
      background: #00796b;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
    }

    .car-details {
      max-width: 800px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .swiper {
      width: 100%;
      height: 300px;
      margin-bottom: 20px;
      border-radius: 10px;
      overflow: hidden;
    }

    .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .car-details h2 {
      color: #004d40;
      margin-bottom: 20px;
    }

    .car-details p {
      margin: 10px 0;
    }

    .btn-back {
      display: inline-block;
      margin-top: 20px;
      background: #00796b;
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }
    .booking-btn{
    height: 30%;
    width: 20%;
      margin-top:0%;
      margin-left:50%;
      display: inline-block;
      background:rgb(27, 55, 52);
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;

    }
    footer {
      text-align: center;
      padding: 16px;
      background: #222;
      color: #ccc;
      margin-top: 60px;
    }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="logo">BroCar Rental</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="#">Cars</a></li>
      <li><a href="#">Contact</a></li>
    </ul>
    <div class="profile">
      <?php if ($userLoggedIn): ?>
        <div class="profile-dropdown">
          <button class="profile-btn">👤 <?= htmlspecialchars($userName) ?> ▼</button>
          <div class="dropdown-menu">
            <a href="profile.php">View Profile</a>
            <a href="logout.php" style="color: #ff4d4d;">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <button><a href="login.php" style="text-decoration:none; color:white;">Login</a> | <a href="signup.php" style="text-decoration:none; color:white;">Signup</a></button>
      <?php endif; ?>
    </div>
  </nav>

  <section class="hero">
    <div>
      <h1>Rent Your Dream Car</h1>
      <p>Explore top cars with ease</p>
    </div>
  </section>

  <?php if (!$viewCarId): ?>
    <div class="search-bar">
      <form method="GET" style="flex:1; display:flex; gap:10px;">
        <input type="text" name="search" placeholder="Search brand/model..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit">Search</button>
      </form>
      <button class="filter-toggle" onclick="toggleFilters()">⚙️ Filter</button>
    </div>

    <div class="filter-container">
      <form method="GET" id="filterForm" class="filter-form">
        <select name="model">
          <option value="">-- Select Model --</option>
          <option value="SUV" <?= ($model ?? '') === 'SUV' ? 'selected' : '' ?>>SUV</option>
          <option value="Sedan" <?= ($model ?? '') === 'Sedan' ? 'selected' : '' ?>>Sedan</option>
        </select>

        <select name="fuel">
          <option value="">-- Fuel Type --</option>
          <option value="Petrol" <?= ($fuel === 'Petrol') ? 'selected' : '' ?>>Petrol</option>
          <option value="Diesel" <?= ($fuel === 'Diesel') ? 'selected' : '' ?>>Diesel</option>
        </select>

        <select name="trans">
          <option value="">-- Transmission --</option>
          <option value="Manual" <?= ($trans === 'Manual') ? 'selected' : '' ?>>Manual</option>
          <option value="Automatic" <?= ($trans === 'Automatic') ? 'selected' : '' ?>>Automatic</option>
        </select>

        <select name="seater">
          <option value="">-- Seater --</option>
          <option value="4" <?= ($seater === '4') ? 'selected' : '' ?>>4</option>
          <option value="7" <?= ($seater === '7') ? 'selected' : '' ?>>7</option>
        </select>

        <select name="terrain">
          <option value="">-- Terrain --</option>
          <option value="City" <?= ($terrain === 'City') ? 'selected' : '' ?>>City</option>
          <option value="Off-road" <?= ($terrain === 'Off-road') ? 'selected' : '' ?>>Off-road</option>
        </select>

        <select name="luxury">
          <option value="">-- Luxury --</option>
          <option value="1" <?= ($luxury === '1') ? 'selected' : '' ?>>Yes</option>
          <option value="0" <?= ($luxury === '0') ? 'selected' : '' ?>>No</option>
        </select>

        <button type="submit" style="margin-top: 10px;">Apply Filters</button>
      </form>
    </div>
  <?php endif; ?>
  <h2><span class="mostbooked-badge">🚗 Featured Cars</span></h2>
  <main>
    <?php if ($viewCarId && $car): ?>
      <div class="car-details">
        <h2><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h2>

        <?php if ($carImages && $carImages->num_rows > 0): ?>
          <div class="swiper">
            <div class="swiper-wrapper">
              <?php while ($img = $carImages->fetch_assoc()): ?>
                <div class="swiper-slide">
                  <img src="images/<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['label']) ?>">
                </div>
              <?php endwhile; ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
          </div>
        <?php else: ?>
          <img src="images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['model']) ?>" style="width:100%; height:300px; object-fit:cover; border-radius:10px; margin-bottom:20px;">
        <?php endif; ?>

        <p><strong>Price:</strong> Rs <?= number_format($car['price_per_day']) ?> / day</p>
        <p><strong>Fuel:</strong> <?= htmlspecialchars($car['fuel_type']) ?></p>
        <p><strong>Transmission:</strong> <?= htmlspecialchars($car['transmission']) ?></p>
        <p><strong>Seater:</strong> <?= htmlspecialchars($car['seater']) ?></p>
        <p><strong>Luxury:</strong> <?= ($car['luxury'] == 1) ? 'Yes' : 'No' ?></p>
        <p><strong>Terrain:</strong> <?= htmlspecialchars($car['terrain']) ?></p>
        <p><strong>Details:</strong><br><?= nl2br(htmlspecialchars($car['details'])) ?></p>

        <a href="index.php" class="btn-back">&larr; Back to All Cars</a>
        <button class="booking-btn"><a href"booking.php">Book Now</a></button>
      </div>
    <?php elseif (!$viewCarId && isset($cars)): ?>
      <div class="cars-container">
        <?php while ($car = $cars->fetch_assoc()): ?>
          <div class="car-card">
            <img src="images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['model']) ?>">
            <div class="car-content">
              <h3><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></h3>
              <div class="price">Rs <?= number_format($car['price_per_day']) ?>/day</div>
              <a href="index.php?view=<?= $car['car_id'] ?>" class="details-btn">See More</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p style="text-align:center; margin-top: 40px;">No cars found matching your filters.</p>
    <?php endif; ?>
  </main>

  <footer>&copy; <?= date('Y') ?> BroCar Rental. All rights reserved.</footer>

  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script>
    const swiper = new Swiper('.swiper', {
      loop: true,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      autoplay: {
        delay: 3000,
        disableOnInteraction: false
      }
    });

    function toggleFilters() {
      const filterForm = document.getElementById('filterForm');
      filterForm.style.display = filterForm.style.display === 'block' ? 'none' : 'block';
    }
  </script>
</body>
</html>
