<?php
    require_once __DIR__ . '/../../models/CashShiftModel.php'; 
    $cashModel = new CashShiftModel();
    $cashier_id = $_SESSION['user_id'] ?? 2;
    $currentShift = $cashModel->getCurrentOpenShift($cashier_id);

    $subtab = isset($_GET['subtab']) ? $_GET['subtab'] : 'shift';
    
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;
?>

<style>
    .sub-tab-btn { padding: 10px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: all 0.2s; border: 1px solid #cbd5e1; color: #475569; background: #fff; }
    .sub-tab-btn:hover { background: #f8fafc; color: #0f172a; }
    .sub-tab-btn.active { background: #d4af37; color: #fff; border-color: #d4af37; box-shadow: 0 2px 4px rgba(212,175,55,0.3); }
    
    .custom-card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .custom-card-header { padding: 16px 20px; font-weight: 700; font-size: 14px; letter-spacing: 0.5px; text-transform: uppercase; }
    .custom-card-body { padding: 24px; }
    .form-group { margin-bottom: 18px; }
    .form-control { width: 100%; box-sizing: border-box; outline: none; }
    
    .btn-fund-thu { background-color: #10b981; color: #ffffff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); cursor: pointer; }
    .btn-fund-thu:hover { background-color: #059669; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3); color: white; }
    
    .btn-fund-chi { background-color: #ef4444; color: #ffffff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2); cursor: pointer; }
    .btn-fund-chi:hover { background-color: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3); color: white; }

    .pagination-container { display: flex; justify-content: center; padding: 20px 0; background: #fff; border-top: 1px solid #e2e8f0; }
    .pagination-custom { display: flex; list-style: none; padding: 0; margin: 0; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; }
    .pagination-custom li { border-right: 1px solid #cbd5e1; }
    .pagination-custom li:last-child { border-right: none; }
    .pagination-custom li a, .pagination-custom li span { display: block; padding: 8px 16px; text-decoration: none; color: #0f172a; font-weight: 600; background: #fff; transition: background-color 0.2s; font-size: 15px; }
    .pagination-custom li a:hover { background: #f1f5f9; }
    .pagination-custom li.active a, .pagination-custom li.active span { background: #cf9b35; color: #fff; border-color: #cf9b35; } /* Màu vàng đồng chuẩn */
    .pagination-custom li.disabled span { color: #94a3b8; background: #f1f5f9; cursor: not-allowed; }
    .btn-icon-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .btn-icon-action:hover {
        background-color: #d4af37; 
        border-color: #d4af37;
        color: #ffffff; 
    }
</style>

<div style="display: flex; gap: 12px; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
    <a href="index.php?c=stock&tab=cash_shift&subtab=shift" class="sub-tab-btn <?= $subtab == 'shift' ? 'active' : '' ?>">🕒 Quản lý Ca</a>
    <a href="index.php?c=stock&tab=cash_shift&subtab=history_shift" class="sub-tab-btn <?= $subtab == 'history_shift' ? 'active' : '' ?>">📜 Lịch sử Ca</a>
    <a href="index.php?c=stock&tab=cash_shift&subtab=fund" class="sub-tab-btn <?= $subtab == 'fund' ? 'active' : '' ?>">💵 Sổ Quỹ Tiền Mặt</a>
</div>

<?php if ($subtab == 'fund'): ?>
    
    <div class="custom-card">
        <div class="custom-card-header" style="background-color: #1e293b; color: #d4af37; display: flex; justify-content: space-between; align-items: center;">
            <span>SỔ QUỸ & THU CHI KHÁC</span>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-fund-thu" data-bs-toggle="modal" data-bs-target="#modalPhieuThu">+ Lập Phiếu Thu</button>
                <button type="button" class="btn-fund-chi" data-bs-toggle="modal" data-bs-target="#modalPhieuChi">- Lập Phiếu Chi</button>
            </div>
        </div>
        <div class="custom-card-body" style="padding: 0;">
            <?php
                $dbConn = Database::getConnection();
                $fundTransactions = [];
                $totalFundPages = 0;
                try {
                    $stmtCount = $dbConn->prepare("SELECT COUNT(*) FROM cash_transactions");
                    $stmtCount->execute();
                    $totalRows = $stmtCount->fetchColumn();
                    $totalFundPages = ceil($totalRows / $limit);

                    $stmtF = $dbConn->prepare("SELECT * FROM cash_transactions ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
                    $stmtF->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                    $stmtF->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                    $stmtF->execute();
                    $fundTransactions = $stmtF->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $fundTransactions = [];
                }

                if (empty($fundTransactions)):
            ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <h4 style="color: #64748b; font-weight: 600; margin-bottom: 8px;">Chưa có giao dịch thu/chi nào.</h4>
                    <p style="color: #94a3b8; font-size: 14px; margin: 0;">Lịch sử lập phiếu thu, chi sẽ hiển thị tại đây.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 15px;">
                        <thead>
                            <tr style="background-color: #e2e8f0; border-bottom: 2px solid #cbd5e1; color: #0f172a;">
                                <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Mã Phiếu</th>
                                <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Loại giao dịch</th>
                                <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px; text-align: right;">Số tiền</th>
                                <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Thời gian</th>
                                <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Người lập</th>
                                <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Nội dung / Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fundTransactions as $ft): ?>
                                <tr style="border-bottom: 1px solid #cbd5e1; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9';" onmouseout="this.style.backgroundColor='transparent';">
                                    <td style="padding: 16px; font-weight: 700; color: #000;">#PT-<?= $ft['id'] ?></td>
                                    <td style="padding: 16px;">
                                        <span style="padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 800; box-shadow: 0 1px 2px rgba(0,0,0,0.05); background: <?= $ft['type'] == 'IN' ? '#dcfce7; color: #15803d;' : '#fee2e2; color: #b91c1c;' ?>">
                                            <?= $ft['type'] == 'IN' ? 'PHIẾU THU' : 'PHIẾU CHI' ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px; text-align: right; font-weight: 800; font-size: 16px; color: <?= $ft['type'] == 'IN' ? '#16a34a' : '#dc2626' ?>;">
                                        <?= ($ft['type'] == 'IN' ? '+' : '-') . number_format($ft['amount']) ?> đ
                                    </td>
                                    <td style="padding: 16px; font-weight: 600; color: #1e293b;"><?= date('d/m/Y H:i', strtotime($ft['created_at'])) ?></td>
                                    <td style="padding: 16px; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($ft['creator'] ?? 'Quản lý') ?></td>
                                    <td style="padding: 16px; font-weight: 600; color: #1e293b; line-height: 1.5;"><?= htmlspecialchars($ft['note'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalFundPages > 1): ?>
                    <div class="pagination-container">
                        <ul class="pagination-custom">
                            <li class="<?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a href="<?= ($page <= 1) ? '#' : 'index.php?c=stock&tab=cash_shift&subtab=fund&page='.($page - 1) ?>">Trước</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalFundPages; $i++): ?>
                                <li class="<?= ($page == $i) ? 'active' : '' ?>">
                                    <a href="index.php?c=stock&tab=cash_shift&subtab=fund&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="<?= ($page >= $totalFundPages) ? 'disabled' : '' ?>">
                                <a href="<?= ($page >= $totalFundPages) ? '#' : 'index.php?c=stock&tab=cash_shift&subtab=fund&page='.($page + 1) ?>">Sau</a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="modalPhieuThu" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <form action="index.php?c=CashShift&a=transaction" method="POST">
                    <input type="hidden" name="trans_type" value="IN">
                    <div class="modal-header" style="background-color: #10b981; color: white; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                        <h5 class="modal-title fw-bold">LẬP PHIẾU THU KHÁC</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="padding: 24px;">
                        <div class="form-group mb-3">
                            <label class="fw-bold mb-2" style="color: #065f46;">Số tiền thu (VNĐ) <span style="color: red;">*</span></label>
                            <input type="text" class="form-control number-format" name="amount" placeholder="VD: 500,000" required maxlength="15" style="border: 2px solid #34d399; font-weight: bold; font-size: 18px; padding: 12px; color: #047857;">
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold mb-2" style="color: #475569;">Người nộp tiền</label>
                            <input type="text" class="form-control" name="target_person" placeholder="Tên khách hàng, nhân viên..." style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        </div>
                        <div class="form-group mb-0">
                            <label class="fw-bold mb-2" style="color: #475569;">Lý do thu tiền <span style="color: red;">*</span></label>
                            <textarea class="form-control" name="note" rows="3" placeholder="Ghi rõ lý do thu tiền..." required style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8fafc; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                        <button type="button" class="btn btn-secondary" style="padding: 8px 16px; border-radius: 8px;" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-fund-thu">Xác nhận Thu Tiền</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPhieuChi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <form action="index.php?c=CashShift&a=transaction" method="POST">
                    <input type="hidden" name="trans_type" value="OUT">
                    <div class="modal-header" style="background-color: #ef4444; color: white; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                        <h5 class="modal-title fw-bold">LẬP PHIẾU CHI KHÁC</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="padding: 24px;">
                        <div class="form-group mb-3">
                            <label class="fw-bold mb-2" style="color: #991b1b;">Số tiền chi (VNĐ) <span style="color: red;">*</span></label>
                            <input type="text" class="form-control number-format" name="amount" placeholder="VD: 200,000" required maxlength="15" style="border: 2px solid #fca5a5; font-weight: bold; font-size: 18px; padding: 12px; color: #b91c1c;">
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold mb-2" style="color: #475569;">Người nhận tiền</label>
                            <input type="text" class="form-control" name="target_person" placeholder="Tên nhà cung cấp, thợ điện..." style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;">
                        </div>
                        <div class="form-group mb-0">
                            <label class="fw-bold mb-2" style="color: #475569;">Lý do chi tiền <span style="color: red;">*</span></label>
                            <textarea class="form-control" name="note" rows="3" placeholder="Ghi rõ lý do xuất chi..." required style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #fef2f2; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                        <button type="button" class="btn btn-secondary" style="padding: 8px 16px; border-radius: 8px;" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-fund-chi">Xác nhận Chi Tiền</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<?php elseif ($subtab == 'history_shift'): ?>

    <div class="custom-card">
        <div class="custom-card-header" style="background-color: #1e293b; color: #d4af37;">
            LỊCH SỬ CA LÀM VIỆC ĐÃ ĐÓNG
        </div>
        <div class="custom-card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 15px;">
                    <thead>
                        <tr style="background-color: #e2e8f0; border-bottom: 2px solid #cbd5e1; color: #0f172a;">
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Mã Ca</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Nhân viên</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Thời gian mở</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Thời gian đóng</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px; text-align: right;">Tiền ban đầu</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px; text-align: right;">Thực tế két</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px; text-align: right;">Chênh lệch</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px;">Ghi chú / Bàn giao</th>
                            <th style="padding: 15px 16px; font-weight: 800; text-transform: uppercase; font-size: 13px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $dbConn = Database::getConnection();
                            $totalHistoryPages = 0;
                            
                            $stmtCountH = $dbConn->prepare("SELECT COUNT(*) FROM cash_shifts WHERE status = 'Closed'");
                            $stmtCountH->execute();
                            $totalHRows = $stmtCountH->fetchColumn();
                            $totalHistoryPages = ceil($totalHRows / $limit);

                            $sqlHistory = "SELECT cs.*, u.full_name as cashier_name 
                                           FROM cash_shifts cs 
                                           LEFT JOIN users u ON cs.cashier_id = u.id 
                                           WHERE cs.status = 'Closed' 
                                           ORDER BY cs.closed_at DESC LIMIT :limit OFFSET :offset";
                            
                            $stmtH = $dbConn->prepare($sqlHistory);
                            $stmtH->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                            $stmtH->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                            $stmtH->execute();
                            $closedShifts = $stmtH->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($closedShifts)):
                        ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #64748b; font-weight: 600;">Chưa có lịch sử ca làm việc nào được đóng.</td>
                            </tr>
                        <?php else: foreach ($closedShifts as $cs): ?>
                            <tr style="border-bottom: 1px solid #cbd5e1; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9';" onmouseout="this.style.backgroundColor='transparent';">
                                <td style="padding: 16px; font-weight: 700; color: #000;">#CA-<?= $cs['id'] ?></td>
                                <td style="padding: 16px; font-weight: 700; color: #1d4ed8;"><?= htmlspecialchars($cs['cashier_name'] ?? 'Nhân viên #' . $cs['cashier_id']) ?></td>
                                <td style="padding: 16px; font-weight: 600; color: #1e293b;"><?= date('d/m/Y H:i', strtotime($cs['opened_at'])) ?></td>
                                <td style="padding: 16px; font-weight: 600; color: #1e293b;"><?= date('d/m/Y H:i', strtotime($cs['closed_at'])) ?></td>
                                <td style="padding: 16px; text-align: right; font-weight: 700; color: #0369a1;"><?= number_format($cs['opening_cash']) ?> đ</td>
                                <td style="padding: 16px; text-align: right; font-weight: 700; color: #15803d;"><?= number_format($cs['actual_closing_cash']) ?> đ</td>
                                <td style="padding: 16px; text-align: right; font-weight: 800; font-size: 16px; color: <?= $cs['discrepancy'] < 0 ? '#b91c1c' : ($cs['discrepancy'] > 0 ? '#15803d' : '#334155') ?>;">
                                    <?= ($cs['discrepancy'] > 0 ? '+' : '') . number_format($cs['discrepancy']) ?> đ
                                </td>
                                <td style="padding: 16px; font-weight: 600; color: #1e293b; line-height: 1.5;"><?= htmlspecialchars($cs['note'] ?? 'Không có') ?></td>
                                <td style="padding: 16px; text-align: center;">
                                    <a href="index.php?c=CashShift&a=printShift&id=<?= $cs['id'] ?>" target="_blank" class="btn-icon-action" title="In Báo Cáo">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalHistoryPages > 1): ?>
                <div class="pagination-container">
                    <ul class="pagination-custom">
                        <li class="<?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a href="<?= ($page <= 1) ? '#' : 'index.php?c=stock&tab=cash_shift&subtab=history_shift&page='.($page - 1) ?>">Trước</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalHistoryPages; $i++): ?>
                            <li class="<?= ($page == $i) ? 'active' : '' ?>">
                                <a href="index.php?c=stock&tab=cash_shift&subtab=history_shift&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="<?= ($page >= $totalHistoryPages) ? 'disabled' : '' ?>">
                            <a href="<?= ($page >= $totalHistoryPages) ? '#' : 'index.php?c=stock&tab=cash_shift&subtab=history_shift&page='.($page + 1) ?>">Sau</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

        </div>
    </div>

<?php else: ?>

    <?php if (!$currentShift): ?>
        <div class="custom-card" style="max-width: 550px; margin: 0 auto;">
            <div style="background-color: #1b1f23; border-bottom: 2px solid #d4af37; padding: 18px 24px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="margin: 0; color: #d4af37; font-size: 16px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;">BẮT ĐẦU CA MỚI</h3>
</div>
            <div class="custom-card-body">
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; color: #92400e; font-size: 14px; margin-bottom: 25px;">
                    Hiện tại <strong>chưa có ca làm việc nào</strong>. Vui lòng đếm số lượng tiền lẻ đầu ca đang có trong két và nhập vào form bên dưới.
                </div>
                
                <form action="index.php?c=CashShift&a=open" method="POST">
                    <div class="form-group">
                        <label style="color: #475569; font-weight: 600; margin-bottom: 8px; display: block;">Tiền lẻ đầu ca (VNĐ) <span style="color: red;">*</span></label>
                        <input type="text" class="form-control number-format" name="opening_cash" placeholder="VD: 500,000" required autofocus maxlength="15" style="padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #475569; font-weight: 600; margin-bottom: 8px; display: block;">Ghi chú (Tùy chọn)</label>
                        <textarea class="form-control" name="note" rows="3" placeholder="Ghi chú tình trạng hòm đựng tiền..." style="padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;"></textarea>
                    </div>
                    <button type="submit" style="background-color: #d4af37; color: white; padding: 14px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; width: 100%; font-size: 15px;">XÁC NHẬN MỞ CA</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 24px; flex-wrap: wrap; align-items: stretch;">
            <div class="custom-card" style="flex: 1; min-width: 350px; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="custom-card-header" style="background-color: #1e293b; color: #d4af37; border-bottom: 3px solid #d4af37;">DÒNG TIỀN TRONG CA</div>
                <div class="custom-card-body" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 15px;">
                        <tr style="border-bottom: 1px dashed #e2e8f0;"><td style="padding: 14px 0; color: #64748b; font-weight: 500;">Giờ mở ca:</td><td style="padding: 14px 0; text-align: right; font-weight: 600;"><?= date('d/m/Y H:i:s', strtotime($currentShift['opened_at'])) ?></td></tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;"><td style="padding: 14px 0; color: #64748b; font-weight: 500;">Tiền lẻ ban đầu:</td><td style="padding: 14px 0; text-align: right; font-weight: 700; color: #0284c7;"><?= number_format($currentShift['opening_cash']) ?> đ</td></tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;"><td style="padding: 14px 0; color: #64748b; font-weight: 500;">Tổng bán hàng:</td><td style="padding: 14px 0; text-align: right; font-weight: 700; color: #16a34a;">+ <?= number_format($currentShift['total_cash_sales']) ?> đ</td></tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;"><td style="padding: 14px 0; color: #64748b; font-weight: 500;">Thu khác:</td><td style="padding: 14px 0; text-align: right; font-weight: 700; color: #16a34a;">+ <?= number_format($currentShift['total_cash_in']) ?> đ</td></tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;"><td style="padding: 14px 0; color: #64748b; font-weight: 500;">Chi khác:</td><td style="padding: 14px 0; text-align: right; font-weight: 700; color: #dc2626;">- <?= number_format($currentShift['total_cash_out']) ?> đ</td></tr>
                        <tr style="border-bottom: 1px dashed #e2e8f0;">
                            <td style="padding: 14px 0; color: #64748b; font-weight: 500;">Ghi chú mở ca:</td>
                            <td style="padding: 14px 0; text-align: right; font-weight: 600; color: #334155; font-style: italic;"><?= !empty($currentShift['note']) ? htmlspecialchars($currentShift['note']) : '<span style="color: #94a3b8; font-style: normal;">Không có ghi chú</span>' ?></td>
                        </tr>
                    </table>
                    <?php $expected_cash = $currentShift['opening_cash'] + $currentShift['total_cash_sales'] + $currentShift['total_cash_in'] - $currentShift['total_cash_out']; ?>
                    <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #fde68a;">
                        <span style="color: #b45309; font-size: 13px; font-weight: 600; text-transform: uppercase;">Tiền mặt phải có trong két</span>
                        <h2 style="margin: 8px 0 0 0; color: #92400e; font-size: 26px; font-weight: 800;"><?= number_format($expected_cash) ?> VNĐ</h2>
                    </div>
                </div>
            </div>

            <div class="custom-card" style="flex: 1; min-width: 350px; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="custom-card-header" style="background-color: #ef4444; color: white; border-bottom: 3px solid #b91c1c;">ĐÓNG CA & BÀN GIAO</div>
                <div class="custom-card-body" style="background-color: #fef2f2; flex: 1; display: flex; flex-direction: column;">
                    <form action="index.php?c=CashShift&a=close" method="POST" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <input type="hidden" name="shift_id" value="<?= $currentShift['id'] ?>">
                        <div>
                            <div class="form-group">
                                <label style="color: #991b1b; font-weight: 600; margin-bottom: 8px; display: block;">Tiền mặt đếm thực tế <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="form-control number-format" name="actual_closing_cash" placeholder="Nhập số tiền thực tế đếm được..." required maxlength="15" style="border: 2px solid #fca5a5; color: #991b1b; font-weight: bold; font-size: 16px; padding: 12px 15px; border-radius: 8px; background-color: #fff;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="color: #475569; font-weight: 600; margin-bottom: 8px; display: block;">Ghi chú bàn giao</label>
                                <textarea class="form-control" name="close_note" rows="4" placeholder="Giải trình nguyên nhân chênh lệch (nếu có)..." style="border: 1px solid #cbd5e1; padding: 12px 15px; border-radius: 8px; font-size: 14px; background-color: #fff;"></textarea>
                            </div>
                        </div>
                        <button type="submit" style="background-color: #dc2626; color: white; padding: 15px 20px; border: none; border-radius: 8px; font-weight: bold; width: 100%; cursor: pointer; margin-top: 20px;" onclick="return confirm('Xác nhận đóng ca?')">XÁC NHẬN ĐÓNG CA</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const numberInputs = document.querySelectorAll('.number-format');
    numberInputs.forEach(function (input) {
        input.addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); });
        input.addEventListener('blur', function () {
            if (this.value !== '') {
                const number = Number(this.value);
                this.value = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(number);
            }
        });
        input.addEventListener('focus', function () { this.value = this.value.replace(/\D/g, ''); });
        if (input.form) {
            input.form.addEventListener('submit', function () {
                numberInputs.forEach(inp => { inp.value = inp.value.replace(/\D/g, ''); });
            });
        }
    });
});
</script>