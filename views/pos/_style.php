<?php /* CSS dÃ¹ng chung cho views/pos/index.php vÃ  report.php - ná»n tá»‘i, vÃ ng gold RoyalDine */ ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet">
<style>
.rd-body-scope {
    background: #0f1012;
    color: #e1e1e1;
}

.rd {
    --g: #d4af37;
    --gh: #b89628;
    --cd: #1a1d20;
    --in: #121416;
    --ln: #2d3238;
    --mu: #8b9198;
    --ok: #3fb27f;
    --wn: #e0a030;
    --bd: #e5645a;
    font: 14px/1.5 "Be Vietnam Pro", system-ui, sans-serif;
    color: #e1e1e1;
    padding: 16px;
}

.rd * {
    box-sizing: border-box;
}

.rd h1, 
.rd h3 {
    font-family: "Cormorant Garamond", Georgia, serif;
    color: var(--g);
    margin: 0;
}

.rd h1 {
    font-size: 30px;
}

.rd h3 {
    font-size: 22px;
    margin-bottom: 10px;
}

.rd h4 {
    font-size: 12px;
    color: var(--mu);
    font-weight: 500;
    margin: 12px 0 4px;
}

.rd hr {
    border: 0;
    border-top: 1px dashed var(--ln);
    margin: 8px 0;
}

.rd-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--ln);
}

.rd-head small {
    color: var(--mu);
}

.rd-nav {
    display: flex;
    gap: 4px;
    background: var(--in);
    border: 1px solid var(--ln);
    padding: 4px;
    border-radius: 10px;
    flex-wrap: wrap;
}

.rd-nav button, 
.rd-nav a {
    background: none;
    border: 0;
    color: #aaa;
    padding: 7px 14px;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    font: inherit;
}

.rd-nav .on {
    background: var(--g);
    color: #000;
    font-weight: 600;
}

.rd-badge-n {
    background: var(--bd);
    color: #fff;
    border-radius: 10px;
    padding: 0 7px;
    font-size: 12px;
    margin-left: 4px;
}

.rd-pane {
    display: none;
}

.rd-pane.on {
    display: block;
}

.rd-grid {
    display: grid;
    gap: 16px;
    align-items: start;
}

.rd-grid.pos {
    grid-template-columns: 270px 1fr 380px;
}

.rd-grid.pay {
    grid-template-columns: 230px 1fr 320px;
}

.rd-card {
    background: var(--cd);
    border: 1px solid var(--ln);
    border-radius: 12px;
    padding: 14px;
}

.rd-tgrid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.rd-tb {
    border: 1px solid var(--ln);
    border-left-width: 4px;
    background: var(--in);
    color: #e1e1e1;
    border-radius: 6px;
    padding: 8px 4px;
    cursor: pointer;
    text-align: center;
    font: inherit;
}

.rd-tb b {
    display: block;
}

.rd-tb small {
    color: var(--mu);
    font-size: 11px;
}

.rd-tb.free {
    border-left-color: var(--ok);
}

.rd-tb.busy {
    border-left-color: var(--wn);
}

.rd-tb.clean {
    border-left-color: #6a7380;
    opacity: .8;
}

.rd-tb.resv {
    border-left-color: #5b9bd5;
}

.rd-tb.vip b {
    color: var(--g);
}

.rd-tb.sel {
    outline: 2px solid var(--g);
}

.rd-tb.wide {
    width: 100%;
    text-align: left;
    padding: 10px;
    margin-bottom: 8px;
}

.rd-lg {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    font-size: 12px;
    color: var(--mu);
    margin-bottom: 10px;
}

.rd-lg i {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 2px;
    margin-right: 4px;
}

.rd-chips {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.rd-chip {
    border: 1px solid var(--ln);
    background: none;
    color: #ccc;
    border-radius: 20px;
    padding: 5px 13px;
    cursor: pointer;
    font: inherit;
}

.rd-chip.on {
    background: var(--g);
    color: #000;
    border-color: var(--g);
    font-weight: 600;
}

.rd-mgrid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 10px;
}

.rd-mi {
    border: 1px solid var(--ln);
    background: var(--in);
    color: #e1e1e1;
    border-radius: 8px;
    padding: 10px;
    text-align: left;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-height: 86px;
    font: inherit;
}

.rd-mi:hover {
    border-color: var(--g);
}

.rd-mi small {
    color: var(--mu);
}

