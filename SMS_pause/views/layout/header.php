<?php

function getPortalLink($sessionKey, $loggedInPage, $loginPage)
{
  return isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === true ? $loggedInPage : "$loginPage?destination=$loggedInPage";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once APP_HEAD_FILE; ?>
</head>

<header id="header">
  <nav class="navbar navbar-expand-lg py-0 mt-0">
    <div class="container-fluid">
      <div class="row w-100 d-flex align-items-center">
        <!-- Left Navigation Links -->
        <div class="col-3 d-flex justify-content-start">
          <ul class="navbar-nav flex-row gap-3">
            <li class="nav-item">
              <a class="nav-link btn <?php echo basename($_SERVER['SCRIPT_NAME']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo APP_ROOT_URL; ?>">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn ms-2 <?php echo basename($_SERVER['SCRIPT_NAME']) == 'about.php' ? 'active' : ''; ?>" href="<?php echo APP_VIEWS_URL . '/pages/about.php' ?>">About</a>
            </li>
          </ul>
        </div>

        <!-- Logo -->
        <div class="col-4 d-flex justify-content-start">
          <div class="header-logo py-0">
            <div>
              <a class="nav-brand" href="<?php echo APP_ROOT_URL ?>">
                <img class="border-0 mh-50 mw-50 co-header-transp-logo" src="<?php echo APP_IMAGES_URL . '/e-logo.png' ?>" alt="CyberAfridi Logo">
              </a>
            </div>
          </div>
        </div>

        <!-- Right User Portals Links -->
        <div class="col-5 d-flex justify-content-end">
          <ul class="navbar-nav">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == true): ?>
              <!-- Show Logout button if user is logged in -->
              <li class="nav-item">
                <a class="btn-primary btn me-2" href="<?php echo APP_AUTH_URL . '/logout.php'; ?>">Logout</a>
              </li>
            <?php else: ?>
              <!-- Show Login button only if not on login.php -->
              <?php if ($currentPage !== 'login.php'): ?>
                <li class="nav-item">
                  <a class="btn-primary btn me-2" href="<?php echo APP_AUTH_URL . '/login.php'; ?>">Login</a>
                </li>
              <?php endif; ?>

              <!-- Show Register button only if not on register.php -->
              <?php if ($currentPage !== 'register.php'): ?>
                <li class="nav-item">
                  <a class="btn-primary btn me-2" href="<?php echo APP_AUTH_URL . '/register.php'; ?>">Register</a>
                </li>
              <?php endif; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</header>
<hr>