        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
/* 1. Ẩn khối tiêu đề trang & mô tả phụ trên mọi trang */
.rd-top-bar > div:first-child,
.rd-head > div:first-child,
.page-header-title,
.page-subtitle,
.rd-top-title,
.rd-top-desc,
.rd-hint,
.dash-header-left,
.dashboard-subtitle,
.card-subtitle,
.section-subtitle {
    display: none !important;
}

/* Đẩy cụm nút công cụ lên góc phải gọn gàng */
.rd-top-bar,
.rd-head {
    justify-content: flex-end !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 0 12px 0 !important;
    margin-bottom: 8px !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Danh sách toàn bộ tiêu đề trang & dòng mô tả hướng dẫn thừa cần ẩn
    const redundantTexts = [
        // Trang Tổng quan hoạt động (Dashboard)
        'tổng quan hoạt động',
        'theo dõi tình hình kinh doanh và vận hành nhà hàng',
        'đơn vị tính bằng vnđ, tổng hợp từ các hóa đơn',
        'đối chiếu doanh thu thực thu theo hình thức',
        'trên toàn bộ khu vực phục vụ',
        'tổng hợp trạng thái ca thu ngân, bếp kds',
        'nguyên vật liệu dưới hoặc chạm mức tồn tối thiểu',
        'tổng hợp từ các đơn hàng đã thanh toán',
        'nhật ký vận hành và thao tác hệ thống mới nhất',
        'tính trên các hóa đơn đã hoàn tất thanh toán',
        // Các trang Đặt bàn, Sơ đồ bàn, POS, Thực đơn, Nhân sự
        'quản lý đặt bàn và check-in',
        'điều phối lịch đặt bàn trước',
        'khi khách nhận bàn và bấm check-in',
        'thiết lập tầng, khu vực',
        'quản lý cấu trúc tầng, bàn',
        'sơ đồ bàn – floor',
        'sơ đồ bàn - floor',
        'ngày kiểm tra lịch:',
        'tầng/khu vực này hiện chưa có',
        'bán hàng pos, điều phối bếp/bar',
        'chọn bàn trống để mở hóa đơn mới',
        'quản lý thực đơn 5 sao',
        'thiết lập món ăn fine dining',
        'quản trị nhân sự, ca làm việc',
        'quản lý hồ sơ nhân viên, ma trận rbac',
        'cơ chế liên kết kho tự động:'
    ];

    document.querySelectorAll('h1, h2, h3, h4, p, small, span, div.alert, div').forEach(function (el) {
        if (el.querySelector('table, form, input, select, button, canvas, .rd-card-grid, .rd-mgrid')) {
            return;
        }
        const txt = (el.textContent || '').trim().toLowerCase();
        if (!txt) return;

        for (let i = 0; i < redundantTexts.length; i++) {
            if (txt === redundantTexts[i] || (txt.indexOf(redundantTexts[i]) !== -1 && txt.length < 140)) {
                el.style.display = 'none';
                // Nếu phần tử cha chỉ chứa tiêu đề vừa ẩn và dòng mô tả thì đẩy thanh công cụ bên phải sang
                if (el.parentElement && el.parentElement.children.length <= 2) {
                    const parentTxt = (el.parentElement.textContent || '').trim().toLowerCase();
                    if (parentTxt.indexOf('tổng quan hoạt động') !== -1) {
                        el.parentElement.style.display = 'none';
                        if (el.parentElement.parentElement) {
                            el.parentElement.parentElement.style.justifyContent = 'flex-end';
                        }
                    }
                }
                break;
            }
        }
    });

    // Ẩn tiêu đề h1 "RoyalDine 5S" trong trang POS
    document.querySelectorAll('.rd-head h1, .rd-head small').forEach(function (el) {
        el.style.display = 'none';
    });

    // 2. Làm sạch dấu ngoặc (...) và dấu gạch chéo / trên nút, nhãn, thẻ KPI và bảng dữ liệu
    function cleanLabelText(str) {
        if (!str) return str;
        return str
            .replace(/\s*\([^)]*\)/g, '')
            .replace(/Thêm Món\s*\/\s*Set Menu 5 Sao/gi, 'Thêm Món')
            .replace(/Tên Món\s*\/\s*Set Menu/gi, 'Tên Món')
            .replace(/Món ăn\s*\/\s*Set Menu/gi, 'Món ăn')
            .replace(/Bàn\s*\/\s*Phòng Riêng/gi, 'Bàn Phòng')
            .replace(/Bàn Đặt\s*\/\s*Phòng Riêng/gi, 'Bàn Phòng')
            .replace(/bàn\s*\/\s*phòng/gi, 'bàn phòng')
            .replace(/Khu Vực\s*\/\s*Sảnh Mới/gi, 'Khu Vực Mới')
            .replace(/Khu Vực\s*\/\s*Sảnh/gi, 'Khu Vực')
            .replace(/Sửa\s*\/\s*Đổi quyền/gi, 'Sửa')
            .replace(/Chỉnh sửa\s*\/\s*Xóa/gi, 'Thao tác')
            // Giữ định dạng ngày tháng dd/mm hoặc dd/mm/yyyy nhưng xóa dấu / phân cách text
            .replace(/(\d+)\s*\/\s*(\d+)\s*bàn/gi, '$1 trên $2 bàn')
            .replace(/([^\d])\s*\/\s*([^\d])/g, '$1 $2')
            .replace(/[←→+•]/g, '')
            .replace(/\s{2,}/g, ' ')
            .trim();
    }

    function sanitizeNodeText(root) {
        const selectors = [
            'button',
            'a.btn',
            'a.rd-btn',
            'a.hr-btn',
            '.rd-chip',
            '.rd-cat-tab',
            '.hr-tab-btn',
            'th',
            'td',
            'label',
            'h3',
            'h4',
            'h5',
            'strong',
            'option',
            '.rd-dish-cat',
            '.rd-kpi-label',
            '.hr-stat-label'
        ].join(',');

        root.querySelectorAll(selectors).forEach(function (el) {
            el.childNodes.forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '') {
                    const cleaned = cleanLabelText(node.nodeValue);
                    if (cleaned !== node.nodeValue.trim()) {
                        node.nodeValue = cleaned;
                    }
                }
            });
        });
    }

    sanitizeNodeText(document.body);

    const cleanObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    sanitizeNodeText(node);
                }
            });
        });
    });
    cleanObserver.observe(document.body, { childList: true, subtree: true });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Xóa các tiêu đề lớn đầu trang & hộp hướng dẫn thừa trên tất cả các Page
    const redundantTexts = [
        'quản lý đặt bàn và check-in',
        'điều phối lịch đặt bàn trước',
        'khi khách nhận bàn và bấm check-in',
        'thiết lập tầng, khu vực',
        'quản lý cấu trúc tầng, bàn',
        'sơ đồ bàn – floor',
        'sơ đồ bàn - floor',
        'ngày kiểm tra lịch:',
        'tầng/khu vực này hiện chưa có',
        'bán hàng pos, điều phối bếp/bar',
        'chọn bàn trống để mở hóa đơn mới',
        'quản lý thực đơn 5 sao',
        'thiết lập món ăn fine dining',
        'quản trị nhân sự, ca làm việc',
        'quản lý hồ sơ nhân viên, ma trận rbac',
        'cơ chế liên kết kho tự động:'
    ];

    document.querySelectorAll('h1, h2, p, small, div.alert, div').forEach(function (el) {
        // Chỉ kiểm tra các phần tử chứa text trực tiếp (không xóa nhầm khung chứa form/bảng)
        if (el.querySelector('table, form, input, select, button, .rd-card-grid, .rd-mgrid')) {
            return;
        }
        const txt = (el.textContent || '').trim().toLowerCase();
        if (!txt) return;

        for (let i = 0; i < redundantTexts.length; i++) {
            if (txt.indexOf(redundantTexts[i]) !== -1) {
                el.style.display = 'none';
                break;
            }
        }
    });

    // Ẩn tiêu đề h1 "RoyalDine 5S" trong trang POS nhưng giữ lại thanh tab Gọi món / Bếp / Thanh toán
    document.querySelectorAll('.rd-head h1, .rd-head small').forEach(function (el) {
        el.style.display = 'none';
    });

    // 2. Hàm làm sạch dấu ngoặc (...) và dấu gạch chéo / trong văn bản hiển thị
    function cleanLabelText(str) {
        if (!str) return str;
        return str
            // Xóa toàn bộ cụm nằm trong ngoặc đơn (...) như (ADMIN), (9), (Appetizers), (Symphony Hall), (cọc 5Tr)
            .replace(/\s*\([^)]*\)/g, '')
            // Xử lý các cụm từ có dấu / phổ biến thành tên ngắn gọn
            .replace(/Thêm Món\s*\/\s*Set Menu 5 Sao/gi, 'Thêm Món')
            .replace(/Tên Món\s*\/\s*Set Menu/gi, 'Tên Món')
            .replace(/Món ăn\s*\/\s*Set Menu/gi, 'Món ăn')
            .replace(/Bàn\s*\/\s*Phòng Riêng/gi, 'Bàn Phòng')
            .replace(/Bàn Đặt\s*\/\s*Phòng Riêng/gi, 'Bàn Phòng')
            .replace(/Bàn\s*\/\s*Phòng/gi, 'Bàn Phòng')
            .replace(/Khu Vực\s*\/\s*Sảnh Mới/gi, 'Khu Vực Mới')
            .replace(/Khu Vực\s*\/\s*Sảnh/gi, 'Khu Vực')
            .replace(/Sửa\s*\/\s*Đổi quyền/gi, 'Sửa')
            .replace(/Chỉnh sửa\s*\/\s*Xóa/gi, 'Thao tác')
            // Thay thế các dấu / còn lại bằng khoảng trắng
            .replace(/\s*\/\s*/g, ' ')
            // Xóa các mũi tên trang trí thừa trên nút như "← Quay lại"
            .replace(/[←→+•]/g, '')
            .replace(/\s{2,}/g, ' ')
            .trim();
    }

    function sanitizeNodeText(root) {
        const selectors = [
            'button',
            'a.btn',
            'a.rd-btn',
            'a.hr-btn',
            '.rd-chip',
            '.rd-cat-tab',
            '.hr-tab-btn',
            'th',
            'label',
            'h3',
            'h4',
            'h5',
            'option',
            '.rd-dish-cat',
            '.rd-kpi-label',
            '.hr-stat-label'
        ].join(',');

        root.querySelectorAll(selectors).forEach(function (el) {
            el.childNodes.forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim() !== '') {
                    const cleaned = cleanLabelText(node.nodeValue);
                    if (cleaned !== node.nodeValue.trim()) {
                        node.nodeValue = cleaned;
                    }
                }
            });
        });
    }

    sanitizeNodeText(document.body);

    // Tự động làm sạch cả các phần tử được render động bằng JS (như trên màn hình POS)
    const cleanObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    sanitizeNodeText(node);
                }
            });
        });
    });
    cleanObserver.observe(document.body, { childList: true, subtree: true });
});
</script>
</body>
</html>