.rd-mi span {
    color: var(--g);
    font-weight: 600;
    margin-top: auto;
}

.rd-btn {
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
    border: 1px solid var(--g);
    background: none;
    color: #e1e1e1;
    font: inherit;
    font-weight: 500;
}

.rd-btn-gold {
    background: var(--g);
    color: #000;
    font-weight: 600;
}

.rd-btn-gold:hover {
    background: var(--gh);
}

.rd-btn-line {
    border-color: var(--ln);
}

.rd-btn-del {
    border-color: var(--bd);
    color: var(--bd);
    padding: 3px 10px;
    font-size: 12px;
}

.rd-btn:disabled {
    opacity: .4;
    cursor: not-allowed;
}

.rd button:focus-visible, 
.rd input:focus-visible, 
.rd select:focus-visible {
    outline: 2px solid var(--g);
    outline-offset: 2px;
}

.rd-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    margin: 6px 0;
}

.rd-row.end {
    justify-content: flex-end;
    margin-top: 14px;
}

.rd input, 
.rd select {
    background: var(--in);
    color: #fff;
    border: 1px solid var(--ln);
    border-radius: 6px;
    padding: 8px;
    width: 100%;
    font: inherit;
}

.rd label {
    display: block;
    margin: 10px 0 4px;
    color: var(--mu);
    font-size: 12px;
}

.rd-ln {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 2px 8px;
    padding: 9px 0;
    border-bottom: 1px solid var(--ln);
}

.rd-ln small {
    color: var(--mu);
}

.rd-ln.void .nm {
    text-decoration: line-through;
    color: var(--mu);
}

.rd-ln a {
    color: var(--g);
    cursor: pointer;
}

.rd-bg {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    border: 1px solid var(--ln);
    white-space: nowrap;
}

.rd-bg.s-Waiting {
    color: var(--wn);
    border-color: var(--wn);
}

.rd-bg.s-Cooking {
    color: var(--g);
    border-color: var(--g);
}

.rd-bg.s-Ready {
    color: var(--ok);
    border-color: var(--ok);
}

.rd-bg.s-Served {
    color: var(--mu);
}

.rd-bg.s-Cancelled {
    color: var(--bd);
    border-color: var(--bd);
}

.rd-q {
    display: inline-flex;
    gap: 6px;
    align-items: center;
}

.rd-q button {
    width: 26px;
    height: 26px;
    border: 1px solid var(--ln);
    background: none;
    color: #e1e1e1;
    border-radius: 50%;
    cursor: pointer;
}

.rd-tot {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 4px 0;
}

.rd-tot.big {
    font-family: "Be Vietnam Pro", system-ui, sans-serif;
    font-weight: 600;
    font-size: 24px;
    color: var(--g);
    border-top: 1px solid var(--g);
    margin-top: 8px;
    padding-top: 8px;
}

.rd-empty {
    color: var(--mu);
    padding: 28px 0;
    text-align: center;
}

.rd-hint {
    font-size: 12px;
    color: var(--mu);
}

.rd-er {
    color: var(--bd);
    font-size: 12px;
    min-height: 16px;
    margin-top: 6px;
}

.rd-ok {
    color: var(--ok);
    font-size: 12px;
    margin-top: 4px;
}

.rd-bh {
    text-align: center;
    border-bottom: 1px double var(--g);
    padding-bottom: 10px;
    margin-bottom: 8px;
}

.rd-bh small {
    color: var(--mu);
}

.rd-qr {
    background: #fff;
    color: #000;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 12px;
}

.rd-qr img {
    width: 100%;
    max-width: 240px;
    margin: auto;
}

.rd-qr b {
    font-size: 16px;
}

.kcols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    align-items: start;
}

.rd-tk {
    background: #22262b;
    border-left: 4px solid var(--g);
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
}

.rd-tk.late {
    border-left-color: var(--bd);
}

.rd-tk.late .tt span:last-child {
    color: var(--bd);
}

.rd-tk .tt {
    display: flex;
    justify-content: space-between;
    font-weight: 600;
}

.rd-tk .qty {
    font: 700 20px "Cormorant Garamond", serif;
    color: #fff;
}

.rd-tk small {
    display: block;
    color: var(--mu);
    margin-bottom: 6px;
}

.rd-tk button {
    width: 100%;
    margin-top: 6px;
}

