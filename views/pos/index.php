<?php
require __DIR__ . '/_style.php';
require_once __DIR__ . '/../../config/Database.php';

$rdUserName = htmlspecialchars($_SESSION['user']['full_name'] ?? 'Thu ngân POS', ENT_QUOTES, 'UTF-8');

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

    // Danh sách voucher còn hiệu lực để chọn nhanh
    $rdState['promos'] = $all("SELECT code, name FROM promotions WHERE is_active = 1 AND NOW() BETWEEN start_date AND end_date ORDER BY code");
} catch (Exception $e) {
    error_log('[POS view] ' . $e->getMessage());
    $rdState['error'] = 'Không đọc được dữ liệu bàn/thực đơn từ cơ sở dữ liệu.';
}
?>
<style>.rd-tgrid{grid-template-columns:repeat(2,1fr)}.rd-note{margin-top:4px}</style>
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
                    <span><i style="background:#5b9bd5"></i>Đã đặt</span>
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

    <datalist id="noteList">
        <option value="Ít cay"><option value="Không cay"><option value="Không hành"><option value="Không tỏi"><option value="Ít muối">
        <option value="Ít đường"><option value="Không đá"><option value="Dị ứng hải sản"><option value="Dị ứng đậu phộng">
        <option value="Mang ra trước"><option value="Mang ra sau món chính">
    </datalist>
    <div class="rd-md" id="rdModal"></div>
    <div id="rdToast" role="status"></div>
</div>

