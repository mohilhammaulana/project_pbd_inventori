<?php

require_once __DIR__ . '/../auth/cek_role.php';

$current_page = $current_page ?? 'dashboard';

$menus = [

    [
        'group' => 'MENU UTAMA',
        'items' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => '▦',
                'url' => 'dashboard.php',
                'roles' => ['Admin', 'Gudang', 'Kasir']
            ]
        ]
    ],

    [
        'group' => 'DATA MASTER',
        'items' => [
            [
                'key' => 'barang',
                'label' => 'Data Barang',
                'icon' => '▣',
                'url' => 'modules/barang/index.php',
                'roles' => ['Admin', 'Gudang', 'Kasir']
            ],
            [
                'key' => 'vendor',
                'label' => 'Data Vendor',
                'icon' => '▤',
                'url' => 'modules/vendor/index.php',
                'roles' => ['Admin', 'Gudang']
            ],
            [
                'key' => 'satuan',
                'label' => 'Data Satuan',
                'icon' => '▥',
                'url' => 'modules/satuan/index.php',
                'roles' => ['Admin', 'Gudang']
            ],
            [
                'key' => 'user',
                'label' => 'Data User',
                'icon' => '♙',
                'url' => 'modules/user/index.php',
                'roles' => ['Admin']
            ],
            [
                'key' => 'role',
                'label' => 'Data Role',
                'icon' => '◇',
                'url' => 'modules/role/index.php',
                'roles' => ['Admin']
            ],
            [
                'key' => 'margin',
                'label' => 'Margin Penjualan',
                'icon' => '%',
                'url' => 'modules/margin/index.php',
                'roles' => ['Admin']
            ]
        ]
    ],

    [
        'group' => 'TRANSAKSI',
        'items' => [
            [
                'key' => 'pengadaan',
                'label' => 'Pengadaan',
                'icon' => '＋',
                'url' => 'modules/pengadaan/index.php',
                'roles' => ['Admin', 'Gudang']
            ],
            [
                'key' => 'penerimaan',
                'label' => 'Penerimaan',
                'icon' => '↓',
                'url' => 'modules/penerimaan/index.php',
                'roles' => ['Admin', 'Gudang']
            ],
            [
                'key' => 'penjualan',
                'label' => 'Penjualan',
                'icon' => '↗',
                'url' => 'modules/penjualan/index.php',
                'roles' => ['Admin', 'Kasir']
            ],
            [
                'key' => 'retur',
                'label' => 'Retur Barang',
                'icon' => '↶',
                'url' => 'modules/retur/index.php',
                'roles' => ['Admin', 'Gudang']
            ]
        ]
    ],

    [
        'group' => 'LAPORAN',
        'items' => [
            [
                'key' => 'kartu_stok',
                'label' => 'Kartu Stok',
                'icon' => '☷',
                'url' => 'modules/kartu_stok/index.php',
                'roles' => ['Admin', 'Gudang']
            ]
        ]
    ]

];

?>

<aside class="sidebar" id="sidebar">

    <div class="brand">

        <div class="brand-logo">SF</div>

        <div>
            <h2>STOKFLOW</h2>
            <small>Inventory System</small>
        </div>

    </div>

    <nav class="sidebar-menu">

        <?php foreach ($menus as $group) : ?>

            <div class="menu-group">

                <div class="menu-label">
                    <?= htmlspecialchars($group['group']); ?>
                </div>

                <?php foreach ($group['items'] as $menu) : ?>

                    <?php if (punya_akses($menu['roles'])) : ?>

                        <?php
                        $tersedia = is_file(
                            __DIR__ . '/../' . $menu['url']
                        );
                        ?>

                        <?php if ($tersedia) : ?>

                            <a
                                href="/stokflow/<?= htmlspecialchars(
                                    $menu['url']
                                ); ?>"
                                class="menu-item <?= 
                                    $current_page === $menu['key']
                                    ? 'active' : '';
                                ?>"
                            >

                                <span class="menu-icon">
                                    <?= htmlspecialchars(
                                        $menu['icon']
                                    ); ?>
                                </span>

                                <?= htmlspecialchars(
                                    $menu['label']
                                ); ?>

                            </a>

                        <?php else : ?>

                            <div
                                class="menu-item disabled"
                                title="Modul belum dibuat"
                            >

                                <span class="menu-icon">
                                    <?= htmlspecialchars(
                                        $menu['icon']
                                    ); ?>
                                </span>

                                <?= htmlspecialchars(
                                    $menu['label']
                                ); ?>

                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    </nav>

    <div class="sidebar-bottom">

        <form
            action="/stokflow/auth/logout.php"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token']
                ); ?>"
            >

            <button type="submit" class="logout-button">
                Keluar / Logout
            </button>

        </form>

    </div>

</aside>