.rd-md {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .65);
    z-index: 1050;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.rd-md.on {
    display: flex;
}

.rd-mb {
    background: var(--cd);
    border: 1px solid var(--ln);
    border-radius: 12px;
    padding: 20px;
    width: min(460px, 100%);
}

#rdToast {
    position: fixed;
    bottom: 18px;
    left: 50%;
    transform: translateX(-50%);
    background: #22262b;
    color: #fff;
    padding: 10px 18px;
    border-radius: 6px;
    border-left: 3px solid var(--g);
    z-index: 1060;
    display: none;
    max-width: 90vw;
}

#rdToast.bad {
    border-left-color: var(--bd);
}

.rd-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 16px;
}

.rd-kpi b {
    display: block;
    font-family: "Be Vietnam Pro", system-ui, sans-serif;
    font-weight: 600;
    font-size: 28px;
    color: var(--g);
}

.rd-kpi small {
    color: var(--mu);
}

.rd-filter {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    align-items: end;
    margin-bottom: 16px;
}

.rd-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
    align-items: start;
}

.rd-table {
    width: 100%;
    border-collapse: collapse;
}

.rd-table th, 
.rd-table td {
    text-align: left;
    padding: 8px;
    border-bottom: 1px solid var(--ln);
    font-size: 13px;
    vertical-align: top;
}

.rd-table th {
    color: var(--g);
    font-weight: 500;
}

.rd-scroll {
    overflow-x: auto;
}

.rd-bar {
    height: 8px;
    background: var(--g);
    border-radius: 4px;
    margin: 2px 0 10px;
}

@media(max-width: 1100px) {
    .rd-grid.pos, 
    .rd-grid.pay, 
    .kcols, 
    .rd-2col, 
    .rd-kpis {
        grid-template-columns: 1fr;
    }
    .rd-filter {
        grid-template-columns: 1fr 1fr;
    }
}

@media print {
    .rd-nav, 
    .rd-filter, 
    .rd-btn, 
    .rd-head a {
        display: none !important;
    }
    .rd, 
    .rd-body-scope {
        background: #fff !important;
        color: #000 !important;
    }
    .rd-card {
        border-color: #999;
        background: #fff;
    }
    .rd h1, 
    .rd h3, 
    .rd-kpi b, 
    .rd-table th {
        color: #000;
    }
}
/* Giao diện thẻ món ăn có hình ảnh trên POS */
.rd-mgrid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(195px, 1fr)) !important;
    gap: 14px !important;
}

.rd-mi {
    padding: 10px !important;
    border-radius: 12px !important;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-height: 215px !important;
}

.rd-mi:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(23, 36, 59, 0.10) !important;
}

.rd-mi-thumb {
    width: 100%;
    height: 118px;
    border-radius: 8px;
    overflow: hidden;
    background: linear-gradient(135deg, #17243b 0%, #0f172a 100%);
    margin-bottom: 6px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.rd-mi-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.25s ease;
}

.rd-mi:hover .rd-mi-thumb img {
    transform: scale(1.05);
}

.rd-mi-thumb-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #e5bd69;
    text-align: center;
    padding: 8px;
}

.rd-mi-thumb-fallback strong {
    font-size: 13px;
    letter-spacing: 0.5px;
    color: #e5bd69;
}

.rd-mi-thumb-fallback span {
    font-size: 10.5px;
    color: #cbd5e1 !important;
    margin-top: 2px !important;
}
</style>

<style>
/* ROYALDINE POS - LIGHT MODE NAVY & GOLD */

/* Nền và màu chữ chính */
.rd-body-scope {
    background: #f8fafc !important;
    color: #1e293b !important;
}

.rd {
    --g: #c69b45;
    --gh: #b38936;
    --cd: #ffffff;
    --in: #f8fafc;
    --ln: #e2e8f0;
    --mu: #64748b;
    --ok: #15803d;
    --wn: #b45309;
    --bd: #b91c1c;

    background: #f8fafc !important;
    color: #1e293b !important;
}

/* Các khung chính: Bàn, Thực đơn, Hóa đơn, Thanh toán */
.rd-card,
.rd-mb {
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
}

/* Tiêu đề */
.rd h1,
.rd h3,
.rd h4 {
    color: #17243b !important;
    font-weight: 800 !important;
}

/* Thanh điều hướng */
.rd-nav {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
}

.rd-nav button,
.rd-nav a {
    color: #475569 !important;
}

