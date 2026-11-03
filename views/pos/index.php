<?php
require __DIR__ . '/_style.php';
require_once __DIR__ . '/../../config/Database.php';

$rdUserName = htmlspecialchars($_SESSION['user']['full_name'] ?? 'Thu ngân POS', ENT_QUOTES, 'UTF-8');
$rdUserId   = (int)($_SESSION['user']['id'] ?? 1);

$rdState = ['tables' => [], 'menu' => [], 'orders' => [], 'promos' => [], 'error' => null];
try {
    $db  = Database::getConnection();
    $all = function ($sql) use ($db) { return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC); };

    $rdState['tables'] = $all("SELECT dt.id, dt.table_name, dt.capacity, dt.status, a.name AS area_name, a.surcharge_percent
                               FROM dining_tables dt JOIN areas a ON dt.area_id = a.id ORDER BY a.id, dt.table_name");
    $rdState['menu']   = $all("SELECT mi.id, mi.name, mi.price, c.name AS category, c.course_type
                               FROM menu_items mi JOIN categories c ON mi.category_id = c.id
                               WHERE mi.status = 'Available' AND c.is_active = 1 ORDER BY c.display_order, mi.name");

    $orders = $all("SELECT id, order_code, table_id FROM orders WHERE status = 'Open'");
    $items  = $all("SELECT od.id, od.order_id, od.menu_item_id, mi.name, od.quantity, od.unit_price, od.modifier_notes, od.kds_status,
                           od.cancel_reason, UNIX_TIMESTAMP(od.ordered_at) AS ts,
                           TIMESTAMPDIFF(MINUTE, od.ordered_at, NOW()) AS wait, c.course_type
                    FROM order_details od
                    JOIN orders o ON od.order_id = o.id AND o.status = 'Open'
                    JOIN menu_items mi ON od.menu_item_id = mi.id
                    JOIN categories c ON mi.category_id = c.id
                    ORDER BY od.ordered_at, od.id");
    foreach ($orders as &$o) {
        $o['items'] = array_values(array_filter($items, function ($i) use ($o) { return $i['order_id'] == $o['id']; }));
    }
    unset($o);
    $rdState['orders'] = $orders;

    $rdState['promos'] = $all("SELECT code, name, discount_type, discount_value, max_discount, min_order_value, min_tier
                               FROM promotions WHERE is_active = 1 AND NOW() BETWEEN start_date AND end_date ORDER BY code");
} catch (Exception $e) {
    error_log('[POS view] ' . $e->getMessage());
    $rdState['error'] = 'Không đọc được dữ liệu bàn/thực đơn từ cơ sở dữ liệu.';
}
?>
<style>.rd-tgrid{grid-template-columns:repeat(2,1fr)}</style>
<div class="rd">
    <div class="rd-head">
        <div>
            <h1>RoyalDine 5S</h1>
            <small>Bán hàng POS, điều phối Bếp/Bar và thanh toán · Nhân viên: <b><?= $rdUserName ?></b></small>
        </div>
        <div class="rd-nav">
            <button class="on" data-p="pos" onclick="showPane('pos')">Gọi món</button>
            <button data-p="kds" onclick="showPane('kds')">Bếp / Bar <span class="rd-badge-n" id="kdsCount">0</span></button>
            <button data-p="pay" onclick="showPane('pay')">Thanh toán</button>
            <a href="index.php?c=pos&a=report">Báo cáo</a>
        </div>
    </div>

    <section class="rd-pane on" id="pane-pos">
        <div class="rd-grid pos">
            <aside class="rd-card">
                <h3>Sơ đồ bàn</h3>
                <div class="rd-lg">
                    <span><i style="background:var(--ok)"></i>Trống</span>
                    <span><i style="background:var(--wn)"></i>Đang dùng</span>
                    <span><i style="background:var(--g)"></i>Tên vàng = khu VIP</span>
                </div>
                <div class="rd-tgrid" id="tbl"></div>
                <p class="rd-hint" style="margin-top:10px">Chọn bàn trống để mở hóa đơn mới, bàn đang dùng để gọi thêm món.</p>
            </aside>
            <div class="rd-card">
                <div class="rd-row" style="justify-content:space-between;margin-top:0">
                    <h3 style="margin:0">Thực đơn</h3>
                    <input id="q" placeholder="Tìm món..." oninput="rMenu()" style="max-width:220px">
                </div>
                <div class="rd-chips" id="cats" style="margin-top:12px"></div>
                <div class="rd-mgrid" id="menu"></div>
            </div>
            <aside class="rd-card" id="ord"></aside>
        </div>
    </section>

    <section class="rd-pane" id="pane-kds">
        <div class="rd-chips" id="stn"></div>
        <p class="rd-hint">Món gọi sớm nhất lên đầu. Thẻ chờ quá 15 phút báo đỏ. Tự làm mới mỗi 10 giây.</p>
        <div class="kcols" id="kc"></div>
    </section>

    <section class="rd-pane" id="pane-pay">
        <div class="rd-grid pay">
            <aside class="rd-card"><h3>Bàn đang dùng bữa</h3><div id="payList"></div></aside>
            <div class="rd-card" id="bill"></div>
            <aside class="rd-card" id="payCtl"></aside>
        </div>
    </section>

    <div class="rd-md" id="rdModal"></div>
    <div id="rdToast" role="status"></div>
</div>

<script id="rdState" type="application/json"><?= json_encode($rdState, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
const API = 'index.php?c=pos&a=';
const UID = <?= $rdUserId ?>;
// Tài khoản nhận tiền VietQR - giống OrderModel của bạn, sửa thành tài khoản thật
const BANK = {id: 'MB', acc: '0901000003', name: 'ROYALDINE 5S'};
const TIERS = [['Không áp dụng', 'Standard', 0], ['Silver · 3%', 'Silver', .03], ['Gold · 5%', 'Gold', .05], ['Diamond · 10%', 'Diamond', .10]];
const RANK = {Standard: 0, Silver: 1, Gold: 2, Diamond: 3};
const STL = {Waiting: 'Chờ chế biến', Cooking: 'Đang nấu', Ready: 'Sẵn sàng', Cancelled: 'Đã hủy'};

const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => Math.round(Number(n) || 0).toLocaleString('vi-VN') + '₫';
const sc = c => '#' + String(c || '').slice(-5);

let S = JSON.parse($('#rdState').textContent);
let curId = null, cat = 'Tất cả', stn = 'ALL', payId = null, pm = 'Cash', PT = 0, PV = '';
const DRAFTS = {};

const curT = () => S.tables.find(t => t.id == curId);
const ordOf = tid => S.orders.find(o => o.table_id == tid);
const live = o => o.items.filter(i => i.kds_status != 'Cancelled');
const sumO = o => live(o).reduce((s, i) => s + i.quantity * i.unit_price, 0);

async function api(a, body) {
    try {
        const r = await fetch(API + a, {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)});
        return await r.json();
    } catch (e) { return {status: 'error', message: 'Không kết nối được máy chủ hoặc máy chủ trả dữ liệu lỗi.'}; }
}
// Lấy lại trạng thái mới nhất bằng cách tải lại chính trang này rồi đọc thẻ #rdState (không cần API mới)
async function refreshState() {
    try {
        const r = await fetch(location.href.split('#')[0], {credentials: 'same-origin'});
        const el = new DOMParser().parseFromString(await r.text(), 'text/html').getElementById('rdState');
        if (!el) { toast('Phiên đăng nhập đã hết, hãy tải lại trang.', 1); return; }
        S = JSON.parse(el.textContent);
        if (S.error) toast(S.error, 1);
    } catch (e) { /* mất mạng thoáng qua: giữ dữ liệu cũ */ }
}
function toast(m, bad) {
    const t = $('#rdToast'); t.textContent = m; t.className = bad ? 'bad' : ''; t.style.display = 'block';
    clearTimeout(t.h); t.h = setTimeout(() => t.style.display = 'none', 3200);
}
function modal(title, body, ok, fn) {
    const m = $('#rdModal');
    m.innerHTML = `<div class="rd-mb"><h3>${title}</h3>${body}<div class="rd-er" id="rdEr"></div>
        <div class="rd-row end"><button class="rd-btn rd-btn-line" onclick="closeM()">Đóng</button><button class="rd-btn rd-btn-gold" id="rdOk">${ok}</button></div></div>`;
    m.classList.add('on');
    $('#rdOk').onclick = async () => {
        const b = $('#rdOk'); b.disabled = true;
        const e = await fn();
        if (e) { $('#rdEr').textContent = e; b.disabled = false; } else closeM();
    };
}
const closeM = () => $('#rdModal').classList.remove('on');
function showPane(p) {
    document.querySelectorAll('.rd-pane').forEach(x => x.classList.toggle('on', x.id == 'pane-' + p));
    document.querySelectorAll('.rd-nav [data-p]').forEach(b => b.classList.toggle('on', b.dataset.p == p));
    renderAll();
}
const paneOn = p => $('#pane-' + p).classList.contains('on');

/* ---------------- Sơ đồ bàn ---------------- */
function rTables() {
    $('#tbl').innerHTML = S.tables.map(t => {
        const o = ordOf(t.id);
        return `<button class="rd-tb ${o ? 'busy' : 'free'} ${+t.surcharge_percent > 0 ? 'vip' : ''} ${curId == t.id ? 'sel' : ''}" onclick="pickTable(${t.id})">
            <b>${esc(t.table_name)}</b><small>${o ? esc(sc(o.order_code)) : t.capacity + ' chỗ'}</small></button>`;
    }).join('') || '<div class="rd-empty">Chưa có bàn nào.</div>';
}
function pickTable(id) { curId = id; rTables(); rOrder(); }

/* ---------------- Thực đơn ---------------- */
function rMenu() {
    const cats = ['Tất cả', ...new Set(S.menu.map(m => m.category))];
    $('#cats').innerHTML = cats.map(c => `<button class="rd-chip ${c == cat ? 'on' : ''}" data-c="${esc(c)}" onclick="cat=this.dataset.c;rMenu()">${esc(c)}</button>`).join('');
    const q = $('#q').value.trim().toLowerCase();
    $('#menu').innerHTML = S.menu.filter(m => (cat == 'Tất cả' || m.category == cat) && m.name.toLowerCase().includes(q)).map(m =>
        `<button class="rd-mi" onclick="tap(${m.id})"><b>${esc(m.name)}</b>
        <small>${m.course_type == 'Beverage' ? 'Quầy Bar' : 'Bếp'}${m.course_type == 'Main_Course' ? ' · chọn độ chín' : ''}</small><span>${money(m.price)}</span></button>`).join('')
        || '<div class="rd-empty">Không có món phù hợp.</div>';
}
function tap(id) {
    if (!curId) return toast('Hãy chọn bàn trước khi gọi món', 1);
    const m = S.menu.find(x => x.id == id);
    if (m.course_type == 'Main_Course') {
        const DONE = ['Tái (Rare)', 'Tái vừa (Medium rare)', 'Chín vừa (Medium)', 'Chín tới (Medium well)', 'Chín kỹ (Well done)'];
        modal(esc(m.name), `<label>Độ chín</label><select id="dn"><option value="">Không ghi độ chín</option>${DONE.map(d => `<option>${d}</option>`).join('')}</select>
            <label>Ghi chú cho bếp</label><input id="nt" maxlength="200" placeholder="Ví dụ: dị ứng hải sản, ít muối">`, 'Thêm vào hóa đơn',
            () => { addDraft(m, [$('#dn').value, $('#nt').value.trim()].filter(Boolean).join(' · ')); });
    } else addDraft(m, '');
}
function addDraft(m, notes) {
    const a = DRAFTS[curId] = DRAFTS[curId] || [];
    const x = a.find(d => d.id == m.id && d.notes == notes);
    if (x) x.qty++; else a.push({id: m.id, name: m.name, price: +m.price, qty: 1, notes});
    rOrder();
}
function dq(i, d) { const a = DRAFTS[curId]; a[i].qty += d; if (a[i].qty < 1) a.splice(i, 1); rOrder(); }
function dnote(i) {
    const x = DRAFTS[curId][i];
    modal('Ghi chú món', `<input id="nt" maxlength="200" value="${esc(x.notes)}">`, 'Lưu', () => { x.notes = $('#nt').value.trim(); rOrder(); });
}

/* ---------------- Hóa đơn của bàn ---------------- */
// Không có cột "lần gọi" trong DB gốc: các món cách nhau quá 20 giây được tính là lần gọi khác
function batches(items) {
    const out = []; let last = null;
    items.forEach(i => { if (last === null || i.ts - last > 20) out.push([]); out[out.length - 1].push(i); last = i.ts; });
    return out;
}
function sentRow(i) {
    const c = i.kds_status == 'Cancelled';
    return `<div class="rd-ln ${c ? 'void' : ''}"><div><span class="nm">${i.quantity}× ${esc(i.name)}</span> <span class="rd-bg s-${i.kds_status}">${STL[i.kds_status] || i.kds_status}</span></div>
        <div>${money(i.quantity * i.unit_price)}</div>
        <small>${esc(i.modifier_notes || '')}${c ? ' · Lý do hủy: ' + esc(i.cancel_reason) : ''}&nbsp;</small>
        <div>${i.kds_status == 'Waiting' ? `<button class="rd-btn rd-btn-del" onclick="cancelM(${i.id})">Hủy món</button>` : ''}</div></div>`;
}
function draftRow(d, i) {
    return `<div class="rd-ln"><div><span class="nm">${esc(d.name)}</span></div><div>${money(d.qty * d.price)}</div>
        <small>${esc(d.notes || 'Chưa có ghi chú')} · <a onclick="dnote(${i})">ghi chú</a></small>
        <div class="rd-q"><button onclick="dq(${i},-1)" aria-label="Giảm">−</button>${d.qty}<button onclick="dq(${i},1)" aria-label="Tăng">+</button></div></div>`;
}
function rOrder() {
    const el = $('#ord'), t = curT();
    if (!t) { el.innerHTML = '<h3>Hóa đơn</h3><div class="rd-empty">Chọn một bàn để bắt đầu gọi món.</div>'; return; }
    const o = ordOf(t.id), dr = DRAFTS[t.id] || [], sur = +t.surcharge_percent;
    let h = `<h3>Bàn ${esc(t.table_name)}</h3><div class="rd-hint">${esc(t.area_name)}${sur > 0 ? ' · phụ thu ' + sur + '%' : ''}${o ? ' · ' + esc(sc(o.order_code)) : ''}</div>`;
    if (o) {
        h += `<div class="rd-row"><button class="rd-btn rd-btn-line" onclick="transferM('transfer')">Chuyển bàn</button><button class="rd-btn rd-btn-line" onclick="transferM('merge')">Gộp bàn</button></div>`;
        batches(o.items).forEach((b, k) => { h += `<h4>Lần gọi ${k + 1}</h4>` + b.map(sentRow).join(''); });
    }
    if (dr.length) h += '<h4>Món mới, chưa gửi bếp</h4>' + dr.map(draftRow).join('');
    if (!o && !dr.length) h += '<div class="rd-empty">Bàn trống. Chạm vào món để mở hóa đơn.</div>';
    const sent = o ? sumO(o) : 0, pend = dr.reduce((s, d) => s + d.qty * d.price, 0);
    h += `<div style="margin-top:12px"><div class="rd-tot"><span>Đã gửi bếp</span><span>${money(sent)}</span></div>
        ${dr.length ? `<div class="rd-tot"><span>Chờ gửi</span><span>${money(pend)}</span></div>` : ''}
        <div class="rd-tot big"><span>Tạm tính</span><span>${money(sent + pend)}</span></div></div>
        <div class="rd-row end"><button class="rd-btn rd-btn-gold" ${dr.length ? '' : 'disabled'} onclick="sendOrder()">Gửi xuống Bếp/Bar (${dr.reduce((s, d) => s + d.qty, 0)} món)</button>
        ${o ? `<button class="rd-btn rd-btn-line" onclick="goPay(${o.id})">Thanh toán</button>` : ''}</div>
        <p class="rd-hint">Phụ thu, phí phục vụ, giảm giá và VAT được tính ở bước Thanh toán.</p>`;
    el.innerHTML = h;
}
async function sendOrder() {
    const t = curT(), dr = DRAFTS[t.id] || [];
    const r = await api('createOrder', {table_id: t.id, waiter_id: UID,
        items: dr.map(d => ({menu_item_id: d.id, quantity: d.qty, unit_price: d.price, modifier_notes: d.notes}))});
    if (r.status != 'success') return toast(r.message || 'Gửi order thất bại', 1);
    DRAFTS[t.id] = [];
    toast('Đã gửi order bàn ' + t.table_name + ' xuống Bếp/Bar');
    await refreshAll();
}

/* ---------------- Hủy món / Chuyển bàn / Gộp bàn ---------------- */
function findItem(id) {
    for (const o of S.orders) { const i = o.items.find(x => x.id == id); if (i) return i; }
    return {};
}
function cancelM(id) {
    const it = findItem(id);
    modal('Hủy món', `<p>${esc(it.quantity || '')}× ${esc(it.name || '')}. Chỉ hủy được khi Bếp/Bar chưa làm. Lý do được ghi vào nhật ký.</p>
        <label>Lý do hủy (bắt buộc)</label><select id="rs"><option value="">Chọn lý do</option><option>Khách đổi ý</option><option>Nhập nhầm món</option><option>Hết nguyên liệu</option><option>Khác</option></select>
        <label>Ghi chú thêm</label><input id="rn" maxlength="150" placeholder="Bắt buộc nếu chọn Khác">`, 'Xác nhận hủy', async () => {
        const r = $('#rs').value, n = $('#rn').value.trim();
        if (!r) return 'Vui lòng chọn lý do hủy.';
        if (r == 'Khác' && n.length < 5) return 'Vui lòng ghi rõ lý do (ít nhất 5 ký tự).';
        const res = await api('cancelItem', {order_detail_id: id, reason: r + (n ? ': ' + n : '')});
        if (res.status != 'success') return res.message;
        toast(res.message); refreshAll();
    });
}
function transferM(mode) {
    const t = curT(), m = mode == 'merge';
    const opts = S.tables.filter(x => x.id != t.id && (m ? ordOf(x.id) : (!ordOf(x.id) && x.status == 'Available')));
    modal(m ? 'Gộp bàn' : 'Chuyển bàn',
        `<p>${m ? 'Gộp toàn bộ món của bàn ' + esc(t.table_name) + ' vào bàn đang có khách (dùng cho khách đi đoàn).' : 'Chuyển bàn ' + esc(t.table_name) + ' sang bàn trống. Món đã gửi bếp giữ nguyên trạng thái, phụ thu tự đổi theo khu vực mới.'}</p>
        <label>${m ? 'Gộp vào bàn' : 'Bàn đích'}</label><select id="to">${opts.map(x => `<option value="${x.id}">${esc(x.table_name)} · ${esc(x.area_name)}${+x.surcharge_percent > 0 ? ' · phụ thu ' + (+x.surcharge_percent) + '%' : ''}${m ? '' : ' · ' + x.capacity + ' chỗ'}</option>`).join('')}</select>`,
        m ? 'Gộp bàn' : 'Chuyển bàn', async () => {
            const to = +$('#to').value;
            if (!to) return m ? 'Không có bàn nào đang mở để gộp.' : 'Không còn bàn trống.';
            const r = await api('transferTable', {from_table_id: t.id, to_table_id: to});
            if (r.status != 'success') return r.message;
            DRAFTS[to] = (DRAFTS[to] || []).concat(DRAFTS[t.id] || []); delete DRAFTS[t.id];
            toast(r.message); curId = to; await refreshAll();
        });
}

/* ---------------- KDS Bếp / Bar ---------------- */
function kdsItems() {
    const out = [];
    S.orders.forEach(o => { const t = S.tables.find(x => x.id == o.table_id);
        o.items.forEach(i => { if (['Waiting', 'Cooking', 'Ready'].includes(i.kds_status))
            out.push({...i, table_name: t ? t.table_name : '?', station: i.course_type == 'Beverage' ? 'Bar' : 'Kitchen'}); }); });
    return out.sort((a, b) => a.ts - b.ts || a.id - b.id);
}
function rKDS() {
    const all = kdsItems();
    $('#kdsCount').textContent = all.filter(i => i.kds_status == 'Waiting').length;
    if (!paneOn('kds')) return;
    $('#stn').innerHTML = [['ALL', 'Tất cả'], ['Kitchen', 'Bếp'], ['Bar', 'Bar']].map(a => `<button class="rd-chip ${stn == a[0] ? 'on' : ''}" onclick="stn='${a[0]}';rKDS()">${a[1]}</button>`).join('');
    const L = all.filter(i => stn == 'ALL' || i.station == stn);
    const col = (st, title, btn, to) => {
        let l = L.filter(i => i.kds_status == st), more = 0;
        if (st == 'Ready' && l.length > 12) { more = l.length - 12; l = l.slice(-12); }   // DB gốc không có bước "đã bưng" nên chỉ hiện 12 món xong gần nhất
        return `<div class="rd-card"><h3>${title} (${L.filter(i => i.kds_status == st).length})</h3>` + (l.length ? l.map(i => {
            const late = i.wait >= 15 && st != 'Ready';
            return `<div class="rd-tk ${late ? 'late' : ''}"><div class="tt"><span>Bàn ${esc(i.table_name)}</span><span>${i.wait} phút</span></div>
                <div class="qty">${i.quantity}× ${esc(i.name)}</div><small>${esc(i.modifier_notes || 'Không ghi chú')} · ${i.station == 'Bar' ? 'Bar' : 'Bếp'}</small>
                ${to ? `<button class="rd-btn rd-btn-gold" onclick="adv(${i.id},'${to}')">${btn}</button>` : `<button class="rd-btn rd-btn-line" disabled>${btn}</button>`}
                ${st == 'Waiting' ? `<button class="rd-btn rd-btn-del" onclick="cancelM(${i.id})">Hủy món</button>` : ''}</div>`;
        }).join('') : '<div class="rd-empty">Không có món</div>') + (more ? `<div class="rd-hint">… và ${more} món xong trước đó</div>` : '') + '</div>';
    };
    $('#kc').innerHTML = col('Waiting', 'Chờ chế biến', 'Bắt đầu nấu', 'Cooking') + col('Cooking', 'Đang nấu', 'Hoàn thành · Báo phục vụ', 'Ready') + col('Ready', 'Sẵn sàng bưng', 'Đã xong, chờ phục vụ bưng', '');
}
async function adv(id, to) {
    const i = kdsItems().find(x => x.id == id);
    const r = await api('updateKds', {order_detail_id: id, status: to});
    if (r.status != 'success') toast(r.message || 'Cập nhật thất bại', 1);
    else if (to == 'Ready' && i) toast(`Báo phục vụ: bàn ${i.table_name} có ${i.quantity}× ${i.name} sẵn sàng`);
    await refreshAll();
}

/* ---------------- Thanh toán ---------------- */
// Cùng công thức với OrderModel::checkoutOrder: món + phụ thu khu vực + 5% phục vụ - giảm giá + 8% VAT
function calc(o, t) {
    const sub = sumO(o), sur = Math.round(sub * (+t.surcharge_percent) / 100), sv = Math.round((sub + sur) * .05);
    const tr = TIERS[PT], td = Math.round((sub + sur) * tr[2]);
    let vd = 0, verr = '';
    const p = S.promos.find(x => x.code == PV);
    if (PV && p) {
        if (sub < +p.min_order_value) verr = 'Đơn tối thiểu ' + money(p.min_order_value) + ' mới dùng được voucher này.';
        else if (RANK[tr[1]] < RANK[p.min_tier]) verr = 'Voucher chỉ dành cho hạng ' + p.min_tier + ' trở lên.';
        else {
            const base = sub + sur - td;
            let d = p.discount_type == 'Percent' ? base * p.discount_value / 100 : +p.discount_value;
            if (p.discount_type == 'Percent' && p.max_discount !== null) d = Math.min(d, +p.max_discount);
            vd = Math.round(Math.max(0, Math.min(d, base)));
        }
    }
    const disc = td + vd, tx = sub + sur + sv - disc, vat = Math.round(tx * .08);
    return {sub, sur, sv, td, vd, verr, disc, vat, total: tx + vat};
}
function rPayList() {
    const l = S.orders.map(o => ({o, t: S.tables.find(x => x.id == o.table_id)})).filter(x => x.t);
    $('#payList').innerHTML = l.length ? l.map(({o, t}) => `<button class="rd-tb busy wide ${+t.surcharge_percent > 0 ? 'vip' : ''} ${payId == o.id ? 'sel' : ''}" onclick="goPay(${o.id})"><b>Bàn ${esc(t.table_name)}</b><small>${esc(sc(o.order_code))} · ${money(sumO(o))}</small></button>`).join('')
        : '<div class="rd-empty">Không có bàn nào đang dùng bữa.</div>';
}
function goPay(id) { if (payId != id) { PT = 0; PV = ''; } payId = id; showPane('pay'); }
function rBill() {
    const el = $('#bill'), c = $('#payCtl');
    const o = S.orders.find(x => x.id == payId), t = o && S.tables.find(x => x.id == o.table_id);
    if (!o || !t) { payId = null; el.innerHTML = '<div class="rd-empty">Chọn một bàn bên trái để xem hóa đơn.</div>'; c.innerHTML = ''; return; }
    const b = calc(o, t), items = live(o), unfinished = items.filter(i => ['Waiting', 'Cooking'].includes(i.kds_status)).length;
    const L = (a, v, x = '') => `<div class="rd-tot ${x}"><span>${a}</span><span>${v}</span></div>`;
    el.innerHTML = `<div class="rd-bh"><h3 style="margin:0">RoyalDine 5S</h3><small>Bàn ${esc(t.table_name)} · ${esc(sc(o.order_code))}</small></div>`
        + items.map(i => L(`${i.quantity}× ${esc(i.name)}${i.modifier_notes ? ` <small style="color:var(--mu)">(${esc(i.modifier_notes)})</small>` : ''}`, money(i.quantity * i.unit_price))).join('') + '<hr>'
        + L('Tổng tiền món', money(b.sub))
        + (b.sur > 0 ? L(`Phụ thu ${esc(t.area_name)} (${+t.surcharge_percent}%)`, money(b.sur)) : '')
        + L('Phí phục vụ (5%)', money(b.sv))
        + (b.td > 0 ? L(`Giảm giá hạng ${esc(TIERS[PT][1])}`, '− ' + money(b.td)) : '')
        + (b.vd > 0 ? L(`Giảm giá voucher ${esc(PV)}`, '− ' + money(b.vd)) : '')
        + L('Thuế VAT (8%)', money(b.vat))
        + L('KHÁCH CẦN THANH TOÁN', money(b.total), 'big');
    const qr = `https://img.vietqr.io/image/${BANK.id}-${BANK.acc}-compact2.png?amount=${b.total}&addInfo=${encodeURIComponent('THANH TOAN ' + o.order_code)}&accountName=${encodeURIComponent(BANK.name)}`;
    c.innerHTML = `<h3>Thanh toán</h3>
        <label>Hạng thẻ thành viên</label><select onchange="PT=+this.value;rBill()">${TIERS.map((x, i) => `<option value="${i}" ${PT == i ? 'selected' : ''}>${x[0]}</option>`).join('')}</select>
        <label>Voucher</label><select onchange="PV=this.value;rBill()"><option value="">Không dùng voucher</option>${S.promos.map(p => `<option value="${esc(p.code)}" ${PV == p.code ? 'selected' : ''}>${esc(p.code)} · ${esc(p.name)}</option>`).join('')}</select>
        ${b.verr ? `<div class="rd-er">${esc(b.verr)}</div>` : ''}
        <h4>Phương thức thanh toán</h4>
        <div class="rd-chips">${[['Cash', 'Tiền mặt'], ['VietQR', 'VietQR'], ['CreditCard', 'Thẻ']].map(a => `<button class="rd-chip ${pm == a[0] ? 'on' : ''}" onclick="pm='${a[0]}';rBill()">${a[1]}</button>`).join('')}</div>
        ${pm == 'VietQR' ? `<div class="rd-qr"><img src="${esc(qr)}" alt="Mã VietQR"><b>${money(b.total)}</b><span>Nội dung: THANH TOAN ${esc(o.order_code)}</span></div>` : ''}
        ${unfinished ? `<div class="rd-er">Còn ${unfinished} món Bếp/Bar chưa làm xong, chưa thể thanh toán.</div>` : ''}
        <div class="rd-row end"><button class="rd-btn rd-btn-gold" ${(b.verr || unfinished || b.sub <= 0) ? 'disabled' : ''} onclick="payM()">Xác nhận đã thu</button></div>`;
}
function payM() {
    const o = S.orders.find(x => x.id == payId), t = S.tables.find(x => x.id == o.table_id), b = calc(o, t);
    const hint = pm == 'Cash' ? 'Hãy nhận đủ tiền mặt trước khi xác nhận.' : pm == 'VietQR' ? 'Chỉ xác nhận khi đã thấy tiền về tài khoản.' : 'Chỉ xác nhận khi máy POS thẻ báo thành công.';
    modal('Xác nhận thanh toán', `<p>Bàn ${esc(t.table_name)} · ${esc(sc(o.order_code))}</p><div class="rd-tot big"><span>Cần thu</span><span>${money(b.total)}</span></div><p class="rd-hint">${hint}</p>`, 'Đã thu đủ tiền', async () => {
        const id = payId;
        const r = await api('checkout', {order_id: id, cashier_id: UID, payment_method: pm, discount_amount: b.disc});
        if (r.status != 'success') return r.message || 'Thanh toán thất bại';
        toast('Đã thu ' + money(r.total_amount));
        window.open(`index.php?c=pos&a=printBill&order_id=${id}`, '_blank', 'width=450,height=700');
        payId = null; PT = 0; PV = ''; curId = null;
        await refreshAll(); showPane('pos');
    });
}

/* ---------------- Khởi động & tự làm mới ---------------- */
function renderAll() { rTables(); rMenu(); rOrder(); rKDS(); rPayList(); if (paneOn('pay')) rBill(); }
async function refreshAll() { await refreshState(); renderAll(); }
renderAll();
setInterval(async () => {
    if (document.hidden || $('#rdModal').classList.contains('on')) return;
    await refreshState();
    rTables(); rKDS(); rPayList();
    if (paneOn('pos')) rOrder();
}, 10000);
</script>