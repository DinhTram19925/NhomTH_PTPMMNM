<?php /* CSS dùng chung cho views/pos/index.php và report.php - nền tối, vàng gold RoyalDine */ ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
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
    font: 700 24px "Cormorant Garamond", serif;
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
    font: 700 28px "Cormorant Garamond", serif;
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
    body {
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
</style>