.rd-nav .on {
    background: #c69b45 !important;
    color: #17243b !important;
}

/* Danh sách bàn */
.rd-tb {
    background: #f8fafc !important;
    color: #1e293b !important;
    border-color: #cbd5e1 !important;
}

.rd-tb small {
    color: #64748b !important;
}

.rd-tb:hover {
    background: #fffbeb !important;
    border-color: #c69b45 !important;
}

.rd-tb.sel {
    background: #17243b !important;
    border-color: #c69b45 !important;
    color: #ffffff !important;
}

.rd-tb.sel *,
.rd-tb.sel small {
    color: #ffffff !important;
}

/* Danh mục món ăn */
.rd-mi {
    background: #ffffff !important;
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
}

.rd-mi:hover {
    background: #fffbeb !important;
    border-color: #c69b45 !important;
}

.rd-mi small {
    color: #64748b !important;
}

/* Bộ lọc và nút danh mục */
.rd-chip {
    background: #ffffff !important;
    color: #475569 !important;
    border-color: #cbd5e1 !important;
}

.rd-chip.on {
    background: #c69b45 !important;
    color: #17243b !important;
    border-color: #c69b45 !important;
}

/* Ô nhập liệu */
.rd input,
.rd select {
    background: #ffffff !important;
    color: #1e293b !important;
    border-color: #cbd5e1 !important;
}

.rd input::placeholder {
    color: #94a3b8 !important;
}

.rd input:focus,
.rd select:focus {
    border-color: #c69b45 !important;
    box-shadow: 0 0 0 3px rgba(198, 155, 69, 0.16) !important;
}

/* Nút bấm */
.rd-btn {
    background: #ffffff !important;
    color: #17243b !important;
}

.rd-btn-gold {
    background: #c69b45 !important;
    color: #17243b !important;
    border-color: #c69b45 !important;
}

.rd-btn-gold:hover {
    background: #b38936 !important;
}

/* Chi tiết hóa đơn và tổng tiền */
.rd-ln {
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

.rd-ln small {
    color: #64748b !important;
}

.rd-tot {
    color: #1e293b !important;
}

.rd-tot.big {
    color: #17243b !important;
    border-color: #c69b45 !important;
}

/* Khu vực bếp */
.rd-tk {
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid #e2e8f0 !important;
}

/* Bảng dữ liệu */
.rd-table th {
    color: #17243b !important;
}

.rd-table td {
    color: #1e293b !important;
    border-color: #e2e8f0 !important;
}

/* Thông báo */
#rdToast {
    background: #17243b !important;
    color: #ffffff !important;
}

/* Responsive */
@media(max-width: 1100px) {
    .rd-card {
        background: #ffffff !important;
    }
}
</style>

<style>
<style>
/* CHỈ ÁP DỤNG LIGHT MODE TRONG PHẠM VI .rd CỦA POS - TUYỆT ĐỐI KHÔNG ĐÈ LÊN SIDEBAR CHÍNH */
.rd {
    background-color: #f4f6f9 !important;
    color: #1e293b !important;
    border-radius: 12px;
}

.rd .rd-card,
.rd .rd-mb {
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid #dce3ec !important;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05) !important;
}

/* Đảm bảo Sidebar bên trái luôn giữ đúng màu Xanh Navy đậm & Vàng Gold chuẩn hệ thống */
.sidebar,
aside.sidebar,
.app-sidebar,
.main-sidebar,
nav.sidebar,
[class*="sidebar"]:not(.rd *) {
    background: #111c2d !important;
    color: #cbd5e1 !important;
    border-right: 1px solid #1e293b !important;
}

.sidebar a,
aside.sidebar a,
.app-sidebar a,
[class*="sidebar"]:not(.rd *) a {
    color: #cbd5e1 !important;
}

