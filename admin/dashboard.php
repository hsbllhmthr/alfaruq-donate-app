<?php
session_start();

// Proteksi Session Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db-connect.php';

// Parameter query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$campaignFilter = isset($_GET['campaign']) ? trim($_GET['campaign']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0) $limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page <= 0) $page = 1;

$offset = ($page - 1) * $limit;

// Ambil statistik ringkasan
try {
    // Total terverifikasi (SUCCESS)
    $stmtSuccess = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total, COUNT(id) as count FROM donations WHERE status = 'SUCCESS'");
    $statsSuccess = $stmtSuccess->fetch();

    // Total pending (PENDING)
    $stmtPending = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total, COUNT(id) as count FROM donations WHERE status = 'PENDING'");
    $statsPending = $stmtPending->fetch();

    // Ambil daftar program (campaignId) unik untuk filter
    $stmtCampaigns = $pdo->query("SELECT DISTINCT campaignId FROM donations WHERE campaignId IS NOT NULL AND campaignId != '' ORDER BY campaignId ASC");
    $campaignsList = $stmtCampaigns->fetchAll(PDO::FETCH_COLUMN);

    // Bangun query filter
    $whereClauses = [];
    $params = [];

    if ($search !== '') {
        $whereClauses[] = "(id LIKE :search OR name LIKE :search OR contact LIKE :search OR prayer LIKE :search OR amount LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if ($statusFilter !== '') {
        $whereClauses[] = "status = :status";
        $params['status'] = $statusFilter;
    }

    if ($campaignFilter !== '') {
        $whereClauses[] = "campaignId = :campaign";
        $params['campaign'] = $campaignFilter;
    }

    $whereSql = '';
    if (count($whereClauses) > 0) {
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    // Hitung total matching records
    $countSql = "SELECT COUNT(*) FROM donations $whereSql";
    $stmtCount = $pdo->prepare($countSql);
    foreach ($params as $key => $val) {
        $stmtCount->bindValue(':' . $key, $val);
    }
    $stmtCount->execute();
    $totalRecords = $stmtCount->fetchColumn();

    $totalPages = ceil($totalRecords / $limit);
    if ($totalPages < 1) $totalPages = 1;
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    // Ambil daftar donasi terfilter & terpaginasi
    $listSql = "SELECT * FROM donations $whereSql ORDER BY createdAt DESC LIMIT :limit OFFSET :offset";
    $stmtList = $pdo->prepare($listSql);
    foreach ($params as $key => $val) {
        $stmtList->bindValue(':' . $key, $val);
    }
    $stmtList->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtList->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtList->execute();
    $donations = $stmtList->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan database: " . $e->getMessage());
}

function formatRupiah($angka) {
    return 'Rp' . number_format($angka, 0, ',', '.');
}

function getPageUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alfaruq Donate</title>
    <!-- TailAdmin Compiled CSS -->
    <link rel="stylesheet" href="../tailadmin-free-tailwind-dashboard-template-main/build/style.css">
    <!-- TailAdmin Compiled JavaScript (includes AlpineJS) -->
    <script defer src="../tailadmin-free-tailwind-dashboard-template-main/build/bundle.js"></script>
</head>
<body
    x-data="{ page: 'dashboard', 'loaded': false, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'userDropdownOpen': false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
    class="bg-gray-50 dark:bg-gray-900 font-sans text-gray-700 antialiased"
>

    <!-- Page Wrapper -->
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar Start -->
        <aside
            :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
            class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0"
            @click.away="sidebarToggle = false"
        >
            <!-- Sidebar Header -->
            <div
                :class="sidebarToggle ? 'justify-center' : 'justify-between'"
                class="flex items-center gap-2 pt-8 sidebar-header pb-7 border-b border-gray-100 dark:border-gray-800"
            >
            </div>

            <!-- Sidebar Navigation -->
            <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
                <nav class="mt-5 px-2 py-4">
                    <div>
                        <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                            <span :class="sidebarToggle ? 'lg:hidden' : ''">Menu</span>
                        </h3>
                        <ul class="flex flex-col gap-2">
                            <li>
                                <a
                                    href="dashboard.php"
                                    class="menu-item group"
                                    :class="page === 'dashboard' ? 'menu-item-active' : 'menu-item-inactive'"
                                >
                                    <svg
                                        :class="page === 'dashboard' ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            clip-rule="evenodd"
                                            d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z"
                                            fill="currentColor"
                                        />
                                    </svg>
                                    <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </aside>
        <!-- Sidebar End -->

        <!-- Content Area Start -->
        <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900">
            
            <!-- Header Start -->
            <header class="sticky top-0 z-99 flex w-full bg-white dark:bg-gray-dark py-4 px-6 lg:px-8 border-b border-gray-200 dark:border-gray-800">
                <div class="flex flex-grow items-center justify-between lg:justify-end py-1">
                    <!-- Mobile Hamburger -->
                    <button class="lg:hidden text-gray-800 mr-4 dark:text-white" @click="sidebarToggle = !sidebarToggle">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- User Area -->
                    <div class="flex items-center gap-3 relative" @click="userDropdownOpen = !userDropdownOpen">
                        <div class="text-right cursor-pointer">
                            <span class="block text-theme-sm font-medium text-gray-800 dark:text-white">
                                <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                            </span>
                            <span class="block text-theme-xs text-gray-400">Administrator</span>
                        </div>
                        
                        <!-- Avatar -->
                        <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center font-bold text-brand-600 cursor-pointer dark:bg-brand-500/10 dark:text-brand-500">
                            <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 2)); ?>
                        </span>

                        <!-- User Dropdown Menu -->
                        <div x-show="userDropdownOpen" 
                             @click.away="userDropdownOpen = false" 
                             class="absolute right-0 top-12 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-50 dark:bg-gray-dark dark:border-gray-800">
                            <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                                Keluar (Logout)
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Header End -->

            <!-- Main Content Start -->
            <main>
                <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6 space-y-5 sm:space-y-6">
                    
                    <!-- Title -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                            Ringkasan Statistik Donasi
                        </h2>
                    </div>

                    <!-- Stats Cards Grid -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 xl:grid-cols-2">
                        
                        <!-- Card Success -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white">
                                        <?php echo formatRupiah($statsSuccess['total']); ?>
                                    </h4>
                                    <span class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">Donasi Terverifikasi (Sukses)</span>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <span class="text-theme-xs font-medium text-success-600 dark:text-success-500">
                                    <?php echo $statsSuccess['count']; ?> transaksi berhasil
                                </span>
                            </div>
                        </div>

                        <!-- Card Pending -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white">
                                        <?php echo formatRupiah($statsPending['total']); ?>
                                    </h4>
                                    <span class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">Donasi Pending (Verifikasi WA)</span>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-500">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <span class="text-theme-xs font-medium text-warning-600 dark:text-warning-500">
                                    <?php echo $statsPending['count']; ?> transaksi menunggu
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- Filter & Data Section -->
                    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                        
                        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                                Daftar Riwayat Transaksi Donatur
                            </h3>
                        </div>

                        <!-- Filter Form -->
                        <div class="bg-gray-50 border-b border-gray-100 p-5 dark:bg-white/[0.01] dark:border-gray-800">
                            <form method="GET" action="dashboard.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                
                                <div class="flex flex-col">
                                    <label class="mb-2 text-theme-xs font-semibold uppercase text-gray-700 dark:text-gray-400" for="search">Cari</label>
                                    <input type="text" id="search" name="search" placeholder="Cari nama, kontak, doa..." 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           class="w-full rounded-lg border border-gray-200 bg-white py-2 px-3 text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-white outline-none transition focus:border-brand-500">
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-theme-xs font-semibold uppercase text-gray-700 dark:text-gray-400" for="status">Status</label>
                                    <select id="status" name="status" 
                                            class="w-full rounded-lg border border-gray-200 bg-white py-2 px-3 text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-white outline-none transition focus:border-brand-500">
                                        <option value="">Semua Status</option>
                                        <option value="PENDING" <?php echo $statusFilter === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="SUCCESS" <?php echo $statusFilter === 'SUCCESS' ? 'selected' : ''; ?>>Sukses</option>
                                        <option value="FAILED" <?php echo $statusFilter === 'FAILED' ? 'selected' : ''; ?>>Batal</option>
                                    </select>
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-theme-xs font-semibold uppercase text-gray-700 dark:text-gray-400" for="campaign">Program</label>
                                    <select id="campaign" name="campaign" 
                                            class="w-full rounded-lg border border-gray-200 bg-white py-2 px-3 text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-white outline-none transition focus:border-brand-500">
                                        <option value="">Semua Program</option>
                                        <?php foreach ($campaignsList as $camp): ?>
                                            <option value="<?php echo htmlspecialchars($camp); ?>" <?php echo $campaignFilter === $camp ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($camp); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-theme-xs font-semibold uppercase text-gray-700 dark:text-gray-400" for="limit">Tampilkan</label>
                                    <select id="limit" name="limit" 
                                            class="w-full rounded-lg border border-gray-200 bg-white py-2 px-3 text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-white outline-none transition focus:border-brand-500">
                                        <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10 baris</option>
                                        <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25 baris</option>
                                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 baris</option>
                                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 baris</option>
                                    </select>
                                </div>

                                <div class="md:col-span-4 flex gap-2 justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 py-2 px-6 font-medium text-white hover:bg-brand-700">
                                        Filter
                                    </button>
                                    <?php if ($search !== '' || $statusFilter !== '' || $campaignFilter !== '' || $limit !== 10): ?>
                                        <a href="dashboard.php" class="inline-flex items-center justify-center rounded-lg border border-gray-200 py-2 px-6 font-medium text-gray-700 hover:bg-gray-100 bg-white dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                            Reset
                                        </a>
                                    <?php endif; ?>
                                </div>

                            </form>
                        </div>

                        <!-- Data Table -->
                        <div class="max-w-full overflow-x-auto">
                            <?php if (count($donations) > 0): ?>
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">ID</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Tanggal</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nama Donatur</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nominal</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Program</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Kontak</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Doa / Dukungan</th>
                                            <th class="px-5 py-3 text-left font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</th>
                                            <th class="px-5 py-3 text-center font-medium text-gray-500 text-theme-xs dark:text-gray-400">Aksi Verifikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        <?php foreach ($donations as $row): ?>
                                            <tr id="row-<?php echo $row['id']; ?>" class="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                                <td class="px-5 py-4 text-theme-sm font-medium text-gray-800 dark:text-white">#<?php echo $row['id']; ?></td>
                                                <td class="px-5 py-4 text-theme-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    <?php echo date('d-m-Y H:i', strtotime($row['createdAt'])); ?>
                                                </td>
                                                <td class="px-5 py-4 text-theme-sm">
                                                    <span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90"><?php echo htmlspecialchars($row['name']); ?></span>
                                                    <?php if ($row['isAnonymous']): ?>
                                                        <span class="block text-theme-xs text-error-600 font-semibold dark:text-error-500">Anonim</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-4 text-theme-sm font-bold text-brand-600 dark:text-brand-500">
                                                    <?php echo formatRupiah($row['amount']); ?>
                                                </td>
                                                <td class="px-5 py-4 text-theme-sm">
                                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-theme-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                                        <?php echo htmlspecialchars($row['campaignId']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-5 py-4 text-theme-sm text-gray-500 dark:text-gray-400">
                                                    <?php echo htmlspecialchars($row['contact']); ?>
                                                </td>
                                                <td class="px-5 py-4 text-theme-xs text-gray-500 dark:text-gray-400 max-w-[200px] truncate" title="<?php echo htmlspecialchars($row['prayer'] ?? ''); ?>">
                                                    <?php echo $row['prayer'] ? htmlspecialchars($row['prayer']) : '-'; ?>
                                                </td>
                                                <td class="px-5 py-4 text-theme-sm status-cell">
                                                    <?php 
                                                        $status = strtoupper($row['status']);
                                                        if ($status === 'SUCCESS') {
                                                            echo '<span class="rounded-full bg-success-50 px-2.5 py-0.5 text-theme-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-500">Sukses</span>';
                                                        } elseif ($status === 'FAILED') {
                                                            echo '<span class="rounded-full bg-error-50 px-2.5 py-0.5 text-theme-xs font-medium text-error-700 dark:bg-error-500/15 dark:text-error-500">Batal</span>';
                                                        } else {
                                                            echo '<span class="rounded-full bg-warning-50 px-2.5 py-0.5 text-theme-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-500">Pending</span>';
                                                        }
                                                    ?>
                                                </td>
                                                <td class="px-5 py-4 text-theme-sm text-center">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <button onclick="updateStatus(<?php echo $row['id']; ?>, 'SUCCESS')" class="inline-flex items-center justify-center rounded-md bg-success-600 py-1.5 px-3 text-center text-xs font-medium text-white hover:bg-opacity-90">Setujui</button>
                                                        <button onclick="updateStatus(<?php echo $row['id']; ?>, 'FAILED')" class="inline-flex items-center justify-center rounded-md bg-error-600 py-1.5 px-3 text-center text-xs font-medium text-white hover:bg-opacity-90">Batalkan</button>
                                                        <button onclick="updateStatus(<?php echo $row['id']; ?>, 'PENDING')" class="inline-flex items-center justify-center rounded-md bg-warning-600 py-1.5 px-3 text-center text-xs font-medium text-white hover:bg-opacity-90">Pending</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                                    Belum ada transaksi donasi yang cocok dengan kriteria filter.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination Footer -->
                        <?php if ($totalPages > 1): ?>
                            <div class="flex flex-col md:flex-row items-center justify-between border-t border-gray-100 dark:border-gray-800 py-4 px-6 gap-4 bg-gray-50/50 dark:bg-white/[0.01]">
                                <div class="text-theme-sm font-medium text-gray-800 dark:text-white">
                                    Menampilkan <strong><?php echo min($offset + 1, $totalRecords); ?></strong> sampai <strong><?php echo min($offset + $limit, $totalRecords); ?></strong> dari <strong><?php echo $totalRecords; ?></strong> donasi
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <!-- Prev Link -->
                                    <a href="<?php echo getPageUrl($page - 1); ?>" 
                                       class="rounded-lg border border-gray-200 py-1.5 px-3 text-theme-sm font-medium hover:bg-gray-100 bg-white text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5 <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                                        &laquo; Sblm
                                    </a>

                                    <!-- Page Links -->
                                    <?php 
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<a href="' . getPageUrl(1) . '" class="rounded-lg border border-gray-200 py-1.5 px-3 text-theme-sm font-medium ' . ($page == 1 ? 'bg-brand-600 text-white' : 'hover:bg-gray-100 bg-white text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5') . '">1</a>';
                                        if ($startPage > 2) {
                                            echo '<span class="px-2 text-gray-500">...</span>';
                                        }
                                    }

                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        $activeClass = ($i === $page) ? 'bg-brand-600 text-white' : 'hover:bg-gray-100 bg-white text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5';
                                        echo '<a href="' . getPageUrl($i) . '" class="rounded-lg border border-gray-200 py-1.5 px-3 text-theme-sm font-medium ' . $activeClass . '">' . $i . '</a>';
                                    }

                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<span class="px-2 text-gray-500">...</span>';
                                        }
                                        echo '<a href="' . getPageUrl($totalPages) . '" class="rounded-lg border border-gray-200 py-1.5 px-3 text-theme-sm font-medium ' . ($page == $totalPages ? 'bg-brand-600 text-white' : 'hover:bg-gray-100 bg-white text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5') . '">' . $totalPages . '</a>';
                                    }
                                    ?>

                                    <!-- Next Link -->
                                    <a href="<?php echo getPageUrl($page + 1); ?>" 
                                       class="rounded-lg border border-gray-200 py-1.5 px-3 text-theme-sm font-medium hover:bg-gray-100 bg-white text-gray-800 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5 <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">
                                        Sldt &raquo;
                                    </a>
                                </div>
                            </div>
                        <?php elseif ($totalRecords > 0): ?>
                            <div class="border-t border-gray-100 dark:border-gray-800 py-4 px-6 bg-gray-50/50 dark:bg-white/[0.01]">
                                <div class="text-theme-sm font-medium text-gray-800 dark:text-white">
                                    Menampilkan <strong>1</strong> sampai <strong><?php echo $totalRecords; ?></strong> dari <strong><?php echo $totalRecords; ?></strong> donasi
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>
            </main>
            <!-- Main Content End -->

        </div>
        <!-- Content Area End -->
    </div>

    <!-- Toast Notification -->
    <div id="toastNotification" class="fixed bottom-5 right-5 z-99999 rounded-xl py-3 px-6 text-white shadow-lg transition-all duration-300 opacity-0 pointer-events-none"></div>

    <script>
        async function updateStatus(id, newStatus) {
            const row = document.getElementById(`row-${id}`);
            const statusCell = row.querySelector('.status-cell');
            
            try {
                const response = await fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id, status: newStatus })
                });
                
                const result = await response.json();
                
                if (response.ok && result.status === 'success') {
                    // Update tampilan badge status secara instan
                    if (newStatus === 'SUCCESS') {
                        statusCell.innerHTML = '<span class="rounded-full bg-success-50 px-2.5 py-0.5 text-theme-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-500">Sukses</span>';
                    } else if (newStatus === 'FAILED') {
                        statusCell.innerHTML = '<span class="rounded-full bg-error-50 px-2.5 py-0.5 text-theme-xs font-medium text-error-700 dark:bg-error-500/15 dark:text-error-500">Batal</span>';
                    } else {
                        statusCell.innerHTML = '<span class="rounded-full bg-warning-50 px-2.5 py-0.5 text-theme-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-500">Pending</span>';
                    }
                    
                    showToast(`Donasi #${id} diperbarui menjadi ${newStatus}`);
                    
                    // Reload statistik di bagian atas
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    showToast(result.message || 'Gagal mengubah status', true);
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal terhubung ke server.', true);
            }
        }

        function showToast(message, isError = false) {
            const toast = document.getElementById('toastNotification');
            toast.textContent = message;
            
            // TailAdmin styled toast classes
            if (isError) {
                toast.className = "fixed bottom-5 right-5 z-99999 rounded-xl py-3 px-6 text-white shadow-lg transition-all duration-300 bg-error-600 opacity-100";
            } else {
                toast.className = "fixed bottom-5 right-5 z-99999 rounded-xl py-3 px-6 text-white shadow-lg transition-all duration-300 bg-success-600 opacity-100";
            }
            
            setTimeout(() => {
                toast.className = "fixed bottom-5 right-5 z-99999 rounded-xl py-3 px-6 text-white shadow-lg transition-all duration-300 opacity-0 pointer-events-none";
            }, 3000);
        }
    </script>
</body>
</html>
