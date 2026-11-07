
<?php
$statusInfo = [
    'Available' => [
        'label' => 'Bàn trống',
        'class' => 'available',
        'icon' => '✓'
    ],
    'Occupied' => [
        'label' => 'Đang phục vụ',
        'class' => 'occupied',
        'icon' => '●'
    ],
    'Reserved' => [
        'label' => 'Đã đặt',
        'class' => 'reserved',
        'icon' => '◷'
    ],
    'Cleaning' => [
        'label' => 'Đang dọn',
        'class' => 'cleaning',
        'icon' => '✦'
    ]
];

$statistics = $statistics ?? [];
$tables = $tables ?? [];
$reservedTableIds = $reservedTableIds ?? [];
$groupedTables = [];

// Đồng bộ lịch đặt bàn vào trạng thái hiển thị
foreach ($tables as &$table) {
    $tableId = (int)($table['table_id'] ?? 0);

    if (
        $tableId > 0 &&
        $table['status'] === 'Available' &&
        in_array($tableId, $reservedTableIds, true)
    ) {
        $table['status'] = 'Reserved';
    }
}
unset($table);
$groupedTables = [];
foreach ($tables as $table) {
    $areaId = $table['area_id'];

    if (!isset($groupedTables[$areaId])) {
        $groupedTables[$areaId] = [
            'name' => $table['area_name'],
            'surcharge' => $table['surcharge_percent'],
            'tables' => []
        ];
    }

    if ($table['table_id'] !== null) {
        $groupedTables[$areaId]['tables'][] = $table;
    }
}

$totalAreas = count($groupedTables);

$totalTables = count($tables);

$totalAvailable = count(array_filter(
    $tables,
    fn($t) => $t['status'] === 'Available'
));

$totalOccupied = count(array_filter(
    $tables,
    fn($t) => $t['status'] === 'Occupied'
));

$totalReserved = count(array_filter(
    $tables,
    fn($t) => $t['status'] === 'Reserved'
));

$totalCleaning = count(array_filter(
    $tables,
    fn($t) => $t['status'] === 'Cleaning'
));
?>

<style>
/* =====================================================
   ROYALDINE 5S - PREMIUM RESTAURANT FLOOR PLAN
   Phạm vi CSS được giới hạn trong .rd-floorplan
===================================================== */

.rd-floorplan {
    --rd-bg: #111925;
    --rd-panel: #1a2737;
    --rd-panel-2: #202e40;
    --rd-border: #354458;
    --rd-gold: #d8b66c;
    --rd-gold-light: #f3dfaa;
    --rd-text: #f4f5f7;
    --rd-muted: #a4b0c1;
    --rd-green: #4dd6a0;
    --rd-red: #ff7d87;
    --rd-yellow: #efc45e;
    --rd-gray: #9aa7b7;

    box-sizing: border-box;
    margin: 0;
    padding: 28px;
    min-height: 700px;
    color: var(--rd-text);
    background:
        radial-gradient(
            ellipse at 90% 0%,
            #29374a 0%,
            transparent 38%
        ),
        var(--rd-bg);
    border-radius: 14px;
    font-family: "Segoe UI", Arial, sans-serif;
}

.rd-floorplan *,
.rd-floorplan *::before,
.rd-floorplan *::after {
    box-sizing: border-box;
}

.rd-floorplan button,
.rd-floorplan input {
    font: inherit;
}

.rd-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.rd-eyebrow {
    color: var(--rd-gold);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.rd-heading {
    margin: 12px 0 9px;
    color: var(--rd-gold-light);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(27px, 3vw, 39px);
    font-weight: 500;
    letter-spacing: -.5px;
}

.rd-subtitle {
    max-width: 650px;
    margin: 0;
    color: var(--rd-muted);
    font-size: 14px;
    line-height: 1.8;
}

.rd-primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 18px;
    border: 1px solid #e7cd8c;
    border-radius: 9px;
    color: #171b21 !important;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none !important;
    transition: .2s ease;
}