.sidebar a:hover,
.sidebar a.active,
aside.sidebar a.active,
[class*="sidebar"]:not(.rd *) a.active {
    background: rgba(198, 155, 69, 0.16) !important;
    color: #e5bd69 !important;
    border-left-color: #c69b45 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let phoneTimer = null;

    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el || el.tagName !== 'INPUT') return;

        const placeholder = (el.getAttribute('placeholder') || '').toLowerCase();
        const idOrName = ((el.id || '') + ' ' + (el.name || '')).toLowerCase();
        const isPhoneInput = placeholder.indexOf('09') !== -1 || idOrName.indexOf('phone') !== -1;

        if (!isPhoneInput) return;

        clearTimeout(phoneTimer);
        const digits = el.value.replace(/\D/g, '');

        // Tự động áp dụng ngay khi gõ đủ 9-11 số hoặc khi xóa trắng hoàn toàn ô SĐT
        if (digits.length >= 9 || digits.length === 0) {
            phoneTimer = setTimeout(function () {
                const container = el.closest('.pay-box, .pos-panel, .pos-card, div') || document;
                const buttons = container.parentElement
                    ? container.parentElement.querySelectorAll('button')
                    : document.querySelectorAll('button');

                for (let i = 0; i < buttons.length; i++) {
                    const txt = (buttons[i].textContent || '').trim().toLowerCase();
                    if (txt.indexOf('áp dụng') !== -1) {
                        buttons[i].click();
                        break;
                    }
                }
            }, 300);
        }
    });
});
</script>
<div class="crm-modal-backdrop" id="posQuickCustomerModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:2000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#ffffff;border:1px solid #dce3ec;border-radius:14px;width:min(760px,100%);overflow:hidden;box-shadow:0 20px 45px rgba(15,23,42,0.22);font-family:'Be Vietnam Pro',sans-serif;">
        <div style="background:#17243b;color:#e5bd69;padding:14px 24px;font-weight:800;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #c69b45;">
            Tạo hồ sơ khách hàng thành viên
        </div>
        <div style="padding:24px 28px;">
            <div id="posQuickCustErr" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:14px;"></div>
            <form id="posQuickCustomerForm">
                <input type="hidden" name="ajax" value="1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#17243b;margin-bottom:6px;">Họ và tên khách hàng *</label>
                        <input type="text" name="full_name" id="posQuickCustName" placeholder="Nhập đầy đủ họ và tên khách hàng" maxlength="100" required
                               style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 13px;font-size:13.5px;color:#0f172a;">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#17243b;margin-bottom:6px;">Số điện thoại di động *</label>
                        <input type="tel" name="phone" id="posQuickCustPhone" placeholder="Nhập số điện thoại 9-11 chữ số" pattern="[0-9]{9,11}" maxlength="11" required
                               style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 13px;font-size:13.5px;color:#0f172a;">
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <label style="display:block;font-size:13px;font-weight:700;color:#17243b;margin-bottom:6px;">Địa chỉ Email</label>
                    <input type="email" name="email" id="posQuickCustEmail" placeholder="vidu@email.com" maxlength="100"
                           style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 13px;font-size:13.5px;color:#0f172a;">
                </div>
                <div style="border-top:1px solid #e2e8f0;margin-top:22px;padding-top:16px;display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" onclick="closePosQuickCustModal()"
                            style="background:#ffffff;border:1px solid #94a3b8;color:#334155;font-weight:600;font-size:13px;padding:8px 18px;border-radius:7px;cursor:pointer;">
                        Hủy bỏ
                    </button>
                    <button type="submit"
                            style="background:#c69b45;border:1px solid #c69b45;color:#17243b;font-weight:800;font-size:13.5px;padding:9px 18px;border-radius:8px;cursor:pointer;">
                        Tạo hồ sơ khách hàng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPosQuickCustModal(prefillPhone) {
    const modal = document.getElementById('posQuickCustomerModal');
    const errBox = document.getElementById('posQuickCustErr');
    errBox.style.display = 'none';
    document.getElementById('posQuickCustName').value = '';
    document.getElementById('posQuickCustPhone').value = prefillPhone || '';
    document.getElementById('posQuickCustEmail').value = '';
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('posQuickCustName').focus(), 50);
}

