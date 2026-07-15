<?php
// includes/header.php – must be included AFTER session_start() + requireAuth()
$user       = currentUser();
$theme      = $user['theme'] ?? 'dark';
$appName    = appName();
$initials   = strtoupper(substr($user['username'] ?? 'U', 0, 1));
$csrfMeta   = csrfToken();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Page titles map
$pageTitles = [
    'dashboard'          => 'Dashboard',
    'clienti'            => 'Clienti',
    'cliente-dettaglio'  => 'Dettaglio Cliente',
    'insegnanti'         => 'Insegnanti',
    'prenotazioni'       => 'Prenotazioni',
    'calendario'         => 'Calendario',
    'pacchetti'          => 'Pacchetti',
    'acquisti'           => 'Acquisti',
    'strumenti'          => 'Strumenti',
    'report'             => 'Report',
    'impostazioni'       => 'Impostazioni',
    'notifiche'          => 'Notifiche',
    'backup'             => 'Backup',
    'import-xml'         => 'Importa XML',
];
$pageTitle = $pageTitles[$currentPage] ?? 'EasyBooking';
?>
<!DOCTYPE html>
<html lang="it" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrfMeta ?>">
    <title><?= htmlspecialchars($pageTitle) ?> – <?= htmlspecialchars($appName) ?></title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- FullCalendar (only load on calendar page) -->
    <?php if ($currentPage === 'calendario'): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
    <?php endif; ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Theme CSS (loaded after style.css so theme variables override defaults) -->
    <link id="theme-dark-css"  rel="stylesheet" href="assets/css/dark-theme.css"  <?= $theme !== 'dark'  ? 'disabled' : '' ?>>
    <link id="theme-light-css" rel="stylesheet" href="assets/css/light-theme.css" <?= $theme !== 'light' ? 'disabled' : '' ?>>
</head>
<body data-theme="<?= $theme ?>">
<div class="app-wrapper">

<!-- ── Sidebar Overlay (mobile) ────────────────────────────── -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<!-- ── Sidebar ──────────────────────────────────────────────── -->
<aside id="sidebar" class="sidebar" aria-label="Navigazione principale">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-music"></i></div>
        <div>
            <div class="logo-text"><?= htmlspecialchars($appName) ?></div>
            <span class="logo-sub">Scuola di Musica</span>
        </div>
    </div>

    <nav>
        <div class="nav-section">
            <div class="nav-section-label">Principale</div>
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
            </a>
            <a href="clienti.php" class="nav-link <?= $currentPage === 'clienti' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-users"></i></span> Clienti
            </a>
            <a href="insegnanti.php" class="nav-link <?= $currentPage === 'insegnanti' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></span> Insegnanti
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-label">Lezioni</div>
            <a href="prenotazioni.php" class="nav-link <?= $currentPage === 'prenotazioni' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span> Prenotazioni
            </a>
            <a href="calendario.php" class="nav-link <?= $currentPage === 'calendario' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span> Calendario
            </a>
            <a href="pacchetti.php" class="nav-link <?= $currentPage === 'pacchetti' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-box-open"></i></span> Pacchetti
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-label">Gestione</div>
            <a href="acquisti.php" class="nav-link <?= $currentPage === 'acquisti' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-shopping-cart"></i></span> Acquisti
            </a>
            <a href="strumenti.php" class="nav-link <?= $currentPage === 'strumenti' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-guitar"></i></span> Strumenti
            </a>
            <a href="report.php" class="nav-link <?= $currentPage === 'report' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span> Report
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-label">Sistema</div>
            <a href="impostazioni.php" class="nav-link <?= $currentPage === 'impostazioni' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-cog"></i></span> Impostazioni
            </a>
            <a href="notifiche.php" class="nav-link <?= $currentPage === 'notifiche' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-bell"></i></span> Notifiche
            </a>
            <a href="backup.php" class="nav-link <?= $currentPage === 'backup' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-database"></i></span> Backup
            </a>
            <?php if ($user['role'] === 'admin'): ?>
            <a href="import-xml.php" class="nav-link <?= $currentPage === 'import-xml' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-file-import"></i></span> Importa XML
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $initials ?></div>
            <div>
                <div class="sidebar-username"><?= htmlspecialchars($user['username']) ?></div>
                <div class="sidebar-role"><?= $user['role'] === 'admin' ? 'Amministratore' : 'Utente' ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- ── Top Navbar ────────────────────────────────────────────── -->
<header class="top-navbar">
    <button id="hamburger-btn" class="navbar-btn hamburger-btn" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>
    <h1 class="navbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
    <div class="navbar-actions">
        <button id="theme-toggle" class="navbar-btn" title="Cambia tema" aria-label="Cambia tema">
            <i id="theme-toggle-icon" class="fas <?= $theme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
        </button>
        <a href="backup.php" class="navbar-btn" title="Backup" aria-label="Backup">
            <i class="fas fa-database"></i>
        </a>
        <a href="logout.php" class="navbar-btn" title="Esci" aria-label="Esci" onclick="return confirm('Vuoi disconnetterti?')">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</header>

<!-- ── Main Content ──────────────────────────────────────────── -->
<main class="main-content">
<?= renderFlashMessages() ?>