.rd-primary-btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.08);
}

.rd-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 25px;
}

.rd-stat {
    position: relative;
    overflow: hidden;
    min-width: 0;
    padding: 19px;
    border: 1px solid var(--rd-border);
    border-radius: 13px;
    background: linear-gradient(145deg, #202e40, #192434);
    transition: border-color .2s, transform .2s;
}

.rd-stat:hover {
    transform: translateY(-2px);
    border-color: #d8b66c70;
}

.rd-stat::after {
    position: absolute;
    right: -27px;
    bottom: -34px;
    width: 75px;
    height: 75px;
    border: 1px solid #d8b66c25;
    border-radius: 50%;
    content: "";
}

.rd-stat-label {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--rd-muted);
    font-size: 12px;
    line-height: 1.5;
}

.rd-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 31px;
    height: 31px;
    border: 1px solid #d8b66c50;
    border-radius: 8px;
    color: var(--rd-gold);
    font-size: 15px;
}

.rd-stat-value {
    margin-top: 15px;
    color: var(--rd-gold-light);
    font-size: 31px;
    font-weight: 700;
    line-height: 1.1;
}

.rd-stat-caption {
    margin-top: 9px;
    color: #8997aa;
    font-size: 11px;
}

.rd-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.rd-legend {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 17px;
}

.rd-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #c5ceda;
    font-size: 12px;
}

.rd-dot {
    width: 9px;
    height: 9px;
    flex-shrink: 0;
    border-radius: 50%;
}

.rd-dot.available { background: var(--rd-green); }
.rd-dot.occupied { background: var(--rd-red); }
.rd-dot.reserved { background: var(--rd-yellow); }
.rd-dot.cleaning { background: var(--rd-gray); }

.rd-search-wrap {
    position: relative;
    width: min(100%, 290px);
}

.rd-search-icon {
    position: absolute;
    top: 50%;
    left: 13px;
    color: var(--rd-muted);
    transform: translateY(-50%);
    pointer-events: none;
}

.rd-search {
    width: 100%;
    padding: 12px 14px 12px 36px;
    border: 1px solid var(--rd-border);
    border-radius: 9px;
    outline: none;
    color: var(--rd-text);
    background: #182333;
    font-size: 12px;
}

.rd-search:focus {
    border-color: var(--rd-gold);
    box-shadow: 0 0 0 3px #d8b66c14;
}