function closePosQuickCustModal() {
    document.getElementById('posQuickCustomerModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('posQuickCustomerForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const errBox = document.getElementById('posQuickCustErr');
            const formData = new FormData(form);

            fetch('index.php?c=customer&a=index', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    closePosQuickCustModal();
                    const phoneInputs = document.querySelectorAll('input[placeholder*="09"], input[name*="phone"], input[id*="phone"]');
                    phoneInputs.forEach(inp => { inp.value = res.phone; });
                    document.querySelectorAll('button').forEach(btn => {
                        if ((btn.textContent || '').trim().toLowerCase().indexOf('áp dụng') !== -1) {
                            btn.click();
                        }
                    });
                } else {
                    errBox.textContent = res.message || 'Không thể lưu hồ sơ khách hàng.';
                    errBox.style.display = 'block';
                }
            })
            .catch(() => {
                errBox.textContent = 'Lỗi kết nối máy chủ.';
                errBox.style.display = 'block';
            });
        });
    }

    // Tự động gắn nút mở Popup khi tìm không thấy SĐT trên màn hình POS
    const observer = new MutationObserver(function () {
        document.querySelectorAll('*').forEach(function (el) {
            if (el.children.length === 0 && (el.textContent || '').indexOf('Không tìm thấy khách hàng với số điện thoại này') !== -1) {
                if (!el.parentElement.querySelector('.btn-pos-quick-crm')) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn-pos-quick-crm';
                    btn.textContent = 'Tạo hồ sơ khách hàng mới';
                    btn.style.cssText = 'margin-top:6px;display:inline-block;background:#c69b45;color:#17243b;border:1px solid #b38936;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:800;cursor:pointer;';
                    btn.onclick = function () {
                        const phoneInp = document.querySelector('input[placeholder*="09"], input[name*="phone"], input[id*="phone"]');
                        openPosQuickCustModal(phoneInp ? phoneInp.value.replace(/\D/g, '') : '');
                    };
                    el.insertAdjacentElement('afterend', btn);
                }
            }
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>
<?php
// Lấy danh sách ảnh món ăn từ bảng menu_items để đồng bộ lên thẻ món POS
$posMenuImages = [];
try {
    $dbPosImg = Database::getConnection();
    $rowsPosImg = $dbPosImg->query("SELECT id, item_code, name, unit, image_url FROM menu_items")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsPosImg as $rImg) {
        $posMenuImages[] = [
            'id'        => (int)$rImg['id'],
            'item_code' => (string)$rImg['item_code'],
            'name'      => mb_strtolower(trim((string)$rImg['name']), 'UTF-8'),
            'unit'      => (string)$rImg['unit'],
            'image_url' => (string)($rImg['image_url'] ?? '')
        ];
    }
} catch (Throwable $e) {}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const posMenuCatalog = <?= json_encode($posMenuImages, JSON_UNESCAPED_UNICODE) ?>;
    const byName = {};
    const byId = {};

    posMenuCatalog.forEach(function (item) {
        byName[item.name] = item;
        byId[item.id] = item;
    });

    function decoratePosMenuCards() {
        document.querySelectorAll('.rd-mi').forEach(function (card) {
            if (card.querySelector('.rd-mi-thumb')) return;

            let matched = null;
            const dataId = card.getAttribute('data-id') || card.getAttribute('data-item-id');
            if (dataId && byId[parseInt(dataId, 10)]) {
                matched = byId[parseInt(dataId, 10)];
            } else {
                const titleEl = card.querySelector('b, strong') || card.firstElementChild;
                const dishName = titleEl ? (titleEl.textContent || '').trim().toLowerCase() : '';
                if (dishName && byName[dishName]) {
                    matched = byName[dishName];
                }
            }

            const thumbBox = document.createElement('div');
            thumbBox.className = 'rd-mi-thumb';

            if (matched && matched.image_url && matched.image_url.trim() !== '') {
                const img = document.createElement('img');
                img.src = matched.image_url;
                img.alt = matched.name;
                img.onerror = function () {
                    thumbBox.innerHTML = '<div class="rd-mi-thumb-fallback"><strong>' + (matched.item_code || '5 SAO') + '</strong><span>' + (matched.unit || 'Phần') + '</span></div>';
                };
                thumbBox.appendChild(img);
            } else {
                const codeLabel = matched && matched.item_code ? matched.item_code : 'ROYALDINE';
                const unitLabel = matched && matched.unit ? matched.unit : 'Fine Dining';
                thumbBox.innerHTML = '<div class="rd-mi-thumb-fallback"><strong>' + codeLabel + '</strong><span>' + unitLabel + '</span></div>';
            }

            card.insertBefore(thumbBox, card.firstChild);
        });
    }

    decoratePosMenuCards();

    // Tự động gắn lại hình ảnh khi người dùng bấm lọc Danh mục hoặc Tìm kiếm món trên POS
    const posObserver = new MutationObserver(function () {
        decoratePosMenuCards();
    });
    posObserver.observe(document.body, { childList: true, subtree: true });
});
</script>