<script id="rdState" type="application/json"><?= json_encode($rdState, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<script>
const API = 'index.php?c=pos&a=';
const STL = {Waiting: 'Chờ chế biến', Cooking: 'Đang nấu', Ready: 'Sẵn sàng', Served: 'Đã phục vụ', Cancelled: 'Đã hủy'};
const DONE = ['Tái (Rare)', 'Tái vừa (Medium rare)', 'Chín vừa (Medium)', 'Chín tới (Medium well)', 'Chín kỹ (Well done)'];

const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => Math.round(Number(n) || 0).toLocaleString('vi-VN') + '₫';
const sc = c => '#' + String(c || '').slice(-5);

let S = JSON.parse($('#rdState').textContent);
let curId = null, cat = 'Tất cả', stn = 'ALL', payId = null, PB = null, pm = 'Cash', PH = '', VC = '', REF = '';
const DRAFTS = {};   // món đã chọn nhưng chưa gửi bếp, theo từng bàn

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
async function apiGet(a, qs) {
    try { const r = await fetch(API + a + (qs || ''), {credentials: 'same-origin'}); return await r.json(); }
    catch (e) { return {status: 'error', message: 'Không kết nối được máy chủ hoặc máy chủ trả dữ liệu lỗi.'}; }
}
// Lấy lại trạng thái mới nhất bằng cách tải lại chính trang này rồi đọc thẻ #rdState
async function refreshState() {
    try {
        const r = await fetch(location.href.split('#')[0], {credentials: 'same-origin'});
        const el = new DOMParser().parseFromString(await r.text(), 'text/html').getElementById('rdState');
        if (!el) { toast('Phiên đăng nhập đã hết, hãy tải lại trang.', 1); return; }
        S = JSON.parse(el.textContent);
        if (S.error) toast(S.error, 1);
    } catch (e) {  }
}
function toast(m, bad) {
    const t = $('#rdToast'); t.textContent = m; t.className = bad ? 'bad' : ''; t.style.display = 'block';
    clearTimeout(t.h); t.h = setTimeout(() => t.style.display = 'none', 3400);
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
const paneOn = p => $('#pane-' + p).classList.contains('on');
function showPane(p) {
    document.querySelectorAll('.rd-pane').forEach(x => x.classList.toggle('on', x.id == 'pane-' + p));
    document.querySelectorAll('.rd-nav [data-p]').forEach(b => b.classList.toggle('on', b.dataset.p == p));
    renderAll();
}

/* ---------------- Sơ đồ bàn ---------------- */
function rTables() {
    $('#tbl').innerHTML = S.tables.map(t => {
        const o = ordOf(t.id);
        return `<button class="rd-tb ${o ? 'busy' : (t.status == 'Reserved' ? 'resv' : 'free')} ${+t.surcharge_percent > 0 ? 'vip' : ''} ${curId == t.id ? 'sel' : ''}" onclick="pickTable(${t.id})">
            <b>${esc(t.table_name)}</b><small>${o ? esc(sc(o.order_code)) : (t.status == 'Reserved' ? 'Đã đặt' : t.capacity + ' chỗ')}</small></button>`;
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
        modal(esc(m.name), `<label>Độ chín</label><select id="dn"><option value="">Không ghi độ chín</option>${DONE.map(d => `<option>${d}</option>`).join('')}</select>
            <label>Ghi chú cho bếp</label><input id="nt" maxlength="200" list="noteList" placeholder="Ví dụ: dị ứng hải sản, ít muối">`, 'Thêm vào hóa đơn',
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

/* ---------------- Hóa đơn của bàn ---------------- */
// các món cách nhau quá 20 giây được tính là lần gọi khác
function batches(items) {
    const out = []; let last = null;
    items.forEach(i => { if (last === null || i.ts - last > 20) out.push([]); out[out.length - 1].push(i); last = i.ts; });
    return out;
}
function sentRow(i) {
    const c = i.kds_status == 'Cancelled';
    return `<div class="rd-ln ${c ? 'void' : ''}"><div><span class="nm">${i.quantity}× ${esc(i.name)}</span> <span class="rd-bg s-${i.kds_status}">${STL[i.kds_status] || i.kds_status}</span></div>
        <div>${money(i.quantity * i.unit_price)}</div>
        <small>${i.modifier_notes ? 'Ghi chú: ' + esc(i.modifier_notes) : ''}${c ? ' · Lý do hủy: ' + esc(i.cancel_reason) : ''}&nbsp;</small>
        <div class="rd-row" style="margin:0;justify-content:flex-end">${i.kds_status == 'Ready' ? `<button class="rd-btn rd-btn-line" style="padding:3px 9px;font-size:12px" onclick="adv(${i.id},'Served')">Đã bưng</button><button class="rd-btn rd-btn-line" style="padding:3px 9px;font-size:12px" onclick="adv(${i.id},'Served',true)">Mang về</button>` : ''}${['Waiting', 'Ready'].includes(i.kds_status) ? `<button class="rd-btn rd-btn-del" onclick="cancelM(${i.id})">Hủy</button>` : ''}</div></div>`;
}
// Mỗi món chưa gửi bếp có ô ghi chú ngay bên dưới (gợi ý nhanh: ít cay, không hành, dị ứng...)
function draftRow(d, i) {
    return `<div class="rd-ln"><div><span class="nm">${esc(d.name)}</span></div><div>${money(d.qty * d.price)}</div>
        <div style="grid-column:1/-1"><input class="rd-note" maxlength="200" list="noteList" value="${esc(d.notes)}" placeholder="Ghi chú cho bếp: ít cay, không hành, dị ứng..." oninput="DRAFTS[curId][${i}].notes=this.value"></div>
        <small>${money(d.price)} / món</small>
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
    const emptyOrder = o && live(o).length == 0 && !dr.length;
    h += `<div style="margin-top:12px"><div class="rd-tot"><span>Đã gửi bếp</span><span>${money(sent)}</span></div>
        ${dr.length ? `<div class="rd-tot"><span>Chờ gửi</span><span>${money(pend)}</span></div>` : ''}
        <div class="rd-tot big"><span>Tạm tính</span><span>${money(sent + pend)}</span></div></div>
        ${emptyOrder ? '<p class="rd-hint">Đơn này không còn món nào. Bạn có thể đóng bàn để trả bàn cho khách khác.</p>' : ''}
        <div class="rd-row end">${emptyOrder ? `<button class="rd-btn rd-btn-del" style="padding:8px 14px" onclick="closeTable(${o.id})">Đóng bàn</button>` : ''}
        <button class="rd-btn rd-btn-gold" ${dr.length ? '' : 'disabled'} onclick="sendOrder()">Gửi xuống Bếp/Bar (${dr.reduce((s, d) => s + d.qty, 0)} món)</button>
        ${o && !emptyOrder ? `<button class="rd-btn rd-btn-line" onclick="goPay(${o.id})">Thanh toán</button>` : ''}</div>
        <p class="rd-hint">Phụ thu, phí phục vụ, giảm giá và VAT được tính ở bước Thanh toán.</p>`;
    el.innerHTML = h;
}
async function sendOrder() {
    const t = curT(), dr = DRAFTS[t.id] || [];
    const r = await api('createOrder', {table_id: t.id, items: dr.map(d => ({menu_item_id: d.id, quantity: d.qty, modifier_notes: (d.notes || '').trim()}))});
    if (r.status != 'success') return toast(r.message || 'Gửi order thất bại', 1);
    DRAFTS[t.id] = [];
    toast('Đã gửi order bàn ' + t.table_name + ' xuống Bếp/Bar');
    await refreshAll();
}

/* ---------------- Hủy món / Chuyển bàn / Gộp bàn / Đóng bàn ---------------- */
function findItem(id) {
    for (const o of S.orders) { const i = o.items.find(x => x.id == id); if (i) return i; }
    return {};
}
function cancelM(id) {
    const it = findItem(id);
    const cooked = it.kds_status == 'Ready';
    const reasons = cooked ? ['Khách bỏ món / không dùng nữa', 'Món sai yêu cầu', 'Chờ quá lâu', 'Khác'] : ['Khách đổi ý', 'Nhập nhầm món', 'Hết nguyên liệu', 'Khác'];
    modal('Hủy món', `<p>${esc(it.quantity || '')}× ${esc(it.name || '')}. ${cooked ? 'Món đã nấu xong nên hủy sẽ gây hao hụt nguyên liệu. Lý do được ghi vào nhật ký để quản lý đối chiếu.' : 'Bếp/Bar chưa làm món này. Lý do được ghi vào nhật ký.'}</p>
        <label>Lý do hủy (bắt buộc)</label><select id="rs"><option value="">Chọn lý do</option>${reasons.map(x => `<option>${x}</option>`).join('')}</select>
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
        `<p>${m ? 'Gộp toàn bộ món của bàn ' + esc(t.table_name) + ' vào bàn đang có khách (dùng cho khách đi đoàn). Hóa đơn gộp tính phụ thu theo khu vực của bàn đích.' : 'Chuyển bàn ' + esc(t.table_name) + ' sang bàn trống. Món đã gửi bếp giữ nguyên trạng thái, phụ thu tự đổi theo khu vực mới.'}</p>
        <label>${m ? 'Gộp vào bàn' : 'Bàn đích'}</label><select id="to">${opts.map(x => `<option value="${x.id}">${esc(x.table_name)} · ${esc(x.area_name)}${+x.surcharge_percent > 0 ? ' · phụ thu ' + (+x.surcharge_percent) + '%' : ''}${m ? '' : ' · ' + x.capacity + ' chỗ'}</option>`).join('')}</select>`,
        m ? 'Gộp bàn' : 'Chuyển bàn', async () => {
            const to = +$('#to').value;
            if (!to) return m ? 'Không có bàn nào đang mở để gộp.' : 'Không còn bàn trống.';
            const r = await api('transferTable', {from_table_id: t.id, to_table_id: to, mode});
            if (r.status != 'success') return r.message;
            DRAFTS[to] = (DRAFTS[to] || []).concat(DRAFTS[t.id] || []); delete DRAFTS[t.id];
            toast(r.message); curId = to; await refreshAll();
        });
}
function closeTable(orderId) {
    modal('Đóng bàn', '<p>Đơn này đã hủy hết món (0₫). Đóng bàn để trả bàn cho khách mới. Thao tác được ghi nhật ký.</p>', 'Đóng bàn', async () => {
        const r = await api('closeEmptyOrder', {order_id: orderId});
        if (r.status != 'success') return r.message;
        toast(r.message);
        if (payId == orderId) { payId = null; PB = null; }
        curId = null; await refreshAll();
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
        const l = L.filter(i => i.kds_status == st);
        return `<div class="rd-card"><h3>${title} (${l.length})</h3>` + (l.length ? l.map(i => {
            const late = i.wait >= 15 && st != 'Ready';
            return `<div class="rd-tk ${late ? 'late' : ''}"><div class="tt"><span>Bàn ${esc(i.table_name)}</span><span>${i.wait} phút</span></div>
                <div class="qty">${i.quantity}× ${esc(i.name)}</div>
                <small>${i.modifier_notes ? `<b style="color:var(--g)">Ghi chú: ${esc(i.modifier_notes)}</b>` : 'Không ghi chú'} · ${i.station == 'Bar' ? 'Bar' : 'Bếp'}</small>
                <button class="rd-btn rd-btn-gold" onclick="adv(${i.id},'${to}')">${btn}</button>
                ${st == 'Ready' ? `<button class="rd-btn rd-btn-line" onclick="adv(${i.id},'Served',true)">Mang về (đóng hộp)</button>` : ''}
                ${st == 'Waiting' || st == 'Ready' ? `<button class="rd-btn rd-btn-del" onclick="cancelM(${i.id})">${st == 'Ready' ? 'Hủy món đã nấu' : 'Hủy món'}</button>` : ''}</div>`;
        }).join('') : '<div class="rd-empty">Không có món</div>') + '</div>';
    };
    $('#kc').innerHTML = col('Waiting', 'Chờ chế biến', 'Bắt đầu nấu', 'Cooking') + col('Cooking', 'Đang nấu', 'Hoàn thành · Báo phục vụ', 'Ready') + col('Ready', 'Sẵn sàng bưng', 'Đã bưng ra bàn', 'Served');
}
async function adv(id, to, takeaway = false) {
    const i = kdsItems().find(x => x.id == id);
    const r = await api('updateKds', {order_detail_id: id, status: to, takeaway});
    if (r.status != 'success') toast(r.message || 'Cập nhật thất bại', 1);
    else if (to == 'Ready' && i) toast(`Báo phục vụ: bàn ${i.table_name} có ${i.quantity}× ${i.name} sẵn sàng`);
    await refreshAll();
}

/* ---------------- Thanh toán (số tiền do server tính) ---------------- */
function rPayList() {
    const l = S.orders.map(o => ({o, t: S.tables.find(x => x.id == o.table_id)})).filter(x => x.t);
    $('#payList').innerHTML = l.length ? l.map(({o, t}) => `<button class="rd-tb busy wide ${+t.surcharge_percent > 0 ? 'vip' : ''} ${payId == o.id ? 'sel' : ''}" onclick="goPay(${o.id})"><b>Bàn ${esc(t.table_name)}</b><small>${esc(sc(o.order_code))} · ${money(sumO(o))}</small></button>`).join('')
        : '<div class="rd-empty">Không có bàn nào đang dùng bữa.</div>';
}
function goPay(id) {
    if (payId != id) { PH = ''; VC = ''; REF = ''; PB = null; }
    payId = id; showPane('pay'); loadBill();
}
async function loadBill() {
    if (!payId) { PB = null; rPayList(); rBill(); return; }
    const r = await apiGet('previewBill', `&order_id=${payId}&customer_phone=${encodeURIComponent(PH)}&voucher_code=${encodeURIComponent(VC)}`);
    PB = r.status == 'success' ? r.data : null;
    if (!PB || PB.status != 'Open') { if (!PB) toast(r.message || 'Không tải được hóa đơn', 1); PB = null; payId = null; }
    rPayList(); rBill();
}
// Thay thế hàm rBill() trong views/pos/index.php bằng đoạn code sau:
function rBill() {
    const el = $('#bill'), c = $('#payCtl');
    if (!PB) { el.innerHTML = '<div class="rd-empty">Chọn một bàn bên trái để xem hóa đơn.</div>'; c.innerHTML = ''; return; }
    const L = (a, v, x = '') => `<div class="rd-tot ${x}"><span>${a}</span><span>${v}</span></div>`;
    el.innerHTML = `<div class="rd-bh"><h3 style="margin:0">RoyalDine 5S</h3><small>Bàn ${esc(PB.table_name)} · ${esc(sc(PB.order_code))}</small></div>`
        + PB.items.map(i => L(`${i.quantity}× ${esc(i.name)}${i.modifier_notes ? ` <small style="color:var(--mu)">(${esc(i.modifier_notes)})</small>` : ''}`, money(i.quantity * i.unit_price))).join('') + '<hr>'
        + L('Tổng tiền món', money(PB.subtotal))
        + (PB.surcharge > 0 ? L(`Phụ thu ${esc(PB.area_name)} (${PB.surcharge_percent}%)`, money(PB.surcharge)) : '')
        + L('Phí phục vụ (5%)', money(PB.service))
        + (PB.tier_discount > 0 ? L(`Giảm giá hạng ${esc(PB.tier)}`, '− ' + money(PB.tier_discount)) : '')
        + (PB.voucher_discount > 0 ? L(`Giảm giá voucher ${esc(PB.voucher_code)}`, '− ' + money(PB.voucher_discount)) : '')
        + (PB.deposit_deducted > 0 ? L('Trừ tiền đặt cọc', '− ' + money(PB.deposit_deducted)) : '')
        + L('Thuế VAT (8%)', money(PB.vat))
        + L('KHÁCH CẦN THANH TOÁN', money(PB.total), 'big');

    const blocked = PB.phone_error || PB.voucher_error || PB.unfinished > 0 || PB.subtotal <= 0;

    c.innerHTML = `<h3>Thanh toán</h3>
        <label>Số điện thoại khách (tra hạng thẻ thành viên)</label>
        <input type="tel" value="${esc(PH)}" oninput="PH=this.value" placeholder="09xxxxxxxx">
        ${PB.customer_name ? `<div class="rd-ok">${esc(PB.customer_name)} · hạng ${esc(PB.tier)}</div>` : ''}
        ${PB.phone_error ? `
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:6px;">
                <div class="rd-er" style="margin:0;">${esc(PB.phone_error)}</div>
                <button type="button" class="rd-btn rd-btn-gold" style="padding:5px 12px;font-size:12px;" onclick="openCreateCustomerModal('${esc(PH)}')">
                    + Tạo khách hàng mới
                </button>
            </div>
        ` : ''}
        <label>Voucher</label>
        <select onchange="VC=this.value;loadBill()">
            <option value="">Không dùng voucher</option>
            ${S.promos.map(p => `<option value="${esc(p.code)}" ${VC == p.code ? 'selected' : ''}>${esc(p.code)} · ${esc(p.name)}</option>`).join('')}
        </select>
        ${PB.voucher_error ? `<div class="rd-er">${esc(PB.voucher_error)}</div>` : ''}
        <div class="rd-row"><button class="rd-btn rd-btn-line" onclick="loadBill()">Áp dụng</button></div>
        <h4>Phương thức thanh toán</h4>
        <div class="rd-chips">${[['Cash', 'Tiền mặt'], ['VietQR', 'VietQR'], ['CreditCard', 'Thẻ']].map(a => `<button class="rd-chip ${pm == a[0] ? 'on' : ''}" onclick="pm='${a[0]}';rBill()">${a[1]}</button>`).join('')}</div>
        ${pm == 'VietQR' ? `<div class="rd-qr"><img src="${esc(PB.vietqr_url)}" alt="Mã VietQR"><b>${money(PB.total)}</b><span>Nội dung: THANH TOAN HD ${esc(PB.order_code)}</span></div>` : ''}
        ${pm != 'Cash' ? `<label>Mã giao dịch / 4 số cuối thẻ</label><input value="${esc(REF)}" oninput="REF=this.value" maxlength="60">` : ''}
        ${PB.unfinished > 0 ? `<div class="rd-er">Còn ${PB.unfinished} món Bếp/Bar chưa làm xong, chưa thể thanh toán.</div>` : ''}
        ${PB.subtotal <= 0 ? '<div class="rd-er">Đơn không còn món nào để thu tiền.</div>' : ''}
        ${PB.ready_unserved > 0 ? `<div class="rd-hint" style="margin-top:8px">Còn ${PB.ready_unserved} món đã nấu xong chưa bấm "Đã bưng". Khi xác nhận thu tiền, hệ thống tự ghi nhận các món này là đã phục vụ.</div>` : ''}
        <div class="rd-row end">${PB.subtotal <= 0 ? `<button class="rd-btn rd-btn-del" style="padding:8px 14px" onclick="closeTable(${PB.order_id})">Đóng bàn</button>` : ''}
        <button class="rd-btn rd-btn-gold" ${blocked ? 'disabled' : ''} onclick="payM()">Xác nhận đã thu</button></div>`;
}

// Thêm hàm mở Popup Tạo khách hàng mới chuẩn giao diện 2 cột ngay dưới hàm rBill():
function openCreateCustomerModal(prefillPhone = '') {
    const cleanPhone = String(prefillPhone || '').replace(/\D/g, '');
    const m = $('#rdModal');
    m.innerHTML = `
        <div class="rd-mb" style="width:min(760px, 100%);padding:26px 28px;border-radius:14px;">
            <h3 style="margin-bottom:18px;font-size:20px;">Thêm khách hàng thành viên mới</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="font-weight:600;color:#1e293b;margin-top:0;">Họ và tên khách hàng *</label>
                    <input id="newCustName" type="text" maxlength="100" placeholder="Nhập đầy đủ họ và tên khách hàng">
                </div>
                <div>
                    <label style="font-weight:600;color:#1e293b;margin-top:0;">Số điện thoại di động *</label>
                    <input id="newCustPhone" type="tel" maxlength="11" value="${esc(cleanPhone)}" placeholder="Nhập số điện thoại 9-11 chữ số">
                </div>
            </div>
            <div style="margin-top:14px;">
                <label style="font-weight:600;color:#1e293b;">Địa chỉ Email</label>
                <input id="newCustEmail" type="email" maxlength="100" placeholder="vidu@email.com">
            </div>
            <div class="rd-er" id="rdEr" style="margin-top:10px;"></div>
            <hr style="margin:18px 0 14px;border-top:1px solid #e2e8f0;">
            <div class="rd-row end" style="margin-top:0;">
                <button type="button" class="rd-btn rd-btn-line" onclick="closeM()">Hủy bỏ</button>
                <button type="button" class="rd-btn rd-btn-gold" id="rdOk">Tạo hồ sơ khách hàng</button>
            </div>
        </div>
    `;
    m.classList.add('on');
    setTimeout(() => { const n = $('#newCustName'); if (n) n.focus(); }, 60);

    $('#rdOk').onclick = async () => {
        const btn = $('#rdOk');
        const fullName = ($('#newCustName').value || '').trim();
        const phone    = ($('#newCustPhone').value || '').trim();
        const email    = ($('#newCustEmail').value || '').trim();

        if (!fullName) {
            $('#rdEr').textContent = 'Vui lòng nhập họ và tên khách hàng.';
            return;
        }
        if (!/^[0-9]{9,11}$/.test(phone)) {
            $('#rdEr').textContent = 'Số điện thoại không hợp lệ (9-11 chữ số).';
            return;
        }

        btn.disabled = true;
        const res = await api('quickCreateCustomer', {
            full_name: fullName,
            phone: phone,
            email: email
        });

        if (res.status !== 'success') {
            $('#rdEr').textContent = res.message || 'Không thể tạo khách hàng.';
            btn.disabled = false;
            return;
        }

        closeM();
        PH = res.phone || phone;
        toast('Đã tạo hội viên ' + fullName + ' và đồng bộ vào CRM!');
        await loadBill();
    };
}
function payM() {
    const hint = pm == 'Cash' ? 'Hãy nhận đủ tiền mặt trước khi xác nhận.' : pm == 'VietQR' ? 'Chỉ xác nhận khi đã thấy tiền về tài khoản.' : 'Chỉ xác nhận khi máy POS thẻ báo thành công.';
    modal('Xác nhận thanh toán', `<p>Bàn ${esc(PB.table_name)} · ${esc(sc(PB.order_code))}</p><div class="rd-tot big"><span>Cần thu</span><span>${money(PB.total)}</span></div><p class="rd-hint">${hint}</p>`, 'Đã thu đủ tiền', async () => {
        const id = payId;
        const w = window.open('', '_blank', 'width=450,height=700');  
        const r = await api('checkout', {order_id: id, payment_method: pm, customer_phone: PH, voucher_code: VC, transaction_ref: REF});
        if (r.status != 'success') { if (w) w.close(); return r.message || 'Thanh toán thất bại'; }
        toast('Đã thu ' + money(r.total_amount));
        if (w) w.location = `index.php?c=pos&a=printBill&order_id=${id}`;
        else toast('Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup rồi mở lại hóa đơn từ trang Báo cáo.', 1);
        payId = null; PB = null; PH = ''; VC = ''; REF = ''; curId = null;
        await refreshAll(); showPane('pos');
    });
}

/* ---------------- Khởi động & tự làm mới ---------------- */
function renderAll() { rTables(); rMenu(); rOrder(); rKDS(); rPayList(); if (paneOn('pay')) rBill(); }
async function refreshAll() { await refreshState(); renderAll(); if (payId) await loadBill(); }
renderAll();
setInterval(async () => {
    if (document.hidden || $('#rdModal').classList.contains('on')) return;
    await refreshState();
    rTables(); rKDS(); rPayList();
    if (paneOn('pos')) rOrder();
}, 10000);
</script>