.rd-map-shell {
    position: relative;
    overflow: hidden;
    padding: 20px;
    border: 1px solid #465368;
    border-radius: 15px;
    background-color: #111a27;
    background-image:
        linear-gradient(#ffffff06 1px, transparent 1px),
        linear-gradient(90deg, #ffffff06 1px, transparent 1px);
    background-size: 24px 24px;
    box-shadow: inset 0 0 45px #00000025;
}

.rd-map-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid #465267;
}

.rd-map-caption {
    color: var(--rd-gold);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
}

.rd-map-scale {
    color: #8997aa;
    font-size: 10px;
    letter-spacing: 1px;
}

.rd-map-entrance {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: min(270px, 100%);
    margin: 0 auto 20px;
    padding: 12px;
    border: 1px dashed #b89b5e;
    border-radius: 8px;
    color: #dfca94;
    background: #202c3b;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
}

.rd-door {
    display: inline-block;
    width: 20px;
    height: 11px;
    border: 2px solid var(--rd-gold);
    border-bottom: 0;
    border-radius: 10px 10px 0 0;
}

.rd-map-service {
    display: grid;
    grid-template-columns: minmax(130px, .75fr) minmax(0, 2fr);
    gap: 14px;
    margin-bottom: 16px;
}

.rd-map-station {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 85px;
    padding: 15px;
    border: 1px solid #465267;
    border-radius: 9px;
    text-align: center;
    background: linear-gradient(145deg, #202e40, #182332);
}

.rd-map-station strong {
    color: #e5d09a;
    font-size: 12px;
    letter-spacing: 1px;
}

.rd-map-station span {
    margin-top: 8px;
    color: #98a6b8;
    font-size: 10px;
    line-height: 1.5;
}

.rd-map-hallway {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    margin: 12px 0 18px;
    border-top: 1px dashed #536177;
    border-bottom: 1px dashed #536177;
    color: #8492a5;
    font-size: 10px;
    letter-spacing: 4px;
}

.rd-map-rooms {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: stretch;
    gap: 18px;
}

.rd-map-room {
    position: relative;
    min-width: 0;
    padding: 18px;
    border: 2px solid #526174;
    border-radius: 12px;
    background: linear-gradient(145deg, #1d2b3b, #182433);
    box-shadow: inset 0 0 0 4px #ffffff03;
}

.rd-map-room.vip-room {
    border-color: #9b804b;
    background: linear-gradient(145deg, #302b26, #1b2532 80%);
}

.rd-map-room-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding-bottom: 15px;
    margin-bottom: 18px;
    border-bottom: 1px solid #ffffff18;
}

.rd-map-room-heading h3 {
    margin: 0;
    color: #f0dca6;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 500;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.rd-map-room-heading p {
    margin: 7px 0 0;
    color: #98a5b6;
    font-size: 11px;
    line-height: 1.6;
}

.rd-map-room-tag {
    flex-shrink: 0;
    padding: 6px 8px;
    border: 1px solid #d8b76c55;
    border-radius: 5px;
    color: var(--rd-gold);
    font-size: 9px;
    letter-spacing: 1px;
}

.rd-map-tables {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 26px 15px;
    min-height: 185px;
    align-content: center;
    justify-items: center;
}

.rd-map-table {
    --table-color: var(--rd-gray);

    width: 100%;
    max-width: 160px;
    min-width: 0;
    padding: 10px 5px;
    border: 1px solid #465267;
    border-radius: 10px;
    color: #edf1f6;
    background: #223145;
    cursor: pointer;
    text-align: center;
    transition: transform .2s, border-color .2s, box-shadow .2s;
}

.rd-map-table.available { --table-color: var(--rd-green); }
.rd-map-table.occupied { --table-color: var(--rd-red); }
.rd-map-table.reserved { --table-color: var(--rd-yellow); }
.rd-map-table.cleaning { --table-color: var(--rd-gray); }

.rd-map-table:hover {
    transform: translateY(-3px);
    border-color: var(--table-color);
    box-shadow: 0 8px 22px #00000030;
}

.rd-map-table:focus-visible {
    outline: 2px solid var(--rd-gold-light);
    outline-offset: 3px;
}

.rd-table-topdown {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 87px;
    margin: 0 auto 8px;
}

.rd-table-chairs {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.rd-table-chairs i {
    position: absolute;
    width: 14px;
    height: 11px;
    border: 1px solid var(--table-color);
    border-radius: 4px;
    background: #28374a;
}

.rd-table-chairs i:nth-child(1) {
    top: 7px;
    left: 23%;
}

.rd-table-chairs i:nth-child(2) {
    top: 7px;
    right: 23%;
}

.rd-table-chairs i:nth-child(3) {
    bottom: 7px;
    left: 23%;
}

.rd-table-chairs i:nth-child(4) {
    bottom: 7px;
    right: 23%;
}

.rd-table-round {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 59px;
    height: 59px;
    border: 2px solid var(--table-color);
    border-radius: 50%;
    color: white;
    background: #172333;
    box-shadow: 0 0 15px #00000030;
}

.rd-table-round .capacity-number {
    font-size: 17px;
    font-weight: 700;
}

.rd-table-round .capacity-label {
    margin-top: 1px;
    font-size: 8px;
    letter-spacing: 1px;
}

.rd-map-table-name {
    display: block;
    margin-top: 5px;
    color: #f6f7f9;
    font-size: 13px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.rd-map-table-info {
    display: block;
    margin-top: 6px;
    color: #9eacbd;
    font-size: 10px;
}

.rd-map-status {
    display: inline-block;
    margin-top: 10px;
    padding: 5px 8px;
    border-radius: 20px;
    color: var(--table-color);
    background: #ffffff08;
    font-size: 10px;
    font-weight: 600;
}

.rd-map-empty {
    grid-column: 1 / -1;
    align-self: center;
    padding: 30px 12px;
    border: 1px dashed #465267;
    border-radius: 8px;
    color: #8997aa;
    font-size: 12px;
    text-align: center;
}

.rd-map-detail {
    display: none;
    margin-top: 18px;
    padding: 20px;
    border: 1px solid #9b804b;
    border-radius: 11px;
    background: linear-gradient(135deg, #263244, #1b2635);
}

.rd-map-detail.open {
    display: block;
}

.rd-map-detail h3 {
    margin: 0 0 8px;
    color: var(--rd-gold-light);
    font-family: Georgia, "Times New Roman", serif;
    font-size: 21px;
}

.rd-map-detail p {
    margin: 0 0 15px;
    color: #bac4d2;
    font-size: 12px;
    line-height: 1.8;
}

.rd-detail-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.rd-detail-actions a,
.rd-detail-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 13px;
    border: 1px solid #d8b66c;
    border-radius: 7px;
    color: #171b21 !important;
    background: #d8b66c;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none !important;
    cursor: pointer;
}

.rd-detail-close {
    color: #e7d9b6 !important;
    background: transparent;
    border-color: #536177;
}

.rd-map-bottom {
    margin-top: 18px;
    padding-top: 15px;
    border-top: 1px solid #465267;
    color: #8190a4;
    font-size: 10px;
    letter-spacing: 1.5px;
    text-align: center;
}

.rd-hidden {
    display: none !important;
}

.rd-no-results {
    display: none;
    padding: 35px 15px;
    color: #9eacbd;
    text-align: center;
}

.rd-no-results.visible {
    display: block;
}

.rd-floorplan-note {
    margin-top: 20px;
    padding: 14px 17px;
    border-left: 3px solid var(--rd-gold);
    border-radius: 0 8px 8px 0;
    color: var(--rd-muted);
    background: #d8b66c0c;
    font-size: 12px;
    line-height: 1.8;
}

@media (max-width: 1150px) {
    .rd-stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .rd-floorplan {
        padding: 16px;
    }

    .rd-map-shell {
        padding: 12px;
    }

    .rd-map-rooms {
        grid-template-columns: 1fr;
    }

    .rd-map-service {
        grid-template-columns: 1fr 1fr;
    }

    .rd-map-room {
        padding: 13px;
    }

    .rd-map-room-heading h3 {
        font-size: 16px;
    }

    .rd-search-wrap {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .rd-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .rd-stat {
        padding: 13px;
    }

    .rd-stat-value {
        font-size: 26px;
    }

    .rd-map-service {
        grid-template-columns: 1fr;
    }

    .rd-map-tables {
        gap: 20px 10px;
    }

    .rd-map-table {
        padding: 8px 3px;
    }
}
</style>

<div class="rd-floorplan">

    <!-- TIÊU ĐỀ -->
    <header class="rd-topbar">
        <div>
            <div class="rd-eyebrow">
                RoyalDine 5S · Restaurant Management
            </div>

            <h1 class="rd-heading">Sơ đồ mặt bằng nhà hàng</h1>

            <p class="rd-subtitle">
                Quản lý không gian, theo dõi trạng thái bàn và chuẩn bị
                trải nghiệm phục vụ cao cấp.
            </p>
        </div>

        <a class="rd-primary-btn"
           href="index.php?c=booking&a=vip">
            <span>＋</span> Đặt bàn VIP
        </a>
        <a class="rd-primary-btn"
   href="index.php?c=booking&a=manage">
    <span>⚙</span> Quản lý bàn
</a>
    </header>

    <!-- THỐNG KÊ -->
    <section class="rd-stats">

        <article class="rd-stat">
            <div class="rd-stat-label">
                <span class="rd-stat-icon">▦</span>
                Tổng số bàn
            </div>
            <div class="rd-stat-value"><?= $totalTables ?></div>
            <div class="rd-stat-caption">Toàn bộ nhà hàng</div>
        </article>

        <article class="rd-stat">
            <div class="rd-stat-label">
                <span class="rd-stat-icon">✓</span>
                Bàn trống
            </div>
            <div class="rd-stat-value"><?= $totalAvailable ?></div>
            <div class="rd-stat-caption">Sẵn sàng phục vụ</div>
        </article>

        <article class="rd-stat">
            <div class="rd-stat-label">
                <span class="rd-stat-icon">♧</span>
                Đang phục vụ
            </div>
            <div class="rd-stat-value"><?= $totalOccupied ?></div>
            <div class="rd-stat-caption">Khách đang dùng bữa</div>
        </article>

        <article class="rd-stat">
            <div class="rd-stat-label">
                <span class="rd-stat-icon">◷</span>
                Đã đặt trước
            </div>
            <div class="rd-stat-value"><?= $totalReserved ?></div>
            <div class="rd-stat-caption">Bàn đã có lịch đặt</div>
        </article>

        <article class="rd-stat">
            <div class="rd-stat-label">
                <span class="rd-stat-icon">✦</span>
                Đang dọn
            </div>
            <div class="rd-stat-value"><?= $totalCleaning ?></div>
            <div class="rd-stat-caption">Chuẩn bị đón khách</div>
        </article>

    </section>

    <!-- CHÚ GIẢI VÀ TÌM KIẾM -->
    <div class="rd-toolbar">
        <div class="rd-legend">
            <?php foreach ($statusInfo as $info): ?>
                <span class="rd-legend-item">
                    <i class="rd-dot <?= $info['class'] ?>"></i>
                    <?= htmlspecialchars($info['label']) ?>
                </span>
            <?php endforeach; ?>
        </div>

        <label class="rd-search-wrap">
            <span class="rd-search-icon">⌕</span>
            <input
                type="search"
                id="rd-search"
                class="rd-search"
                placeholder="Tìm tên bàn hoặc khu vực..."
                aria-label="Tìm tên bàn hoặc khu vực">
        </label>
    </div>

    <!-- SƠ ĐỒ MẶT BẰNG -->
    <div class="rd-map-shell">

        <div class="rd-map-top">
            <div class="rd-map-caption">
                ✦ RoyalDine · Floor Plan
            </div>

            <div class="rd-map-scale">
                SƠ ĐỒ MINH HỌA · KHÔNG THEO TỶ LỆ
            </div>
        </div>

        <!-- LỐI VÀO -->
        <div class="rd-map-entrance">
            <span class="rd-door"></span>
            LỐI VÀO NHÀ HÀNG
            <span class="rd-door"></span>
        </div>

        <!-- KHU VỰC PHỤC VỤ -->
        <div class="rd-map-service">
            <div class="rd-map-station">
                <strong>✦ LỄ TÂN</strong>
                <span>Đón khách · Check-in</span>
            </div>

            <div class="rd-map-station">
                <strong>ROYAL SERVICE</strong>
                <span>Khu vực điều phối và phục vụ</span>
            </div>
        </div>

        <div class="rd-map-hallway">
            LỐI ĐI CHÍNH
        </div>

        <!-- CÁC PHÒNG ĐƯỢC TẠO TỪ DATABASE -->
        <div class="rd-map-rooms" id="rd-map-rooms">

            <?php foreach ($groupedTables as $areaId => $area): ?>

                <?php
                $areaName = $area['name'];
                $surcharge = (float)$area['surcharge'];

                $isVip = $surcharge > 0
                    || stripos($areaName, 'VIP') !== false;

                $areaSearchText = mb_strtolower(
                    $areaName,
                    'UTF-8'
                );
                ?>

                <section
                    class="rd-map-room <?= $isVip ? 'vip-room' : '' ?>"
                    data-area="<?= htmlspecialchars($areaSearchText, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="rd-map-room-heading">
                        <div>
                            <h3>
                                <?= htmlspecialchars($areaName, ENT_QUOTES, 'UTF-8') ?>
                            </h3>

                            <p>
                                Khu vực <?= (int)$areaId ?>
                                · Phụ thu <?= number_format($surcharge) ?>%
                                · <?= count($area['tables']) ?> bàn
                            </p>
                        </div>

                        <span class="rd-map-room-tag">
                            <?= $isVip ? 'VIP PRIVATE' : 'DINING HALL' ?>
                        </span>
                    </div>

                    <div class="rd-map-tables">

                        <?php if (empty($area['tables'])): ?>
                            <div class="rd-map-empty">
                                Khu vực chưa có bàn.
                            </div>
                        <?php endif; ?>

                        <?php foreach ($area['tables'] as $table): ?>

                            <?php
                            $status = $table['status'];

                            $info = $statusInfo[$status] ?? [
                                'label' => $status,
                                'class' => 'cleaning',
                                'icon' => '•'
                            ];

                            $tableName = $table['table_name'];
                            $tableSearchText = mb_strtolower(
                                $tableName,
                                'UTF-8'
                            );
                            ?>

                            <button
                                type="button"
                                class="rd-map-table <?= $info['class'] ?>"
                                data-name="<?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?>"
                                data-name-search="<?= htmlspecialchars($tableSearchText, ENT_QUOTES, 'UTF-8') ?>"
                                data-area-name="<?= htmlspecialchars($areaName, ENT_QUOTES, 'UTF-8') ?>"
                                data-area-search="<?= htmlspecialchars($areaSearchText, ENT_QUOTES, 'UTF-8') ?>"
                                data-capacity="<?= (int)$table['capacity'] ?>"
                                data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                data-status-label="<?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>"
                                data-id="<?= (int)$table['table_id'] ?>"
                                data-surcharge="<?= number_format($surcharge, 0) ?>"
                                aria-label="Xem thông tin bàn <?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?>">

                                <span class="rd-table-topdown">
                                    <span class="rd-table-chairs">
                                        <i></i>
                                        <i></i>
                                        <i></i>
                                        <i></i>
                                    </span>

                                    <span class="rd-table-round">
                                        <span class="capacity-number">
                                            <?= (int)$table['capacity'] ?>
                                        </span>
                                        <span class="capacity-label">
                                            KHÁCH
                                        </span>
                                    </span>
                                </span>

                                <span class="rd-map-table-name">
                                    <?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?>
                                </span>

                                <span class="rd-map-table-info">
                                    Mã bàn #<?= (int)$table['table_id'] ?>
                                </span>

                                <span class="rd-map-status">
                                    <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </button>

                        <?php endforeach; ?>

                    </div>
                </section>

            <?php endforeach; ?>

        </div>

        <div id="rd-no-results" class="rd-no-results">
            Không tìm thấy bàn hoặc khu vực phù hợp.
        </div>

        <!-- CHI TIẾT BÀN KHI BẤM -->
        <section
            id="rd-map-detail"
            class="rd-map-detail"
            aria-live="polite">

            <h3 id="rd-detail-title">Thông tin bàn</h3>

            <p id="rd-detail-info"></p>

            <div class="rd-detail-actions">
                <a id="rd-detail-booking"
                   href="index.php?c=booking&a=vip">
                    Mở trang đặt bàn VIP
                </a>

                <button
                    type="button"
                    id="rd-detail-close"
                    class="rd-detail-close">
                    Đóng chi tiết
                </button>
            </div>
        </section>

        <div class="rd-map-bottom">
            ROYALDINE 5S · PREMIUM DINING EXPERIENCE
        </div>

    </div>
</div>

<script>
(function () {
    const root = document.querySelector('.rd-floorplan');

    if (!root) {
        return;
    }

    const searchInput = root.querySelector('#rd-search');
    const rooms = Array.from(root.querySelectorAll('.rd-map-room'));
    const tables = Array.from(root.querySelectorAll('.rd-map-table'));
    const noResults = root.querySelector('#rd-no-results');

    const detail = root.querySelector('#rd-map-detail');
    const detailTitle = root.querySelector('#rd-detail-title');
    const detailInfo = root.querySelector('#rd-detail-info');
    const closeButton = root.querySelector('#rd-detail-close');
    const bookingLink = root.querySelector('#rd-detail-booking');

    // Tìm bàn theo tên bàn hoặc khu vực.
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = this.value
                .trim()
                .toLocaleLowerCase('vi');

            let visibleRoomCount = 0;
            let visibleTableCount = 0;

            rooms.forEach(function (room) {
                const roomArea = (
                    room.dataset.area || ''
                ).toLocaleLowerCase('vi');

                let roomHasVisibleTable = false;

                room.querySelectorAll('.rd-map-table').forEach(
                    function (table) {
                        const tableName = (
                            table.dataset.nameSearch || ''
                        ).toLocaleLowerCase('vi');

                        const tableArea = (
                            table.dataset.areaSearch || ''
                        ).toLocaleLowerCase('vi');

                        const matched = keyword === ''
                            || tableName.includes(keyword)
                            || tableArea.includes(keyword)
                            || roomArea.includes(keyword);

                        table.classList.toggle(
                            'rd-hidden',
                            !matched
                        );

                        if (matched) {
                            roomHasVisibleTable = true;
                            visibleTableCount++;
                        }
                    }
                );

                // Khu vực không có bàn vẫn hiện khi ô tìm kiếm rỗng.
                const hasTables = room.querySelectorAll(
                    '.rd-map-table'
                ).length > 0;

                const shouldShowRoom = keyword === ''
                    ? true
                    : roomHasVisibleTable
                        || (!hasTables && roomArea.includes(keyword));

                room.classList.toggle(
                    'rd-hidden',
                    !shouldShowRoom
                );

                if (shouldShowRoom) {
                    visibleRoomCount++;
                }
            });

            noResults.classList.toggle(
                'visible',
                keyword !== '' && visibleTableCount === 0
                    && visibleRoomCount === 0
            );

            if (keyword !== '' && visibleTableCount === 0) {
                const hasEmptyAreaMatch = rooms.some(function (room) {
                    return room.querySelectorAll('.rd-map-table').length === 0
                        && (room.dataset.area || '')
                            .toLocaleLowerCase('vi').includes(keyword);
                });

                noResults.classList.toggle(
                    'visible',
                    !hasEmptyAreaMatch
                );
            }
        });
    }

    // Bấm bàn để xem thông tin.
    tables.forEach(function (table) {
        table.addEventListener('click', function () {
            detailTitle.textContent =
                'Bàn ' + this.dataset.name;

            detailInfo.textContent =
                'Khu vực: ' + this.dataset.areaName
                + ' | Sức chứa: ' + this.dataset.capacity + ' khách'
                + ' | Trạng thái: ' + this.dataset.statusLabel
                + ' | Phụ thu khu vực: ' + this.dataset.surcharge + '%';

            bookingLink.href =
                'index.php?c=booking&a=vip&table_id='
                + encodeURIComponent(this.dataset.id);

            detail.classList.add('open');

            detail.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        });
    });

    if (closeButton) {
        closeButton.addEventListener('click', function () {
            detail.classList.remove('open');
        });
    }
})();
</script>
