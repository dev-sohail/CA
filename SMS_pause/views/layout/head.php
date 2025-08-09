<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">

<!-- ✅ Tailwind CSS (with custom config for tw- prefix and no preflight to avoid conflict with Bootstrap) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    prefix: "tw-",
    corePlugins: {
      preflight: false,
    },
  };
</script>

<!-- ✅ Your Custom Styles (should come after Tailwind to override if needed) -->
<link rel="stylesheet" href="<?php echo APP_PUBLIC_URL . '/css/custom-styles.css'; ?>">

<!-- ✅ Bootstrap 5.3 (CDN - latest stable) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">

<!-- ✅ Optional: If you have your own Bootstrap overrides -->
<link rel="stylesheet" href="<?php echo APP_PUBLIC_URL . '/bootstrap/bootstrap.css'; ?>">

<!-- ✅ FontAwesome 6 Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">

<!-- ✅ Slick Carousel (Only include if you're using it) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">

<!-- ✅ Swiper Carousel (Recommended modern alternative) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<!-- ✅ FullCalendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">

<!-- ✅ DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- ✅ Tippy.js for Tooltips -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tippy.js@6.3.7/dist/tippy.css">

<!-- ✅ Custom Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Borsok&display=swap" rel="stylesheet">


<?php
    // Define a default title
    $title = "CA SMS";

    // Get the name of the current script
    $currentPage = basename($_SERVER['PHP_SELF']);

    // Set the title based on the current page
    switch ($currentPage) {
        case 'index.php':
            $title = "School Management System: CyberAfridi";
            break;
        case 'login.php':
            $title = "Login Page";
            break;
        case 'register.php':
            $title = "Register Page";
            break;
        case 'about.php':
            $title = "About Us";
            break;
        default:
            // $title remains as the default value
            break;
    }
?>
<title>
    <?php echo htmlspecialchars($title); ?>
</title>